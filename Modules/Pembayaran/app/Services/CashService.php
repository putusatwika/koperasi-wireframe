<?php

namespace Modules\Pembayaran\Services;

use App\Models\Jurnal;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Pembayaran\Enums\PembayaranCOAConfigEnum;
use Modules\Pembayaran\Models\CashSetting;
use Modules\Pembayaran\Models\CoaConfig;
use RuntimeException;

class CashService
{
    /**
     * Persist the cooperative opening cash balance.
     *
     * @param  array<string, mixed>  $data
     */
    public function setOpeningBalance(array $data): CashSetting
    {
        abort_unless(auth()->user()?->can('pembayaran.kas.update') ?? false, 403);

        return DB::transaction(function () use ($data): CashSetting {
            $userId = auth()->id();

            if (blank($userId)) {
                throw new RuntimeException('Pengguna terautentikasi diperlukan untuk mengatur kas awal.');
            }

            $amount = $this->nonNegativeAmount($data['opening_balance'] ?? null);
            $setting = CashSetting::query()->first() ?? new CashSetting;
            $setting->fill([
                'opening_balance' => $amount,
                'opening_date' => $data['opening_date'] ?? null,
                'created_by' => $setting->exists ? $setting->created_by : $userId,
                'updated_by' => $userId,
            ]);
            $setting->save();

            return $setting->refresh();
        });
    }

    public function setting(): CashSetting
    {
        return CashSetting::query()->first() ?? new CashSetting;
    }

    /**
     * Summarize the opening cash plus every posted cash movement.
     *
     * @return array{opening_balance: string, total_in: string, total_out: string, remaining: string, cash_account_configured: bool}
     */
    public function summary(): array
    {
        $openingCents = Money::toCents((string) $this->setting()->opening_balance);
        $cashAccountId = $this->cashAccountId();

        $totalInCents = $cashAccountId === null ? 0 : $this->journalTotal($cashAccountId, 'DEBIT');
        $totalOutCents = $cashAccountId === null ? 0 : $this->journalTotal($cashAccountId, 'CREDIT');
        $remainingCents = $openingCents + $totalInCents - $totalOutCents;

        return [
            'opening_balance' => Money::fromCents($openingCents),
            'total_in' => Money::fromCents($totalInCents),
            'total_out' => Money::fromCents($totalOutCents),
            'remaining' => $remainingCents < 0
                ? '-'.Money::fromCents(abs($remainingCents))
                : Money::fromCents($remainingCents),
            'cash_account_configured' => $cashAccountId !== null,
        ];
    }

    public function cashAccountId(): ?int
    {
        $accountId = CoaConfig::query()
            ->where('key', PembayaranCOAConfigEnum::PaymentCash->value)
            ->value('coa_account_id');

        return blank($accountId) ? null : (int) $accountId;
    }

    private function journalTotal(int $coaAccountId, string $position): int
    {
        $total = (float) Jurnal::query()
            ->where('coa_account_id', $coaAccountId)
            ->where('position', $position)
            ->where('status', 'POSTED')
            ->sum('amount');

        return (int) round($total * 100);
    }

    private function nonNegativeAmount(mixed $value): float
    {
        if (! is_numeric($value) || (float) $value < 0) {
            throw new InvalidArgumentException('Jumlah kas awal tidak boleh negatif.');
        }

        return round((float) $value, 2);
    }
}
