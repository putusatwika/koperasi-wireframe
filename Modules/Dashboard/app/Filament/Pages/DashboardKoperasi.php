<?php

namespace Modules\Dashboard\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\Dashboard\Services\DashboardService;
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
            'kas' => $service->kas(),
            'piutang' => $service->piutang(),
            'tunggakan' => $service->tunggakan(),
            'denda' => $service->dendaBelumDicairkan(),
            'keuangan' => $service->keuangan($periode['dari'], $periode['sampai']),
            'periode' => $periode,
            'filter' => $this->filter,
        ];
    }
}
