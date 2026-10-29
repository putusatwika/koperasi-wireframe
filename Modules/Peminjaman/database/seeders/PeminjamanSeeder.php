<?php

namespace Modules\Peminjaman\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Enums\TujuanPeminjaman;
use Modules\Peminjaman\Models\Peminjaman;

class PeminjamanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $anggota = Anggota::query()->where('member_number', 'AGT-00001')->first();

        if ($anggota === null) {
            return;
        }

        Peminjaman::query()->firstOrCreate(
            ['loan_number' => 'PJM-000001'],
            [
                'anggota_id' => $anggota->getKey(),
                'account_number' => $anggota->account_number,
                'simulation_type' => 'default',
                'interest_type' => JenisBungaPeminjaman::Flat,
                'interest_rate' => 12,
                'term_months' => 12,
                'loan_amount' => 10000000,
                'purpose' => TujuanPeminjaman::Productive,
                'guarantee_required' => false,
                'penalty_rate' => 1,
                'late_fee' => 0,
                'status' => StatusPeminjaman::Submitted,
                'submitted_at' => now(),
                'statement_document' => null,
            ],
        );
    }
}
