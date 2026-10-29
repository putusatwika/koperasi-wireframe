<?php

namespace Modules\Dashboard\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\Dashboard\Services\PortalAnggotaService;
use Modules\Pembayaran\Filament\Resources\Histori\HistoriResource;
use Modules\Peminjaman\Filament\Pages\AjukanPeminjaman;
use Modules\Peminjaman\Filament\Pages\KartuPiutang;
use Modules\Peminjaman\Filament\Pages\PinjamanSaya;
use Modules\Peminjaman\Filament\Pages\SimulasiPeminjaman;
use Modules\Simpanan\Filament\Pages\SimpananSaya;

class PortalAnggota extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Portal Anggota';

    protected static ?int $navigationSort = -10;

    protected string $view = 'dashboard::filament.pages.portal-anggota';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->can('dashboard.portal_anggota.view') ?? false) && $user?->anggota !== null;
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $anggota = auth()->user()?->anggota;

        if ($anggota === null) {
            return ['data' => null, 'links' => []];
        }

        return [
            'data' => app(PortalAnggotaService::class)->ringkasan($anggota),
            'links' => $this->links(),
        ];
    }

    /** @return array<string, string> */
    private function links(): array
    {
        return [
            'simulasi' => SimulasiPeminjaman::getUrl(),
            'ajukan' => AjukanPeminjaman::getUrl(),
            'kartu_piutang' => KartuPiutang::getUrl(),
            'pinjaman_saya' => PinjamanSaya::getUrl(),
            'simpanan' => SimpananSaya::getUrl(),
            'histori' => HistoriResource::getUrl('index'),
        ];
    }
}
