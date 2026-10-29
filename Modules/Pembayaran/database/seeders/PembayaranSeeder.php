<?php

namespace Modules\Pembayaran\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Pembayaran\Enums\JenisPembayaran;
use Modules\Pembayaran\Models\Pembayaran;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Enums\TujuanPeminjaman;
use Modules\Peminjaman\Models\Peminjaman;

class PembayaranSeeder extends Seeder
{
    public function run(): void
    {
        $anggota = Anggota::query()->where('member_number', 'AGT-00001')->first();

        if ($anggota === null) {
            return;
        }

        $peminjaman = Peminjaman::query()->firstOrCreate(
            ['loan_number' => 'PJM-000002'],
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
                'status' => StatusPeminjaman::Disbursed,
                'submitted_at' => now()->subMonths(3),
                'approved_at' => now()->subMonths(2),
                'disbursed_at' => now()->subMonths(2),
                'disbursed_by' => $anggota->user_id,
            ],
        );

        Pembayaran::query()->firstOrCreate(
            ['payment_number' => 'PAY-000001'],
            [
                'peminjaman_id' => $peminjaman->getKey(),
                'anggota_id' => $anggota->getKey(),
                'payment_type' => JenisPembayaran::Manual,
                'installment_number' => 1,
                'payment_date' => now()->subMonth()->toDateString(),
                'amount' => 850000,
                'principal_amount' => 750000,
                'interest_amount' => 100000,
                'penalty_amount' => 0,
                'reference_number' => 'TRANSFER-000001',
                'notes' => 'Data contoh pembayaran anggota.',
                'created_by' => $anggota->user_id,
            ],
        );

        Pembayaran::query()->firstOrCreate(
            ['payment_number' => 'PAY-000002'],
            [
                'peminjaman_id' => $peminjaman->getKey(),
                'anggota_id' => $anggota->getKey(),
                'payment_type' => JenisPembayaran::Autodebet,
                'installment_number' => 2,
                'payment_date' => now()->toDateString(),
                'amount' => 850000,
                'principal_amount' => 760000,
                'interest_amount' => 90000,
                'penalty_amount' => 0,
                'reference_number' => 'AUTODEBET-000002',
                'notes' => 'Data contoh hasil import autodebet.',
                'created_by' => $anggota->user_id,
            ],
        );
    }
}
