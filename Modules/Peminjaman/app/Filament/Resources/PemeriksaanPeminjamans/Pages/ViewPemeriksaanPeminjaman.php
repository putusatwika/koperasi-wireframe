<?php

namespace Modules\Peminjaman\Filament\Resources\PemeriksaanPeminjamans\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Filament\Resources\PemeriksaanPeminjamans\PemeriksaanPeminjamanResource;
use Modules\Peminjaman\Services\PeminjamanService;

class ViewPemeriksaanPeminjaman extends ViewRecord
{
    protected static string $resource = PemeriksaanPeminjamanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mulaiPemeriksaan')
                ->label('Mulai Pemeriksaan')
                ->icon('heroicon-o-magnifying-glass')
                ->authorize('peminjaman.pemeriksaan.process')
                ->visible(fn (): bool => $this->record->status === StatusPeminjaman::Submitted)
                ->requiresConfirmation()
                ->action(function (PeminjamanService $service): void {
                    $service->review($this->getRecord());
                    $this->refreshFormData(['status', 'reviewed_at', 'reviewed_by']);
                }),
            Action::make('berkasLengkap')
                ->label('Berkas Lengkap')
                ->color('success')
                ->icon('heroicon-o-check')
                ->authorize('peminjaman.pemeriksaan.process')
                ->visible(fn (): bool => $this->record->status === StatusPeminjaman::InReview)
                ->form([
                    Textarea::make('rekomendasi')
                        ->label('Catatan Rekomendasi untuk Ketua')
                        ->placeholder('Contoh: Kas tersedia, dokumen lengkap, layak disetujui.')
                        ->required()
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data, PeminjamanService $service): void {
                    $service->completeReview($this->getRecord(), $data['rekomendasi']);
                    $this->refreshFormData(['status', 'rekomendasi', 'rekomendasi_at', 'rekomendasi_by']);
                }),
            Action::make('tolakPemeriksaan')
                ->label('Tolak Pemeriksaan')
                ->color('danger')
                ->icon('heroicon-o-x-mark')
                ->authorize('peminjaman.pemeriksaan.reject')
                ->visible(fn (): bool => $this->record->status === StatusPeminjaman::InReview)
                ->form([
                    Textarea::make('reason')->label('Pesan Penolakan')->required(),
                ])
                ->action(function (array $data, PeminjamanService $service): void {
                    $service->rejectDuringReview($this->getRecord(), $data['reason']);
                    $this->refreshFormData(['status', 'rejection_stage', 'rejection_reason', 'rejected_at', 'rejected_by']);
                }),
        ];
    }
}
