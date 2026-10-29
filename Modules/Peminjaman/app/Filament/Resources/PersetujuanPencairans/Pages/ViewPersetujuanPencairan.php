<?php

namespace Modules\Peminjaman\Filament\Resources\PersetujuanPencairans\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Filament\Resources\PersetujuanPencairans\PersetujuanPencairanResource;
use Modules\Peminjaman\Services\PeminjamanService;
use RuntimeException;

class ViewPersetujuanPencairan extends ViewRecord
{
    protected static string $resource = PersetujuanPencairanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('setujui')
                ->label('Setujui')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->authorize('peminjaman.persetujuan.approve')
                ->visible(fn (): bool => $this->record->status === StatusPeminjaman::ReadyForApproval)
                ->requiresConfirmation()
                ->action(function (PeminjamanService $service): void {
                    $service->approve($this->getRecord());
                    $this->refreshFormData(['status', 'approved_at', 'approved_by']);
                }),
            Action::make('tolakPersetujuan')
                ->label('Tolak Persetujuan')
                ->color('danger')
                ->icon('heroicon-o-x-mark')
                ->authorize('peminjaman.persetujuan.reject')
                ->visible(fn (): bool => $this->record->status === StatusPeminjaman::ReadyForApproval)
                ->form([
                    Textarea::make('reason')->label('Pesan Penolakan')->required(),
                ])
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
                        Notification::make()
                            ->title('Pencairan tidak dapat diproses')
                            ->body($exception->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    $this->refreshFormData(['status', 'disbursed_at', 'disbursed_by']);
                }),
        ];
    }
}
