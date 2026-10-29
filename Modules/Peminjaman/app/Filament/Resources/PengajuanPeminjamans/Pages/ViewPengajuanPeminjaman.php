<?php

namespace Modules\Peminjaman\Filament\Resources\PengajuanPeminjamans\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Filament\Resources\PengajuanPeminjamans\PengajuanPeminjamanResource;
use Modules\Peminjaman\Models\ApprovalWorkflowStep;
use Modules\Peminjaman\Models\Peminjaman;
use Modules\Peminjaman\Services\PeminjamanService;
use RuntimeException;

class ViewPengajuanPeminjaman extends ViewRecord
{
    protected static string $resource = PengajuanPeminjamanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->authorize('peminjaman.pengajuan.update')
                ->visible(fn (): bool => in_array($this->record->status, [StatusPeminjaman::Draft, StatusPeminjaman::Submitted, StatusPeminjaman::Rejected], true)),
            Action::make('mulaiVerifikasi')
                ->label('Mulai Verifikasi')
                ->icon('heroicon-o-magnifying-glass')
                ->authorize('peminjaman.pemeriksaan.process')
                ->visible(fn (): bool => $this->record->status === StatusPeminjaman::Submitted)
                ->requiresConfirmation()
                ->action(function (PeminjamanService $service): void {
                    $service->review($this->getRecord());
                    $this->refreshFormData(['status', 'reviewed_at', 'reviewed_by']);
                }),
            Action::make('setujuiBendahara')
                ->label('Setujui ke Ketua')
                ->color('success')
                ->icon('heroicon-o-check')
                ->authorize('peminjaman.pengajuan.approve_bendahara')
                ->visible(fn (): bool => $this->record->status === StatusPeminjaman::InReview)
                ->requiresConfirmation()
                ->action(function (PeminjamanService $service): void {
                    $service->completeReview($this->getRecord());
                    $this->refreshFormData(['status']);
                }),
            Action::make('tolakBendahara')
                ->label('Tolak Verifikasi')
                ->color('danger')
                ->authorize('peminjaman.pemeriksaan.reject')
                ->visible(fn (): bool => $this->record->status === StatusPeminjaman::InReview)
                ->form([Textarea::make('reason')->label('Alasan Penolakan')->required()])
                ->action(function (array $data, PeminjamanService $service): void {
                    $service->rejectDuringReview($this->getRecord(), $data['reason']);
                    $this->refreshFormData(['status', 'rejection_stage', 'rejection_reason', 'rejected_at', 'rejected_by']);
                }),
            Action::make('prosesTahapTambahan')
                ->label(fn (): string => 'Setujui: '.($this->currentStep()?->step_name ?? 'Tahap Berikutnya'))
                ->color('success')
                ->authorize(fn (): bool => $this->canProcessCurrentStep())
                ->visible(fn (): bool => $this->hasAdditionalLoanStep())
                ->requiresConfirmation()
                ->action(function (PeminjamanService $service): void {
                    $service->processCurrentWorkflowStep($this->getRecord());
                    $this->refreshFormData(['status', 'approved_at', 'approved_by', 'disbursed_at', 'disbursed_by']);
                }),
            Action::make('ajukanPembatalan')
                ->label('Ajukan Pembatalan')
                ->color('danger')
                ->authorize('peminjaman.pengajuan.cancel')
                ->visible(fn (): bool => in_array($this->record->status, [StatusPeminjaman::Submitted, StatusPeminjaman::InReview], true)
                    && $this->record->anggota?->user_id === auth()->id())
                ->form([Textarea::make('reason')->label('Alasan Pembatalan')->required()])
                ->action(function (array $data, PeminjamanService $service): void {
                    $service->requestCancellation($this->getRecord(), $data['reason']);
                    $this->refreshFormData(['status', 'cancellation_reason', 'cancellation_requested_at']);
                }),
            Action::make('setujuiPembatalan')
                ->label(fn (): string => 'Setujui: '.($this->currentStep('cancellation')?->step_name ?? 'Pembatalan'))
                ->color('danger')
                ->authorize(fn (): bool => $this->canProcessCurrentStep('cancellation'))
                ->visible(fn (): bool => $this->record->status === StatusPeminjaman::CancellationRequested && $this->currentStep('cancellation') !== null)
                ->requiresConfirmation()
                ->action(function (PeminjamanService $service): void {
                    $service->approveCancellation($this->getRecord());
                    $this->refreshFormData(['status', 'cancellation_approved_at']);
                }),
            Action::make('tolakPembatalan')
                ->label('Lanjutkan Pengajuan')
                ->authorize(fn (): bool => $this->canProcessCurrentStep('cancellation'))
                ->visible(fn (): bool => $this->record->status === StatusPeminjaman::CancellationRequested)
                ->requiresConfirmation()
                ->action(function (PeminjamanService $service): void {
                    $service->rejectCancellation($this->getRecord());
                    $this->refreshFormData(['status', 'cancellation_reason', 'cancellation_requested_at']);
                }),
            Action::make('setujuiKetua')
                ->label('Setujui Ketua')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->authorize('peminjaman.persetujuan.approve_ketua')
                ->visible(fn (): bool => $this->record->status === StatusPeminjaman::ReadyForApproval)
                ->requiresConfirmation()
                ->action(function (PeminjamanService $service): void {
                    $service->approve($this->getRecord());
                    $this->refreshFormData(['status', 'approved_at', 'approved_by']);
                }),
            Action::make('tolakKetua')
                ->label('Tolak Persetujuan')
                ->color('danger')
                ->authorize('peminjaman.persetujuan.reject')
                ->visible(fn (): bool => $this->record->status === StatusPeminjaman::ReadyForApproval)
                ->form([Textarea::make('reason')->label('Alasan Penolakan')->required()])
                ->action(function (array $data, PeminjamanService $service): void {
                    $service->rejectByApproval($this->getRecord(), $data['reason']);
                    $this->refreshFormData(['status', 'rejection_stage', 'rejection_reason', 'rejected_at', 'rejected_by']);
                }),
            Action::make('cairkan')
                ->label('Cairkan')
                ->color('warning')
                ->icon('heroicon-o-banknotes')
                ->authorize('peminjaman.persetujuan.disburse')
                ->visible(fn (): bool => $this->record->status === StatusPeminjaman::Approved)
                ->requiresConfirmation()
                ->action(function (PeminjamanService $service): void {
                    try {
                        $service->disburse($this->getRecord());
                    } catch (RuntimeException $exception) {
                        Notification::make()->title('Pencairan tidak dapat diproses')->body($exception->getMessage())->danger()->persistent()->send();

                        return;
                    }

                    $this->refreshFormData(['status', 'disbursed_at', 'disbursed_by']);
                }),
        ];
    }

    private function currentStep(string $type = 'loan'): ?ApprovalWorkflowStep
    {
        /** @var Peminjaman $record */
        $record = $this->getRecord();
        $workflow = $record->approvalWorkflows()
            ->where('type', $type)
            ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
            ->latest('id')
            ->first();

        return $workflow?->currentStep();
    }

    private function canProcessCurrentStep(string $type = 'loan'): bool
    {
        $step = $this->currentStep($type);

        return $step !== null && (auth()->user()?->can($step->permission) ?? false);
    }

    private function hasAdditionalLoanStep(): bool
    {
        $step = $this->currentStep();

        return $step !== null
            && ! in_array($step->step_key, ['verification', 'ketua_approval', 'disbursement'], true)
            && $this->record->status === StatusPeminjaman::ReadyForApproval;
    }
}
