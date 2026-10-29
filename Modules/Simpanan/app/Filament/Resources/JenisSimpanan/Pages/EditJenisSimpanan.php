<?php

namespace Modules\Simpanan\Filament\Resources\JenisSimpanan\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Simpanan\Filament\Resources\JenisSimpanan\JenisSimpananResource;

class EditJenisSimpanan extends EditRecord
{
    protected static string $resource = JenisSimpananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->authorize('simpanan.jenis_simpanan.delete'),
        ];
    }
}
