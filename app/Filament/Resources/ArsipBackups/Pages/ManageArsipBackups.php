<?php

namespace App\Filament\Resources\ArsipBackups\Pages;

use App\Filament\Resources\ArsipBackups\ArsipBackupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageArsipBackups extends ManageRecords
{
    protected static string $resource = ArsipBackupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
