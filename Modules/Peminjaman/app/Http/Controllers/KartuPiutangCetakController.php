<?php

namespace Modules\Peminjaman\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Peminjaman\Models\Peminjaman;
use Modules\Peminjaman\Services\PeminjamanService;

class KartuPiutangCetakController
{
    public function __invoke(Request $request, Peminjaman $peminjaman): View
    {
        $user = $request->user();
        $anggota = $user?->anggota;

        $bolehLihat = ($anggota !== null && (int) $peminjaman->anggota_id === (int) $anggota->getKey())
            || ($user?->can('peminjaman.daftar_peminjaman.view') ?? false);

        abort_unless($bolehLihat, 403);

        $service = app(PeminjamanService::class);
        $schedule = $peminjaman->jadwalAngsurans->sortBy('angsuran_ke');

        $rows = $schedule->map(fn ($row): array => [
            'installment' => $row,
            'denda' => $service->dendaBelumDibayar($row),
        ]);

        return view('peminjaman::kartu-piutang-cetak', [
            'peminjaman' => $peminjaman->loadMissing('anggota.user', 'jenisPinjaman'),
            'rows' => $rows,
            'sisaPokok' => round((float) $schedule->sum(
                fn ($row): float => (float) $row->pokok - (float) $row->dibayar_pokok,
            ), 2),
            'denda' => round((float) collect($rows)->sum('denda'), 2),
            'dicetakOleh' => $user?->name,
        ]);
    }
}
