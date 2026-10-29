<?php

namespace Modules\Peminjaman\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Models\Peminjaman;
use UnitEnum;

class ReportPencairan extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Peminjaman';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Report Pencairan';

    protected static ?int $navigationSort = 30;

    protected string $view = 'peminjaman::filament.pages.report-pencairan';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('peminjaman.report_pencairan.view') ?? false;
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'peminjamans' => Peminjaman::query()
                ->with('anggota.user')
                ->where('status', StatusPeminjaman::Disbursed)
                ->latest('disbursed_at')
                ->get(),
        ];
    }
}
