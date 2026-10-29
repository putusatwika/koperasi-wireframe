<?php

namespace Modules\Keanggotaan\Filament\Resources\Anggotas\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Keanggotaan\Filament\Resources\Anggotas\AnggotaResource;

class ViewAnggota extends ViewRecord
{
    protected static string $resource = AnggotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->authorize('keanggotaan.anggota.update'),
        ];
    }
}
