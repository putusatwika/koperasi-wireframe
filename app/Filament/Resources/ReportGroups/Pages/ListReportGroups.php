<?php

namespace App\Filament\Resources\ReportGroups\Pages;

use App\Filament\Resources\ReportGroups\ReportGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReportGroups extends ListRecords
{
    protected static string $resource = ReportGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
