<?php

namespace App\Filament\Resources\ReportGroups\Pages;

use App\Filament\Resources\ReportGroups\ReportGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditReportGroup extends EditRecord
{
    protected static string $resource = ReportGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
