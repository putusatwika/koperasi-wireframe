<?php

namespace Modules\Peminjaman\Database\Seeders;

use App\Support\Accounting\CoaAccountEnsurer;
use Illuminate\Database\Seeder;
use Modules\Peminjaman\Enums\PeminjamanCOAConfigEnum;
use Modules\Peminjaman\Models\CoaConfig;

class PeminjamanCOASeeder extends Seeder
{
    public function run(): void
    {
        $mapping = [
            PeminjamanCOAConfigEnum::LoanReceivable->value => ['1200', 'PIUTANG_PINJAMAN', 'Piutang Pinjaman', 'ASSET', 'DEBIT'],
            PeminjamanCOAConfigEnum::DisbursementCash->value => ['1100', 'KAS_OPERASIONAL', 'Kas Operasional', 'ASSET', 'DEBIT'],
            PeminjamanCOAConfigEnum::AdminIncome->value => ['4110', 'PENDAPATAN_ADMINISTRASI', 'Pendapatan Administrasi', 'REVENUE', 'CREDIT'],
            PeminjamanCOAConfigEnum::ProvisionIncome->value => ['4120', 'PENDAPATAN_PROVISI', 'Pendapatan Provisi', 'REVENUE', 'CREDIT'],
            PeminjamanCOAConfigEnum::InterestIncome->value => ['4100', 'PENDAPATAN_BUNGA', 'Pendapatan Bunga', 'REVENUE', 'CREDIT'],
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
