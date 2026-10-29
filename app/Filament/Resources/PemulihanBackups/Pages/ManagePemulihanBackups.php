<?php

namespace App\Filament\Resources\PemulihanBackups\Pages;

use App\Filament\Resources\PemulihanBackups\PemulihanBackupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePemulihanBackups extends ManageRecords
{
    protected static string $resource = PemulihanBackupResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->authorize('sistem.restore.create')];
    }
}
