<?php

namespace App\Filament\Pages\Reports;

use App\Services\Accounting\AccountingReportService;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class LaporanLabaRugi extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Report';

    protected static ?string $navigationLabel = 'Laporan Laba Rugi';

    protected static ?int $navigationSort = 11;

    public ?array $data = [];

    /** @var array<string, mixed> */
    public array $report = [];

    protected string $view = 'filament.pages.reports.laporan-laba-rugi';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('report.laba_rugi.view') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                DatePicker::make('from')
                    ->label('Dari Tanggal')
                    ->required(),
                DatePicker::make('to')
                    ->label('Sampai Tanggal')
                    ->required(),
            ]);
    }

    public function generateReport(AccountingReportService $service): void
    {
        $data = $this->form->getState();
        $from = CarbonImmutable::parse($data['from']);
        $to = CarbonImmutable::parse($data['to']);

        abort_if($from->greaterThan($to), 422, 'Tanggal awal laporan tidak boleh setelah tanggal akhir.');

        $this->report = $service->labaRugi($from, $to);
    }
}
