<?php

namespace Modules\Peminjaman\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\Peminjaman\Enums\StatusJadwalAngsuran;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Models\Peminjaman;
use Modules\Peminjaman\Services\PeminjamanService;
use UnitEnum;

class PinjamanSaya extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Peminjaman';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Pinjaman Saya';

    protected static ?int $navigationSort = 14;

    protected string $view = 'peminjaman::filament.pages.pinjaman-saya';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->can('peminjaman.kartu_piutang.view') ?? false) && $user?->anggota !== null;
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $anggota = auth()->user()?->anggota;

        if ($anggota === null) {
            return ['aktif' => collect(), 'ditutup' => collect()];
        }

        $service = app(PeminjamanService::class);

        $loans = Peminjaman::query()
            ->with('jadwalAngsurans')
            ->where('anggota_id', $anggota->getKey())
            ->latest('id')
            ->get()
            ->map(function (Peminjaman $peminjaman) use ($service): array {
                $schedule = $peminjaman->jadwalAngsurans->sortBy('angsuran_ke');
                $next = $schedule->first(fn ($row): bool => $row->status !== StatusJadwalAngsuran::Lunas);

                return [
                    'peminjaman' => $peminjaman,
                    'sisa_pokok' => $schedule->isEmpty() ? null : round((float) $schedule->sum(
                        fn ($row): float => (float) $row->pokok - (float) $row->dibayar_pokok,
                    ), 2),
                    'next' => $next,
                    'denda' => $schedule->isEmpty() ? 0.0 : round((float) $schedule->sum(
                        fn ($row): float => $service->dendaBelumDibayar($row),
                    ), 2),
                ];
            });

        $statusAktif = [
            StatusPeminjaman::Submitted,
            StatusPeminjaman::InReview,
            StatusPeminjaman::ReadyForApproval,
            StatusPeminjaman::Approved,
            StatusPeminjaman::Disbursed,
        ];

        [$aktif, $ditutup] = $loans->partition(
            fn (array $row): bool => in_array($row['peminjaman']->status, $statusAktif, true),
        );

        return [
            'aktif' => $aktif->values(),
            'ditutup' => $ditutup->values(),
        ];
    }
}
