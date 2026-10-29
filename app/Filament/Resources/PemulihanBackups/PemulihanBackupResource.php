<?php

namespace App\Filament\Resources\PemulihanBackups;

use App\Filament\Resources\PemulihanBackups\Pages\ManagePemulihanBackups;
use App\Jobs\VerifyPemulihanBackup;
use App\Models\PemulihanBackup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PemulihanBackupResource extends Resource
{
    protected static ?string $model = PemulihanBackup::class;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $navigationLabel = 'Pemulihan Backup';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        return static::canAccess() ? parent::getAuthorizationResponse($action, $record) : Response::deny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('uuid')->uuid()->required()->unique(ignoreRecord: true),
            Select::make('arsip_backup_id')->relationship('arsipBackup', 'uuid')->searchable()->preload()->required(),
            Textarea::make('alasan')->required(),
            Select::make('status')->options(['requested' => 'Requested', 'verifying' => 'Verifying', 'verified' => 'Verified', 'completed' => 'Completed', 'failed' => 'Failed'])->default('requested')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('uuid')->searchable(),
            TextColumn::make('arsipBackup.uuid')->label('Backup')->searchable(),
            TextColumn::make('status')->badge()->sortable(),
            TextColumn::make('requestedBy.name')->label('Diminta oleh'),
            TextColumn::make('completed_at')->dateTime()->sortable(),
        ])->recordActions([
            Action::make('verify')->label('Verifikasi')->authorize('sistem.restore.verify')->visible(fn (PemulihanBackup $record): bool => in_array($record->status, ['requested', 'failed'], true))->action(fn (PemulihanBackup $record): mixed => VerifyPemulihanBackup::dispatch($record->fresh())),
            Action::make('execute')->label('Eksekusi')->authorize('sistem.restore.execute')->visible(fn (PemulihanBackup $record): bool => $record->status === 'verified')->requiresConfirmation()->action(fn (PemulihanBackup $record): bool => $record->update(['status' => 'completed', 'confirmed_at' => now(), 'completed_at' => now()])),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePemulihanBackups::route('/')];
    }
}
