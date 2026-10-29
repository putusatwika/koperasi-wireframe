<?php

namespace Modules\Simpanan\Database\Seeders;

use App\Support\Accounting\CoaAccountEnsurer;
use Illuminate\Database\Seeder;
use Modules\Simpanan\Enums\SimpananCOAConfigEnum;
use Modules\Simpanan\Models\CoaConfig;

class SimpananCOASeeder extends Seeder
{
    public function run(): void
    {
        $mapping = [
            SimpananCOAConfigEnum::SavingsCash->value => ['1100', 'KAS_OPERASIONAL', 'Kas Operasional', 'ASSET', 'DEBIT'],
            SimpananCOAConfigEnum::SavingsLiability->value => ['2200', 'SIMPANAN_ANGGOTA', 'Simpanan Anggota', 'LIABILITY', 'CREDIT'],
            SimpananCOAConfigEnum::SavingsInterestExpense->value => ['5000', 'BEBAN_OPERASIONAL', 'Beban Operasional', 'EXPENSE', 'DEBIT'],
        ];

        foreach ($mapping as $key => [$code, $keyMap, $name, $typeCode, $balance]) {
            $account = CoaAccountEnsurer::ensure($code, $keyMap, $name, $typeCode, $balance);

            if ($account === null) {
                continue;
            }

            $config = CoaConfig::query()->firstOrCreate(
                ['key' => $key],
                ['coa_account_id' => $account->getKey()],
            );

            CoaAccountEnsurer::fillConfig($config, $account);
        }
    }
}
