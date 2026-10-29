<?php

namespace Modules\ExampleModule\Filament\Resources\ExampleData\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\ExampleModule\Filament\Resources\ExampleData\ExampleDataResource;

class ViewExampleData extends ViewRecord
{
    protected static string $resource = ExampleDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->authorize('examplemodule.example_data.update'),
        ];
    }
}
