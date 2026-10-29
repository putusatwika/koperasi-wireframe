<?php

namespace App\Filament\Resources\ArsipBackups;

use App\Filament\Resources\ArsipBackups\Pages\ManageArsipBackups;
use App\Models\ArsipBackup;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
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
use Illuminate\Support\Str;
use UnitEnum;

class ArsipBackupResource extends Resource
{
    protected static ?string $model = ArsipBackup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Arsip Backup';

    protected static ?string $modelLabel = 'Arsip Backup';

    protected static ?string $pluralModelLabel = 'Arsip Backup';

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
                TextInput::make('uuid')
                    ->label('UUID')
                    ->default(fn (): string => Str::uuid()->toString())
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                TextInput::make('jenis')
                    ->required(),
                TextInput::make('disk')
                    ->required(),
                TextInput::make('path')
                    ->required(),
                TextInput::make('checksum')
                    ->required(),
                TextInput::make('ukuran')
                    ->required()
                    ->numeric(),
                Toggle::make('terenkripsi')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('creating'),
                Select::make('created_by')
                    ->label('Dibuat oleh')
                    ->relationship('createdBy', 'name')
                    ->searchable()
                    ->preload(),
                DateTimePicker::make('verified_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable(),
                TextColumn::make('jenis')
                    ->searchable(),
                TextColumn::make('disk')
                    ->searchable(),
                TextColumn::make('path')
                    ->searchable(),
                TextColumn::make('checksum')
                    ->searchable(),
                TextColumn::make('ukuran')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('terenkripsi')
                    ->boolean(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('createdBy.name')
                    ->label('Dibuat oleh')
                    ->sortable(),
                TextColumn::make('verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => ManageArsipBackups::route('/'),
        ];
    }
}
