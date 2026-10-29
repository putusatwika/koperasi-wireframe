<?php

namespace App\Filament\Resources\PesanNotifikasis\Pages;

use App\Filament\Resources\PesanNotifikasis\PesanNotifikasiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePesanNotifikasis extends ManageRecords
{
    protected static string $resource = PesanNotifikasiResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->authorize('sistem.notifikasi.create')];
    }
}
