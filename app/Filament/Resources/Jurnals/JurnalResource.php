<?php

namespace App\Filament\Resources\Jurnals;

use App\Filament\Resources\Jurnals\Pages\ManageJurnals;
use App\Models\Jurnal;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class JurnalResource extends Resource
{
    protected static ?string $model = Jurnal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Core';

    protected static ?string $navigationLabel = 'Jurnal';

    protected static ?string $modelLabel = 'Jurnal';

    protected static ?string $pluralModelLabel = 'Jurnal';

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
                TextInput::make('jurnal_number')
                    ->required()
                    ->maxLength(50),
                DatePicker::make('jurnal_date')
                    ->required(),
                Select::make('coa_account_id')
                    ->relationship('coaAccount', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('position')
                    ->options(['DEBIT' => 'Debit', 'CREDIT' => 'Credit'])
                    ->required(),
                TextInput::make('amount')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('Rp')
                    ->required(),
                Textarea::make('line_description')
                    ->label('Keterangan Baris')
                    ->maxLength(1000),
                TextInput::make('source_module')
                    ->maxLength(50)
                    ->nullable(),
                TextInput::make('source_id')
                    ->numeric()
                    ->nullable(),
                Textarea::make('description')
                    ->maxLength(1000),
                Select::make('status')
                    ->options([
                        'DRAFT' => 'Draft',
                        'PENDING' => 'Pending',
                        'POSTED' => 'Posted',
                        'REVERSED' => 'Reversed',
                        'REJECTED' => 'Rejected',
                    ])
                    ->default('DRAFT')
                    ->required(),
                Select::make('reversal_of_id')
                    ->relationship('reversalOf', 'jurnal_number')
                    ->searchable()
                    ->preload(),
                DateTimePicker::make('posted_at')
                    ->disabled(),
                Select::make('posted_by')
                    ->relationship('postedBy', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('created_by')
                    ->relationship('createdBy', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('jurnal_number')->searchable()->sortable(),
                TextColumn::make('jurnal_date')->date()->sortable(),
                TextColumn::make('coaAccount.name')->label('COA')->searchable(),
                TextColumn::make('position')->badge()->sortable(),
                TextColumn::make('amount')->money('IDR')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('createdBy.name')->label('Created By')->sortable(),
                TextColumn::make('posted_at')->dateTime()->sortable(),
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
            'index' => ManageJurnals::route('/'),
        ];
    }
}
