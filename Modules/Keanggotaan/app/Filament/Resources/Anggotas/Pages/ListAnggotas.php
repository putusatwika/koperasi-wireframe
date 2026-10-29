<?php

namespace Modules\Keanggotaan\Filament\Resources\Anggotas\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Keanggotaan\Filament\Resources\Anggotas\AnggotaResource;

class ListAnggotas extends ListRecords
{
    protected static string $resource = AnggotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->authorize('keanggotaan.anggota.create'),
        ];
    }
}
