<?php

namespace Modules\Simpanan\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Simpanan\Enums\KategoriSimpanan;
use Modules\Simpanan\Enums\PeriodikSimpanan;
use Modules\Simpanan\Models\JenisSimpanan;

class JenisSimpananSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'kode' => 'POKOK',
                'nama' => 'Simpanan Pokok',
                'kategori' => KategoriSimpanan::Pokok,
                'nominal_default' => 100000,
                'wajib' => true,
                'periodik' => PeriodikSimpanan::None,
                'berbunga' => false,
                'deskripsi' => 'Dibayarkan sekali saat mendaftar menjadi anggota.',
            ],
            [
                'kode' => 'WAJIB',
                'nama' => 'Simpanan Wajib',
                'kategori' => KategoriSimpanan::Wajib,
                'nominal_default' => 50000,
                'wajib' => true,
                'periodik' => PeriodikSimpanan::Monthly,
                'berbunga' => false,
                'tanggal_jatuh_tempo' => 1,
                'deskripsi' => 'Dibayarkan setiap bulan, pertama kali saat mendaftar.',
            ],
            [
                'kode' => 'SUKARELA',
                'nama' => 'Simpanan Sukarela',
                'kategori' => KategoriSimpanan::Sukarela,
                'nominal_default' => 0,
                'wajib' => false,
                'periodik' => PeriodikSimpanan::None,
                'berbunga' => false,
                'deskripsi' => 'Setoran dan penarikan sukarela melalui bendahara.',
            ],
            [
                'kode' => 'BERJANGKA',
                'nama' => 'Simpanan Berjangka',
                'kategori' => KategoriSimpanan::Berjangka,
                'nominal_default' => 0,
                'wajib' => false,
                'periodik' => PeriodikSimpanan::None,
                'berbunga' => true,
                'bunga_per_bulan' => 0.5,
                'periode_bunga_bulan' => 1,
                'tenor_bulan' => 12,
                'deskripsi' => 'Simpanan berjangka dengan bunga bulanan.',
            ],
            [
                'kode' => 'TAHAYA',
                'nama' => 'Tahaya (Berjangka Hari Raya)',
                'kategori' => KategoriSimpanan::Berjangka,
                'nominal_default' => 0,
                'wajib' => false,
                'periodik' => PeriodikSimpanan::None,
                'berbunga' => true,
                'bunga_per_bulan' => 0.5,
                'periode_bunga_bulan' => 1,
                'tenor_bulan' => 12,
                'deskripsi' => 'Produk berjangka hari raya; tenor dan bunga dapat diubah bendahara.',
            ],
        ];

        foreach ($products as $product) {
            JenisSimpanan::query()->firstOrCreate(
                ['kode' => $product['kode']],
                $product,
            );
        }
    }
}
