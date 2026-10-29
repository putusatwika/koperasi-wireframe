<?php

namespace Modules\Peminjaman\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;
use Modules\Peminjaman\Enums\TujuanPeminjaman;
use Modules\Peminjaman\Filament\Resources\PengajuanPeminjamans\Pages\EditPengajuanPeminjaman;
use Modules\Peminjaman\Services\PeminjamanService;
use RuntimeException;
use UnitEnum;

class SimulasiPeminjaman extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Peminjaman';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?string $navigationLabel = 'Simulasi Kredit';

    protected static ?int $navigationSort = 10;

    protected string $view = 'peminjaman::filament.pages.simulasi-peminjaman';

    public ?array $data = [];

    public ?array $result = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! ($user?->can('peminjaman.simulasi.view') ?? false)) {
            return false;
        }

        $anggota = $user?->anggota;

        return $anggota === null || $anggota->status === 'active';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('loan_amount')
                    ->label('Jumlah Pinjaman')
                    ->numeric()
                    ->minValue(1)
                    ->prefix('Rp')
                    ->required(),
                Select::make('interest_type')
                    ->label('Jenis Bunga')
                    ->options([
                        JenisBungaPeminjaman::Flat->value => 'Flat',
                        JenisBungaPeminjaman::Effective->value => 'Efektif',
                        JenisBungaPeminjaman::Annuity->value => 'Anuitas',
                    ])
                    ->required(),
                TextInput::make('interest_rate')
                    ->label('Bunga per Bulan (%)')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('term_months')
                    ->label('Tenor (Bulan)')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->required(),
                Select::make('purpose')
                    ->label('Peruntukan')
                    ->options([
                        TujuanPeminjaman::Consumption->value => 'Konsumsi',
                        TujuanPeminjaman::Vehicle->value => 'Beli Kendaraan',
                        TujuanPeminjaman::Education->value => 'Pendidikan',
                        TujuanPeminjaman::Productive->value => 'Produktif/Usaha',
                    ])
                    ->required(),
            ]);
    }

    public function simulate(PeminjamanService $service): void
    {
        try {
            $data = $this->form->getState();
            $data['interest_rate'] = filled($data['interest_rate'] ?? null)
                ? (float) $data['interest_rate'] * 12
                : 0;

            $this->result = $service->simulate($data);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            Notification::make()
                ->title('Simulasi tidak dapat diproses')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title('Simulasi berhasil dihitung')
            ->success()
            ->send();
    }

    public function continueApplication(PeminjamanService $service): void
    {
        try {
            $peminjaman = $service->createDraft($this->form->getState());
        } catch (InvalidArgumentException|RuntimeException $exception) {
            Notification::make()
                ->title('Pengajuan tidak dapat diproses')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $this->redirect(EditPengajuanPeminjaman::getUrl(['record' => $peminjaman]));
    }
}
