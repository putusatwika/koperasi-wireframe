<?php

namespace Database\Seeders;

use App\Models\AccountType;
use App\Models\CoaAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class CoaAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['code' => '1000', 'name' => 'Kas dan Setara Kas', 'account_type_code' => 'ASSET', 'normal_balance' => 'DEBIT', 'level' => 1, 'is_postable' => false],
            ['code' => '1100', 'name' => 'Kas Operasional', 'account_type_code' => 'ASSET', 'normal_balance' => 'DEBIT', 'parent_code' => '1000', 'level' => 2],
            ['code' => '2000', 'name' => 'Liabilitas', 'account_type_code' => 'LIABILITY', 'normal_balance' => 'CREDIT', 'level' => 1, 'is_postable' => false],
            ['code' => '2100', 'name' => 'Utang Operasional', 'account_type_code' => 'LIABILITY', 'normal_balance' => 'CREDIT', 'parent_code' => '2000', 'level' => 2],
            ['code' => '3000', 'name' => 'Ekuitas', 'account_type_code' => 'EQUITY', 'normal_balance' => 'CREDIT', 'level' => 1, 'is_postable' => false],
            ['code' => '3100', 'name' => 'Modal Koperasi', 'account_type_code' => 'EQUITY', 'normal_balance' => 'CREDIT', 'parent_code' => '3000', 'level' => 2],
            ['code' => '4000', 'name' => 'Pendapatan Operasional', 'account_type_code' => 'REVENUE', 'normal_balance' => 'CREDIT', 'level' => 1],
            ['code' => '5000', 'name' => 'Beban Operasional', 'account_type_code' => 'EXPENSE', 'normal_balance' => 'DEBIT', 'level' => 1],
        ];

        foreach ($accounts as $account) {
            $accountType = AccountType::query()->where('code', $account['account_type_code'])->firstOrFail();
            $parent = isset($account['parent_code'])
                ? CoaAccount::query()->where('code', $account['parent_code'])->firstOrFail()
                : null;

            CoaAccount::query()->firstOrCreate(
                ['code' => $account['code']],
                [
                    ...Arr::except($account, ['account_type_code', 'parent_code']),
                    'key_map' => strtoupper((string) preg_replace('/\s+/', '_', trim($account['name']))),
                    'account_type_id' => $accountType->getKey(),
                    'parent_id' => $parent?->getKey(),
                ],
            );
        }
    }
}
