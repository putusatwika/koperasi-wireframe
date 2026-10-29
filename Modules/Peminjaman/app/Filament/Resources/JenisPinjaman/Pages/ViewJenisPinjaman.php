<?php

namespace Modules\Peminjaman\Filament\Resources\JenisPinjaman\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Peminjaman\Filament\Resources\JenisPinjaman\JenisPinjamanResource;

class ViewJenisPinjaman extends ViewRecord
{
    protected static string $resource = JenisPinjamanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->authorize('peminjaman.jenis_pinjaman.update'),
        ];
    }
}
