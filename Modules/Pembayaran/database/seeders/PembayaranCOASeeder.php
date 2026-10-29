<?php

namespace Modules\Pembayaran\Database\Seeders;

use App\Support\Accounting\CoaAccountEnsurer;
use Illuminate\Database\Seeder;
use Modules\Pembayaran\Enums\PembayaranCOAConfigEnum;
use Modules\Pembayaran\Models\CoaConfig;

class PembayaranCOASeeder extends Seeder
{
    public function run(): void
    {
        $mapping = [
            PembayaranCOAConfigEnum::PaymentCash->value => ['1100', 'KAS_OPERASIONAL', 'Kas Operasional', 'ASSET', 'DEBIT'],
            PembayaranCOAConfigEnum::LoanReceivable->value => ['1200', 'PIUTANG_PINJAMAN', 'Piutang Pinjaman', 'ASSET', 'DEBIT'],
            PembayaranCOAConfigEnum::InterestIncome->value => ['4100', 'PENDAPATAN_BUNGA', 'Pendapatan Bunga', 'REVENUE', 'CREDIT'],
            PembayaranCOAConfigEnum::PenaltyIncome->value => ['4200', 'PENDAPATAN_DENDA', 'Pendapatan Denda', 'REVENUE', 'CREDIT'],
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
