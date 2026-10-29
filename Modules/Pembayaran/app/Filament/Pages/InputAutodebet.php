<?php

namespace Modules\Pembayaran\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Pembayaran\Imports\AutodebetImport;
use Modules\Pembayaran\Services\PembayaranService;
use UnitEnum;

class InputAutodebet extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Pembayaran';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?string $navigationLabel = 'Input Autodebet';

    protected static ?int $navigationSort = 10;

    protected string $view = 'pembayaran::filament.pages.input-autodebet';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('pembayaran.autodebet.create') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                FileUpload::make('file')
                    ->label('File Autodebet (.xls/.xlsx)')
                    ->disk('local')
                    ->directory('pembayaran/autodebet')
                    ->acceptedFileTypes([
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(10240)
                    ->required(),
            ]);
    }

    public function import(PembayaranService $service): void
    {
        $file = $this->form->getState()['file'] ?? null;

        if (! is_string($file)) {
            Notification::make()->title('File autodebet wajib dipilih')->danger()->send();

            return;
        }

        try {
            $import = new AutodebetImport($service);
            Excel::import($import, Storage::disk('local')->path($file));
            $count = $import->importedCount();
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('Import autodebet gagal')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title("{$count} pembayaran autodebet berhasil diimport")
            ->success()
            ->send();

        $this->form->fill();
    }
}
