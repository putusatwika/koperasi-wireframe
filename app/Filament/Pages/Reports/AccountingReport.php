<?php

namespace App\Filament\Pages\Reports;

use App\Models\ReportGroup;
use App\Services\Accounting\AccountingReportService;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AccountingReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Report';

    protected static ?string $navigationLabel = 'Accounting Report';

    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    /** @var array<string, mixed> */
    public array $report = [];

    protected string $view = 'filament.pages.reports.accounting-report';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
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
                Select::make('report_group_id')
                    ->label('Report Group')
                    ->options(fn (): array => ReportGroup::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->required(),
                DatePicker::make('from')
                    ->label('From')
                    ->required(),
                DatePicker::make('to')
                    ->label('To')
                    ->required(),
            ]);
    }

    public function generateReport(AccountingReportService $service): void
    {
        $data = $this->form->getState();
        $from = CarbonImmutable::parse($data['from']);
        $to = CarbonImmutable::parse($data['to']);

        abort_if($from->greaterThan($to), 422, 'The report start date must be before the end date.');

        $reportGroup = ReportGroup::query()->findOrFail($data['report_group_id']);
        $this->report = $service->generate($reportGroup, $from, $to);
    }
}
