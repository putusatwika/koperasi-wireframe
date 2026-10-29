<?php

namespace Modules\Keanggotaan\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Keanggotaan\Models\Anggota;

class AnggotaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Anggota::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'member_number' => 'AGT-'.fake()->unique()->numerify('#####'),
            'account_number' => fake()->unique()->numerify('##########'),
            'identity_number' => fake()->unique()->numerify('################'),
            'phone' => fake()->numerify('08##########'),
            'address' => fake()->address(),
            'joined_at' => now()->toDateString(),
            'status' => 'active',
        ];
    }
}
