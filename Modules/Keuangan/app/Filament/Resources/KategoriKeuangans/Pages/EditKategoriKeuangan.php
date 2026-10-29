<?php

namespace Modules\Keuangan\Filament\Resources\KategoriKeuangans\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Keuangan\Filament\Resources\KategoriKeuangans\KategoriKeuanganResource;

class EditKategoriKeuangan extends EditRecord
{
    protected static string $resource = KategoriKeuanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->authorize('keuangan.kategori.delete'),
        ];
    }
}
