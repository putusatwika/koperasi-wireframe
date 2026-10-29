<?php

namespace App\Support\Accounting;

use App\Models\AccountType;
use App\Models\CoaAccount;
use Illuminate\Database\Eloquent\Model;

class CoaAccountEnsurer
{
    /**
     * Pastikan akun COA standar tersedia. Mengembalikan null bila tipe akun
     * belum tersedia (chart of accounts core belum di-seed).
     */
    public static function ensure(
        string $code,
        string $keyMap,
        string $name,
        string $accountTypeCode,
        string $normalBalance,
        ?string $description = null,
    ): ?CoaAccount {
        $existing = CoaAccount::query()->where('code', $code)->first();

        if ($existing !== null) {
            return $existing;
        }

        $accountType = AccountType::query()->where('code', $accountTypeCode)->first();

        if ($accountType === null) {
            return null;
        }

        return CoaAccount::query()->create([
            'code' => $code,
            'key_map' => $keyMap,
            'name' => $name,
            'account_type_id' => $accountType->getKey(),
            'normal_balance' => $normalBalance,
            'level' => 1,
            'is_postable' => true,
            'is_active' => true,
            'description' => $description,
        ]);
    }

    /**
     * Lengkapi pemetaan konfigurasi COA modul hanya bila masih kosong,
     * sehingga pengaturan yang sudah diisi pengguna tidak pernah ditimpa.
     */
    public static function fillConfig(Model $config, ?CoaAccount $account): void
    {
        if ($account === null) {
            return;
        }

        if ($config->coa_account_id === null) {
            $config->update(['coa_account_id' => $account->getKey()]);
        }
    }
}
