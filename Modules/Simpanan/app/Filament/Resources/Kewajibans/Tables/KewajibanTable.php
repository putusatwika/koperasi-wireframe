<?php

namespace Modules\Simpanan\Filament\Resources\Kewajibans\Tables;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Simpanan\Enums\StatusKewajiban;
use Modules\Simpanan\Models\KewajibanSimpanan;
use Modules\Simpanan\Services\SimpananService;
use Throwable;

class KewajibanTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('anggota.member_number')->label('No. Anggota')->searchable(),
                TextColumn::make('anggota.user.name')
                    ->label('Anggota')
                    ->searchable(),
                TextColumn::make('jenisSimpanan.nama')->label('Produk'),
                TextColumn::make('periode')->label('Periode'),
                TextColumn::make('nominal')->label('Nominal')->money('IDR')->sortable(),
                TextColumn::make('jatuh_tempo')->label('Jatuh Tempo')->date()->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (StatusKewajiban|string $state): string => $state instanceof StatusKewajiban ? $state->name : $state),
                TextColumn::make('paid_at')->label('Dibayar')->dateTime()->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(StatusKewajiban::cases())->mapWithKeys(
                        fn (StatusKewajiban $status): array => [$status->value => $status->name],
                    )->all()),
            ])
            ->recordActions([
                Action::make('bayar')
                    ->label('Tandai Lunas')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->authorize('simpanan.kewajiban.update')
                    ->visible(fn (KewajibanSimpanan $record): bool => $record->status === StatusKewajiban::Pending)
                    ->form([
                        TextInput::make('nominal')
                            ->label('Nominal Pembayaran')
                            ->numeric()
                            ->minValue(1)
                            ->default(fn (KewajibanSimpanan $record): float => (float) $record->nominal)
                            ->required(),
                    ])
                    ->action(function (KewajibanSimpanan $record, array $data): void {
                        try {
                            app(SimpananService::class)->bayarKewajiban($record, (float) $data['nominal']);
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Pembayaran kewajiban gagal')
                                ->body($exception->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        Notification::make()->title('Kewajiban simpanan ditandai lunas')->success()->send();
                    }),
            ]);
    }
}
