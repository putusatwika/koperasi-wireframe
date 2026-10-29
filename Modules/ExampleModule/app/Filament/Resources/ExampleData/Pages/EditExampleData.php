<?php

namespace Modules\ExampleModule\Filament\Resources\ExampleData\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Modules\ExampleModule\Filament\Resources\ExampleData\ExampleDataResource;

class EditExampleData extends EditRecord
{
    protected static string $resource = ExampleDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->authorize('examplemodule.example_data.view'),
            DeleteAction::make()
                ->authorize('examplemodule.example_data.delete'),
            ForceDeleteAction::make()
                ->authorize('examplemodule.example_data.delete'),
            RestoreAction::make()
                ->authorize('examplemodule.example_data.delete'),
        ];
    }
}
