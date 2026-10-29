<?php

namespace Modules\Keuangan\Filament\Resources\TransaksiKeuangans\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Modules\Keuangan\Filament\Pages\InputKeuangan;
use Modules\Keuangan\Filament\Resources\TransaksiKeuangans\TransaksiKeuanganResource;

class ListTransaksiKeuangan extends ListRecords
{
    protected static string $resource = TransaksiKeuanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('catatTransaksi')
                ->label('Catat Transaksi')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->authorize('keuangan.transaksi.create')
                ->url(InputKeuangan::getUrl()),
        ];
    }
}
