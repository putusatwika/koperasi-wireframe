<?php

namespace Modules\Pembayaran\Filament\Resources\Histori\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PembayaranInfolist
{
    public static function configure(Schema $schema, bool $showMember = true): Schema
    {
        $components = [
            TextEntry::make('payment_number')->label('Nomor Pembayaran'),
        ];

        if ($showMember) {
            $components[] = TextEntry::make('anggota.user.name')->label('Anggota');
            $components[] = TextEntry::make('anggota.member_number')->label('Nomor Anggota');
        }

        return $schema
            ->components([
                ...$components,
                TextEntry::make('peminjaman.loan_number')->label('Nomor Pinjaman'),
                TextEntry::make('payment_type')->label('Jenis')->badge(),
                TextEntry::make('payment_date')->label('Tanggal')->date('d/m/Y'),
                TextEntry::make('amount')->label('Jumlah')->money('IDR'),
                TextEntry::make('principal_amount')->label('Pokok')->money('IDR'),
                TextEntry::make('interest_amount')->label('Bunga')->money('IDR'),
                TextEntry::make('penalty_amount')->label('Denda')->money('IDR'),
                TextEntry::make('reference_number')->label('Nomor Referensi'),
                TextEntry::make('receipt_document')->label('Bukti Pembayaran'),
                TextEntry::make('notes')->label('Catatan')->columnSpanFull(),
                TextEntry::make('createdBy.name')->label('Dicatat Oleh'),
                TextEntry::make('created_at')->label('Waktu Pencatatan')->dateTime(),
            ]);
    }
}
