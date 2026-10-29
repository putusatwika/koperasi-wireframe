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

class KartuPiutang extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Peminjaman';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Kartu Piutang';

    protected static ?int $navigationSort = 15;

    protected string $view = 'peminjaman::filament.pages.kartu-piutang';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('peminjaman.kartu_piutang.view') ?? false;
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $anggota = auth()->user()?->anggota;

        if ($anggota === null) {
            return ['loans' => collect()];
        }

        $service = app(PeminjamanService::class);

        $loans = Peminjaman::query()
            ->with('jadwalAngsurans')
            ->where('anggota_id', $anggota->getKey())
            ->whereIn('status', [StatusPeminjaman::Disbursed, StatusPeminjaman::Lunas])
            ->latest('disbursed_at')
            ->get()
            ->map(function (Peminjaman $peminjaman) use ($service): array {
                $schedule = $peminjaman->jadwalAngsurans->sortBy('angsuran_ke');
                $next = $schedule->first(fn ($row): bool => $row->status !== StatusJadwalAngsuran::Lunas);
                $sisaPokok = $schedule->sum(fn ($row): float => (float) $row->pokok - (float) $row->dibayar_pokok);
                $tunggakan = $schedule->filter(
                    fn ($row): bool => $row->status !== StatusJadwalAngsuran::Lunas && $row->jatuh_tempo->isPast(),
                );
                $rows = $schedule->map(fn ($row): array => [
                    'installment' => $row,
                    'denda' => $service->dendaBelumDibayar($row),
                ]);

                return [
                    'peminjaman' => $peminjaman,
                    'schedule' => $rows,
                    'next' => $next,
                    'sisa_pokok' => $sisaPokok,
                    'tunggakan' => $tunggakan->count(),
                    'denda' => round((float) collect($rows)->sum('denda'), 2),
                    'penalti' => $service->hitungPenaltiPelunasan($peminjaman),
                ];
            });

        return ['loans' => $loans];
    }
}
