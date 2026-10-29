<?php

namespace Modules\Peminjaman\Filament\Resources\Tables;

use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Models\Peminjaman;
use Modules\Peminjaman\Services\PeminjamanService;
use Throwable;

class DaftarPeminjamanTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(PeminjamanTableColumns::columns())
            ->filters(PeminjamanTableColumns::filters())
            ->recordActions([
                ViewAction::make()->authorize('peminjaman.daftar_peminjaman.view'),
                Action::make('tandaiLunas')
                    ->label('Tandai Lunas')
                    ->color('success')
                    ->icon('heroicon-o-check-badge')
                    ->authorize('peminjaman.daftar_peminjaman.lunas')
                    ->visible(fn (Peminjaman $record): bool => $record->status === StatusPeminjaman::Disbursed)
                    ->requiresConfirmation()
                    ->modalHeading('Tandai pinjaman lunas?')
                    ->modalDescription(fn (Peminjaman $record): string => sprintf(
                        'Sisa pokok yang belum dibayar: Rp %s. Status pinjaman akan diubah menjadi Lunas.',
                        number_format((float) $record->jadwalAngsurans->sum(
                            fn ($installment): float => (float) $installment->pokok - (float) $installment->dibayar_pokok,
                        ), 2, ',', '.'),
                    ))
                    ->action(function (Peminjaman $record): void {
                        try {
                            app(PeminjamanService::class)->tandaiLunas($record);
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Gagal menandai lunas')
                                ->body($exception->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        Notification::make()->title('Pinjaman ditandai lunas')->success()->send();
                    }),
            ]);
    }
}
