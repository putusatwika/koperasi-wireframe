<?php

namespace App\Filament\Resources\ReportGroups\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReportGroupLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'reportGroupLines';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('account_type_id')
                    ->relationship('accountType', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('display_order')
                    ->numeric()
                    ->minValue(1),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('accountType.name')->label('Account Type'),
                TextEntry::make('display_order')->label('Display Order'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('display_order')
            ->columns([
                TextColumn::make('accountType.name')
                    ->label('Account Type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('display_order')
                    ->label('Display Order')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
