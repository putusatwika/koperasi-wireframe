<?php

namespace Modules\Peminjaman\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Enums\TujuanPeminjaman;
use Modules\Peminjaman\Models\Peminjaman;

class PeminjamanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Peminjaman::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'loan_number' => 'PJM-'.fake()->unique()->numerify('######'),
            'anggota_id' => Anggota::factory(),
            'account_number' => fake()->numerify('##########'),
            'simulation_type' => 'default',
            'interest_type' => JenisBungaPeminjaman::Flat,
            'interest_rate' => 12,
            'term_months' => 12,
            'loan_amount' => 10000000,
            'monthly_income' => 15000000,
            'dependents_count' => 2,
            'purpose' => TujuanPeminjaman::Productive,
            'urgency' => 'medium',
            'purpose_description' => 'Modal kerja usaha.',
            'need_description' => 'Tambahan modal untuk pengembangan usaha.',
            'funds_needed_at' => today()->addDays(7),
            'repayment_source' => 'Pendapatan usaha bulanan.',
            'status' => StatusPeminjaman::Submitted,
            'submitted_at' => now(),
        ];
    }
}
