<?php

namespace App\Filament\Resources\AktivitasPenggunas;

use App\Filament\Resources\AktivitasPenggunas\Pages\ManageAktivitasPenggunas;
use App\Models\AktivitasPengguna;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AktivitasPenggunaResource extends Resource
{
    protected static ?string $model = AktivitasPengguna::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Log Aktivitas';

    protected static ?string $modelLabel = 'Log Aktivitas';

    protected static ?string $pluralModelLabel = 'Log Aktivitas';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        return static::canAccess()
            ? parent::getAuthorizationResponse($action, $record)
            : Response::deny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('aksi')
                    ->label('Aksi')
                    ->badge()
                    ->sortable(),
                TextColumn::make('performedBy.name')
                    ->label('Dilakukan oleh')
                    ->placeholder('Sistem')
                    ->sortable(),
                TextColumn::make('metadata.model_type')
                    ->label('Model')
                    ->formatStateUsing(fn (?string $state): ?string => $state ? class_basename($state) : null)
                    ->searchable(),
                TextColumn::make('metadata.model_id')
                    ->label('ID Data')
                    ->sortable(),
                TextColumn::make('status_sebelum')
                    ->label('Status sebelum')
                    ->placeholder('-'),
                TextColumn::make('status_sesudah')
                    ->label('Status sesudah')
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAktivitasPenggunas::route('/'),
        ];
    }
}
