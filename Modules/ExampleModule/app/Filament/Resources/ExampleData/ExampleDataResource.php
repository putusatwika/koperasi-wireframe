<?php

namespace Modules\ExampleModule\Filament\Resources\ExampleData;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\ExampleModule\Filament\Resources\ExampleData\Pages\CreateExampleData;
use Modules\ExampleModule\Filament\Resources\ExampleData\Pages\EditExampleData;
use Modules\ExampleModule\Filament\Resources\ExampleData\Pages\ListExampleData;
use Modules\ExampleModule\Filament\Resources\ExampleData\Pages\ViewExampleData;
use Modules\ExampleModule\Filament\Resources\ExampleData\Schemas\ExampleDataForm;
use Modules\ExampleModule\Filament\Resources\ExampleData\Schemas\ExampleDataInfolist;
use Modules\ExampleModule\Filament\Resources\ExampleData\Tables\ExampleDataTable;
use Modules\ExampleModule\Models\ExampleData as ModelsExampleData;
use UnitEnum;

class ExampleDataResource extends Resource
{
    protected static ?string $model = ModelsExampleData::class;

    protected static string|UnitEnum|null $navigationGroup = 'Example Module';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ExampleDataForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ExampleDataInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExampleDataTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('examplemodule.example_data.viewAny') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('examplemodule.example_data.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('examplemodule.example_data.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('examplemodule.example_data.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('examplemodule.example_data.delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('examplemodule.example_data.delete') ?? false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return auth()->user()?->can('examplemodule.example_data.delete') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('examplemodule.example_data.delete') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExampleData::route('/'),
            'create' => CreateExampleData::route('/create'),
            'view' => ViewExampleData::route('/{record}'),
            'edit' => EditExampleData::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
