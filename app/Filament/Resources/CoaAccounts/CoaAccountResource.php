<?php

namespace App\Filament\Resources\CoaAccounts;

use App\Filament\Resources\CoaAccounts\Pages\ManageCoaAccounts;
use App\Models\CoaAccount;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CoaAccountResource extends Resource
{
    protected static ?string $model = CoaAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Core';

    protected static ?string $navigationLabel = 'COA Accounts';

    protected static ?string $modelLabel = 'COA Account';

    protected static ?string $pluralModelLabel = 'COA Accounts';

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
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(30)
                    ->unique(ignoreRecord: true),
                TextInput::make('key_map')
                    ->required()
                    ->maxLength(100)
                    ->rules(['required', 'regex:/^[A-Z0-9_]+$/'])
                    ->dehydrateStateUsing(fn (string $state): string => strtoupper((string) preg_replace('/\s+/', '_', trim($state)))),
                TextInput::make('name')
                    ->required()
                    ->maxLength(150),
                Select::make('account_type_id')
                    ->relationship('accountType', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('normal_balance')
                    ->options([
                        'DEBIT' => 'Debit',
                        'CREDIT' => 'Credit',
                    ])
                    ->required(),
                Select::make('parent_id')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('level')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->default(1),
                Toggle::make('is_postable')
                    ->default(true)
                    ->required(),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
                Textarea::make('description')
                    ->maxLength(1000),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('key_map')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('accountType.name')->label('Account Type')->sortable(),
                TextColumn::make('normal_balance')->badge()->sortable(),
                TextColumn::make('parent.name')->label('Parent'),
                TextColumn::make('level')->numeric()->sortable(),
                IconColumn::make('is_postable')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCoaAccounts::route('/'),
        ];
    }
}
