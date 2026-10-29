<?php

namespace Modules\Keuangan\Database\Seeders;

use App\Support\Accounting\CoaAccountEnsurer;
use Illuminate\Database\Seeder;
use Modules\Keuangan\Enums\KeuanganCOAConfigEnum;
use Modules\Keuangan\Models\CoaConfig;

class KeuanganCOASeeder extends Seeder
{
    public function run(): void
    {
        $mapping = [
            KeuanganCOAConfigEnum::Kas->value => ['1100', 'KAS_OPERASIONAL', 'Kas Operasional', 'ASSET', 'DEBIT'],
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
