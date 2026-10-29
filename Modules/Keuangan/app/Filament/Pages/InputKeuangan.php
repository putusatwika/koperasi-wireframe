<?php

namespace Modules\Keuangan\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Keuangan\Models\KategoriKeuangan;
use Modules\Keuangan\Services\KeuanganService;
use Throwable;
use UnitEnum;

class InputKeuangan extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Keuangan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Catat Keuangan';

    protected static ?int $navigationSort = 10;

    protected string $view = 'keuangan::filament.pages.input-keuangan';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('keuangan.transaksi.create') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        $kategoriOptions = KategoriKeuangan::query()
            ->where('is_active', true)
            ->orderBy('tipe')
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn (KategoriKeuangan $kategori): array => [
                $kategori->getKey() => "[{$kategori->tipe->name}] {$kategori->nama}",
            ])
            ->all();

        return $schema
            ->statePath('data')
            ->components([
                Select::make('kategori_id')
                    ->label('Kategori')
                    ->options($kategoriOptions)
                    ->searchable()
                    ->required(),
                DatePicker::make('tanggal')->label('Tanggal')->default(now())->required(),
                TextInput::make('nominal')->label('Nominal')->numeric()->minValue(1)->prefix('Rp')->required(),
                TextInput::make('referensi')->label('Nomor Referensi')->maxLength(100),
                FileUpload::make('lampiran')
                    ->label('Lampiran Bukti (opsional)')
                    ->disk('public')
                    ->directory('keuangan/lampiran')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(10240),
                Textarea::make('keterangan')->label('Keterangan')->maxLength(1000)->columnSpanFull(),
            ]);
    }

    public function submit(KeuanganService $service): void
    {
        try {
            $service->createTransaksi($this->form->getState());
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Transaksi keuangan gagal')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()->title('Transaksi keuangan berhasil dicatat')->success()->send();
        $this->form->fill();
    }
}
