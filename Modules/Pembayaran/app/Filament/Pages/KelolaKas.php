<?php

namespace Modules\Pembayaran\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Pembayaran\Services\CashService;
use Throwable;
use UnitEnum;

class KelolaKas extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Pembayaran';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Kas';

    protected static ?int $navigationSort = 5;

    protected string $view = 'pembayaran::filament.pages.kelola-kas';

    public ?array $data = [];

    /** @var array<string, mixed> */
    public array $summary = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('pembayaran.kas.viewAny') ?? false;
    }

    public function mount(CashService $service): void
    {
        $setting = $service->setting();

        $this->form->fill([
            'opening_balance' => (float) $setting->opening_balance,
            'opening_date' => $setting->opening_date,
        ]);

        $this->summary = $service->summary();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('opening_balance')
                    ->label('Jumlah Kas di Awal')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp')
                    ->required(),
                DatePicker::make('opening_date')->label('Tanggal Kas di Awal'),
            ]);
    }

    public function save(CashService $service): void
    {
        try {
            $service->setOpeningBalance($this->form->getState());
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Gagal menyimpan kas di awal')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $this->summary = $service->summary();

        Notification::make()->title('Kas di awal berhasil disimpan')->success()->send();
    }
}
