<?php

namespace Modules\Keuangan\Filament\Resources\KategoriKeuangans\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Keuangan\Filament\Resources\KategoriKeuangans\KategoriKeuanganResource;

class ListKategoriKeuangan extends ListRecords
{
    protected static string $resource = KategoriKeuanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->authorize('keuangan.kategori.create'),
        ];
    }
}
