<?php

namespace App\Filament\Resources\ReportGroups;

use App\Filament\Resources\ReportGroups\Pages\CreateReportGroup;
use App\Filament\Resources\ReportGroups\Pages\EditReportGroup;
use App\Filament\Resources\ReportGroups\Pages\ListReportGroups;
use App\Filament\Resources\ReportGroups\Pages\ViewReportGroup;
use App\Filament\Resources\ReportGroups\RelationManagers\ReportGroupLinesRelationManager;
use App\Filament\Resources\ReportGroups\Schemas\ReportGroupForm;
use App\Filament\Resources\ReportGroups\Schemas\ReportGroupInfolist;
use App\Filament\Resources\ReportGroups\Tables\ReportGroupsTable;
use App\Models\ReportGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ReportGroupResource extends Resource
{
    protected static ?string $model = ReportGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Core';

    protected static ?string $navigationLabel = 'Report Groups';

    protected static ?string $modelLabel = 'Report Group';

    protected static ?string $pluralModelLabel = 'Report Groups';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        return (auth()->user()?->hasRole('super_admin') ?? false)
            ? parent::getAuthorizationResponse($action, $record)
            : Response::deny();
    }

    public static function form(Schema $schema): Schema
    {
        return ReportGroupForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReportGroupInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReportGroupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ReportGroupLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReportGroups::route('/'),
            'create' => CreateReportGroup::route('/create'),
            'view' => ViewReportGroup::route('/{record}'),
            'edit' => EditReportGroup::route('/{record}/edit'),
        ];
    }
}
