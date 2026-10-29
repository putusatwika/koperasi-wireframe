<?php

namespace Database\Seeders;

use App\Models\AccountType;
use Illuminate\Database\Seeder;

class AccountTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'ASSET', 'name' => 'Asset'],
            ['code' => 'LIABILITY', 'name' => 'Liability'],
            ['code' => 'EQUITY', 'name' => 'Equity'],
            ['code' => 'REVENUE', 'name' => 'Revenue'],
            ['code' => 'EXPENSE', 'name' => 'Expense'],
        ] as $accountType) {
            AccountType::query()->firstOrCreate(['code' => $accountType['code']], $accountType);
        }
    }
}
