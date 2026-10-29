<?php

namespace Modules\Peminjaman\Filament\Resources\JenisPinjaman\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Peminjaman\Filament\Resources\JenisPinjaman\JenisPinjamanResource;

class EditJenisPinjaman extends EditRecord
{
    protected static string $resource = JenisPinjamanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->authorize('peminjaman.jenis_pinjaman.view'),
            DeleteAction::make()->authorize('peminjaman.jenis_pinjaman.delete'),
        ];
    }
}
