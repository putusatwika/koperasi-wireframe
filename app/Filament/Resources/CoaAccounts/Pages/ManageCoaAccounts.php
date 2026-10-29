<?php

namespace App\Filament\Resources\CoaAccounts\Pages;

use App\Filament\Resources\CoaAccounts\CoaAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCoaAccounts extends ManageRecords
{
    protected static string $resource = CoaAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
