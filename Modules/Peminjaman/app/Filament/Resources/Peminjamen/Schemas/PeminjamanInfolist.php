<?php

namespace Modules\Peminjaman\Filament\Resources\Peminjamen\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Modules\Peminjaman\Models\Peminjaman;

class PeminjamanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('loan_number')->label('Nomor Pinjaman'),
                TextEntry::make('anggota.user.name')->label('Anggota'),
                TextEntry::make('anggota.member_number')->label('Nomor Anggota'),
                TextEntry::make('account_number')->label('Nomor Rekening'),
                TextEntry::make('loan_amount')->label('Jumlah Pinjaman')->money('IDR'),
                TextEntry::make('interest_type')->label('Jenis Bunga')->badge(),
                TextEntry::make('interest_rate')
                    ->label('Bunga per Bulan (%)')
                    ->formatStateUsing(fn (mixed $state): mixed => filled($state) ? (float) $state / 12 : $state)
                    ->suffix('%'),
                TextEntry::make('term_months')->label('Tenor')->suffix(' bulan'),
                TextEntry::make('purpose')->label('Peruntukan')->badge(),
                TextEntry::make('status')->label('Status')->badge(),
                TextEntry::make('rekomendasi')->label('Rekomendasi Bendahara')->placeholder('-')->columnSpanFull(),
                TextEntry::make('rekomendasiBy.name')->label('Direkomendasikan Oleh')->placeholder('-'),
                TextEntry::make('rekomendasi_at')->label('Direkomendasikan')->dateTime()->placeholder('-'),
                TextEntry::make('approval_progress')
                    ->label('Tahap Aktif')
                    ->state(function (Peminjaman $record): string {
                        $workflow = $record->approvalWorkflows()
                            ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
                            ->latest('id')
                            ->first();

                        return $workflow?->currentStep()?->step_name ?? 'Tidak ada';
                    }),
                TextEntry::make('rejection_stage')->label('Tahap Penolakan')->badge(),
                TextEntry::make('rejection_reason')->label('Alasan Penolakan'),
                TextEntry::make('cancellation_reason')->label('Alasan Pembatalan'),
                TextEntry::make('submitted_at')->label('Diajukan')->dateTime(),
                TextEntry::make('cancellation_requested_at')->label('Pembatalan Diajukan')->dateTime(),
                TextEntry::make('approved_at')->label('Disetujui')->dateTime(),
                TextEntry::make('disbursed_at')->label('Dicairkan')->dateTime(),
            ]);
    }
}
