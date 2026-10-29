<?php

namespace Modules\Dashboard\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\Dashboard\Services\DashboardService;
use Modules\Keanggotaan\Filament\Resources\Anggotas\AnggotaResource;
use Modules\Keuangan\Filament\Pages\InputKeuangan;
use Modules\Pembayaran\Filament\Pages\BayarPinjaman;
use Modules\Pembayaran\Filament\Pages\InputAutodebet;
use Modules\Peminjaman\Filament\Pages\AjukanPeminjamanAnggota;
use Modules\Peminjaman\Filament\Pages\SimulasiPeminjaman;
use Modules\Peminjaman\Filament\Resources\SimulasiPeminjaman\SimulasiPeminjamanResource;
use UnitEnum;

class DashboardKoperasi extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Dashboard Koperasi';

    protected static ?int $navigationSort = 1;

    protected string $view = 'dashboard::filament.pages.dashboard-koperasi';

    public string $filter = 'bulan';

    public ?string $dari = null;

    public ?string $sampai = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('dashboard.dashboard.view') ?? false;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['hari', 'bulan', 'rentang'], true) ? $filter : 'bulan';
    }

    public function applyRentang(): void
    {
        $this->filter = 'rentang';
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $service = app(DashboardService::class);
        $periode = $service->periode($this->filter, $this->dari, $this->sampai);

        return [
            'shortcuts' => $this->shortcuts(),
            'kas' => $service->kas(),
            'piutang' => $service->piutang(),
            'tunggakan' => $service->tunggakan(),
            'denda' => $service->dendaBelumDicairkan(),
            'keuangan' => $service->keuangan($periode['dari'], $periode['sampai']),
            'periode' => $periode,
            'filter' => $this->filter,
        ];
    }

    /**
     * @return array<int, array{label: string, url: string, icon: string, color: string}>
     */
    protected function shortcuts(): array
    {
        $shortcuts = [];

        if (AnggotaResource::canCreate()) {
            $shortcuts[] = [
                'label' => 'Tambah Anggota',
                'url' => AnggotaResource::getUrl('create'),
                'icon' => 'heroicon-o-user-plus',
                'color' => 'primary',
            ];
        }

        if (AjukanPeminjamanAnggota::canAccess()) {
            $shortcuts[] = [
                'label' => 'Pengajuan Pinjaman Anggota',
                'url' => AjukanPeminjamanAnggota::getUrl(),
                'icon' => 'heroicon-o-document-plus',
                'color' => 'gray',
            ];
        }

        if (SimulasiPeminjaman::canAccess()) {
            $shortcuts[] = [
                'label' => 'Simulasi Kredit',
                'url' => SimulasiPeminjaman::getUrl(),
                'icon' => 'heroicon-o-calculator',
                'color' => 'gray',
            ];
        }

        if (SimulasiPeminjamanResource::canViewAny()) {
            $shortcuts[] = [
                'label' => 'Simulasi Pinjaman',
                'url' => SimulasiPeminjamanResource::getUrl('index'),
                'icon' => 'heroicon-o-calculator',
                'color' => 'gray',
            ];
        }

        if (BayarPinjaman::canAccess()) {
            $shortcuts[] = [
                'label' => 'Input Pembayaran',
                'url' => BayarPinjaman::getUrl(),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'gray',
            ];
        }

        if (InputAutodebet::canAccess()) {
            $shortcuts[] = [
                'label' => 'Input Autodebet',
                'url' => InputAutodebet::getUrl(),
                'icon' => 'heroicon-o-arrow-up-tray',
                'color' => 'gray',
            ];
        }

        if (InputKeuangan::canAccess()) {
            $shortcuts[] = [
                'label' => 'Catat Keuangan',
                'url' => InputKeuangan::getUrl(),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'gray',
            ];
        }

        return $shortcuts;
    }
}
