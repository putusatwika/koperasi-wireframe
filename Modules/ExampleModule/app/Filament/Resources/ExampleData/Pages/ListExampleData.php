<?php

namespace Modules\ExampleModule\Filament\Resources\ExampleData\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\ExampleModule\Filament\Resources\ExampleData\ExampleDataResource;

class ListExampleData extends ListRecords
{
    protected static string $resource = ExampleDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->authorize('examplemodule.example_data.create'),
        ];
    }
}
