<?php

namespace App\Filament\Resources\PesanNotifikasis;

use App\Filament\Resources\PesanNotifikasis\Pages\ManagePesanNotifikasis;
use App\Jobs\ProcessPesanNotifikasi;
use App\Models\PesanNotifikasi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PesanNotifikasiResource extends Resource
{
    protected static ?string $model = PesanNotifikasi::class;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Notifikasi';

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
            TextInput::make('deduplication_key')->uuid()->required()->unique(ignoreRecord: true),
            TextInput::make('tujuan')->required(),
            TextInput::make('template')->required(),
            Select::make('channel')->options(['log' => 'Log', 'mail' => 'Mail', 'database' => 'Database'])->default('log')->required(),
            Select::make('status')->options(['queued' => 'Queued', 'processing' => 'Processing', 'sent' => 'Sent', 'failed' => 'Failed'])->default('queued')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('tujuan')->searchable(),
            TextColumn::make('template')->searchable(),
            TextColumn::make('channel')->badge(),
            TextColumn::make('status')->badge()->sortable(),
            TextColumn::make('percobaan')->sortable(),
            TextColumn::make('sent_at')->dateTime()->sortable(),
        ])->recordActions([
            Action::make('retry')->label('Kirim Ulang')->authorize('sistem.notifikasi.retry')->visible(fn (PesanNotifikasi $record): bool => $record->status === 'failed')->action(fn (PesanNotifikasi $record): mixed => ProcessPesanNotifikasi::dispatch($record->fresh())),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePesanNotifikasis::route('/')];
    }
}
