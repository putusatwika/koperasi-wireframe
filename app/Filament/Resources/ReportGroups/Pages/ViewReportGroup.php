<?php

namespace App\Filament\Resources\ReportGroups\Pages;

use App\Filament\Resources\ReportGroups\ReportGroupResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReportGroup extends ViewRecord
{
    protected static string $resource = ReportGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
