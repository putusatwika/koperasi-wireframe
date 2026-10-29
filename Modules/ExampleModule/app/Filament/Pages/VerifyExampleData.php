<?php

namespace Modules\ExampleModule\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class VerifyExampleData extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Example Module';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('examplemodule.verify_example_data.view') ?? false;
    }

    protected string $view = 'examplemodule::filament.pages.verify-example-data';
}
