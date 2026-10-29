<?php

namespace Modules\Simpanan\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\Simpanan\Enums\StatusKewajiban;
use Modules\Simpanan\Models\KewajibanSimpanan;
use Modules\Simpanan\Models\TransaksiSimpanan;
use Modules\Simpanan\Services\SimpananService;
use UnitEnum;

class SimpananSaya extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Simpanan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Simpanan Saya';

    protected static ?int $navigationSort = 10;

    protected string $view = 'simpanan::filament.pages.simpanan-saya';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->can('simpanan.portal_anggota.view') ?? false) && $user?->anggota !== null;
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $anggota = auth()->user()?->anggota;

        if ($anggota === null) {
            return [
                'anggota' => null,
                'saldo' => ['total' => '0.00', 'accounts' => []],
                'kewajibans' => collect(),
                'transaksis' => collect(),
            ];
        }

        return [
            'anggota' => $anggota,
            'saldo' => app(SimpananService::class)->saldoAnggota($anggota),
            'kewajibans' => KewajibanSimpanan::query()
                ->with('jenisSimpanan')
                ->where('anggota_id', $anggota->getKey())
                ->where('status', StatusKewajiban::Pending->value)
                ->orderBy('jatuh_tempo')
                ->get(),
            'transaksis' => TransaksiSimpanan::query()
                ->with('akun.jenisSimpanan')
                ->whereHas('akun', fn ($query) => $query->where('anggota_id', $anggota->getKey()))
                ->latest('tanggal')
                ->latest('id')
                ->limit(20)
                ->get(),
        ];
    }
}
