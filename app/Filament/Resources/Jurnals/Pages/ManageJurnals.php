<?php

namespace App\Filament\Resources\Jurnals\Pages;

use App\Filament\Resources\Jurnals\JurnalResource;
use App\Models\Jurnal;
use App\Services\Accounting\JurnalService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageJurnals extends ManageRecords
{
    protected static string $resource = JurnalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): Jurnal {
                    return app(JurnalService::class)->create($data);
                }),
        ];
    }
}
