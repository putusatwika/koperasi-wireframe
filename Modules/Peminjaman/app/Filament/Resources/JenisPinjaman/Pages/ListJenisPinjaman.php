<?php

namespace Modules\Peminjaman\Filament\Resources\JenisPinjaman\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Peminjaman\Filament\Resources\JenisPinjaman\JenisPinjamanResource;

class ListJenisPinjaman extends ListRecords
{
    protected static string $resource = JenisPinjamanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->authorize('peminjaman.jenis_pinjaman.create'),
        ];
    }
}
