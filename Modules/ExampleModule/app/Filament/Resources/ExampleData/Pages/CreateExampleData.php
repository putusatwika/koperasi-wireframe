<?php

namespace Modules\ExampleModule\Filament\Resources\ExampleData\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\ExampleModule\Filament\Resources\ExampleData\ExampleDataResource;
use Modules\ExampleModule\Services\ExampleDataService;

class CreateExampleData extends CreateRecord
{
    protected static string $resource = ExampleDataResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(ExampleDataService::class)->create($data);
    }
}
