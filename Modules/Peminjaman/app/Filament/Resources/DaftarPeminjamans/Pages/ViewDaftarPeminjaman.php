<?php

namespace Modules\Peminjaman\Filament\Resources\DaftarPeminjamans\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Filament\Resources\DaftarPeminjamans\DaftarPeminjamanResource;
use Modules\Peminjaman\Services\PeminjamanService;

class ViewDaftarPeminjaman extends ViewRecord
{
    protected static string $resource = DaftarPeminjamanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tandaiLunas')
                ->label('Tandai Lunas')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->authorize('peminjaman.daftar_peminjaman.lunas')
                ->visible(fn (): bool => $this->getRecord()->status === StatusPeminjaman::Disbursed)
                ->requiresConfirmation()
                ->modalHeading('Tandai pinjaman lunas?')
                ->modalDescription(function (): string {
                    $record = $this->getRecord();
                    $sisa = (float) $record->jadwalAngsurans->sum(
                        fn ($installment): float => (float) $installment->pokok - (float) $installment->dibayar_pokok,
                    );

                    return sprintf(
                        'Sisa pokok yang belum dibayar: Rp %s. Status pinjaman akan diubah menjadi Lunas.',
                        number_format($sisa, 2, ',', '.'),
                    );
                })
                ->action(function (PeminjamanService $service): void {
                    $service->tandaiLunas($this->getRecord());
                    $this->refreshFormData(['status', 'lunas_at', 'lunas_by']);
                }),
        ];
    }
}
