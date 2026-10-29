<?php

namespace Tests\Feature;

use Database\Seeders\JurnalSeeder;
use Tests\TestCase;

class TransactionSeederTest extends TestCase
{
    public function test_transaction_seeder_was_renamed_to_jurnal_seeder(): void
    {
        $this->assertTrue(class_exists(JurnalSeeder::class));
    }
}
