<?php

namespace Modules\Simpanan\Filament\Resources\JenisSimpanan\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Simpanan\Filament\Resources\JenisSimpanan\JenisSimpananResource;

class ListJenisSimpanan extends ListRecords
{
    protected static string $resource = JenisSimpananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->authorize('simpanan.jenis_simpanan.create'),
        ];
    }
}
