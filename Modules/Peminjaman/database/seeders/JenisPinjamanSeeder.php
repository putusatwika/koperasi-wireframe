<?php

namespace Modules\Peminjaman\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;
use Modules\Peminjaman\Models\JenisPinjaman;

class JenisPinjamanSeeder extends Seeder
{
    public function run(): void
    {
        $jenisPinjaman = [
            [
                'kode' => 'PJ-RG',
                'nama' => 'Pinjaman Reguler',
                'metode_bunga' => JenisBungaPeminjaman::Effective,
                'persentase_bunga_per_bulan' => 1.10,
                'biaya_administrasi' => 50000,
                'biaya_provisi_persen' => 1.0,
                'denda_keterlambatan_persen' => 0.1,
                'penalti_pelunasan_persen' => 2.0,
            ],
            [
                'kode' => 'PJ-MG',
                'nama' => 'Pinjaman Multiguna (Kendaraan)',
                'metode_bunga' => JenisBungaPeminjaman::Annuity,
                'persentase_bunga_per_bulan' => 1.35,
                'biaya_administrasi' => 100000,
                'biaya_provisi_persen' => 1.5,
                'denda_keterlambatan_persen' => 0.1,
                'penalti_pelunasan_persen' => 2.5,
            ],
            [
                'kode' => 'PJ-PD',
                'nama' => 'Pinjaman Pendidikan',
                'metode_bunga' => JenisBungaPeminjaman::Effective,
                'persentase_bunga_per_bulan' => 1.00,
                'biaya_administrasi' => 25000,
                'biaya_provisi_persen' => 0.5,
                'denda_keterlambatan_persen' => 0.1,
                'penalti_pelunasan_persen' => 1.5,
            ],
            [
                'kode' => 'PJ-DR',
                'nama' => 'Pinjaman Darurat',
                'metode_bunga' => JenisBungaPeminjaman::Flat,
                'persentase_bunga_per_bulan' => 0.90,
                'biaya_administrasi' => 10000,
                'biaya_provisi_persen' => 0.5,
                'denda_keterlambatan_persen' => 0.2,
                'penalti_pelunasan_persen' => 1.0,
            ],
        ];

        foreach ($jenisPinjaman as $data) {
            JenisPinjaman::query()->firstOrCreate(
                ['kode' => $data['kode']],
                $data,
            );
        }
    }
}
