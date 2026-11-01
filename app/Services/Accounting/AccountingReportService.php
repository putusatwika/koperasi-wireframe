<?php

namespace App\Services\Accounting;

use App\Models\CoaAccount;
use App\Models\ReportGroup;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class AccountingReportService
{
    /**
     * @return array{report_group_name: string, from: string, to: string, rows: array<int, array<string, mixed>>, totals: array<string, string>}
     */
    public function generate(ReportGroup $reportGroup, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $accountTypeIds = $reportGroup->reportGroupLines()
            ->orderByRaw('display_order IS NULL')
            ->orderBy('display_order')
            ->pluck('account_type_id');

        $accounts = CoaAccount::query()
            ->with('accountType')
            ->whereIn('account_type_id', $accountTypeIds)
            ->withSum([
                'jurnals as debit_total' => fn (Builder $query): Builder => $query
                    ->where('position', 'DEBIT')
                    ->where($this->postedBetween($from, $to)),
            ], 'amount')
            ->withSum([
                'jurnals as credit_total' => fn (Builder $query): Builder => $query
                    ->where('position', 'CREDIT')
                    ->where($this->postedBetween($from, $to)),
            ], 'amount')
            ->orderBy('code')
            ->get();

        $rows = $accounts->map(function (CoaAccount $account): array {
            $debitCents = $this->toCents($account->debit_total);
            $creditCents = $this->toCents($account->credit_total);
            $balanceCents = $account->normal_balance === 'CREDIT'
                ? $creditCents - $debitCents
                : $debitCents - $creditCents;

            return [
                'code' => $account->code,
                'name' => $account->name,
                'account_type' => $account->accountType->name,
                'normal_balance' => $account->normal_balance,
                'debit' => $this->formatCents($debitCents),
                'credit' => $this->formatCents($creditCents),
                'balance' => $this->formatCents($balanceCents),
            ];
        })->all();

        $totalDebit = $accounts->sum(fn (CoaAccount $account): int => $this->toCents($account->debit_total));
        $totalCredit = $accounts->sum(fn (CoaAccount $account): int => $this->toCents($account->credit_total));
        $totalBalance = collect($rows)->sum(fn (array $row): int => $this->toCents($row['balance']));

        return [
            'report_group_name' => $reportGroup->name,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'rows' => $rows,
            'totals' => [
                'debit' => $this->formatCents($totalDebit),
                'credit' => $this->formatCents($totalCredit),
                'balance' => $this->formatCents($totalBalance),
            ],
        ];
    }

    /**
     * Profit and loss summary for a period, grouped by the two-digit parent COA.
     *
     * @return array{
     *     from: string,
     *     to: string,
     *     pendapatan: array<int, array{code: string, name: string, amount: string}>,
     *     beban: array<int, array{code: string, name: string, amount: string}>,
     *     total_pendapatan: string,
     *     total_beban: string,
     *     laba_rugi: string
     * }
     */
    public function labaRugi(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $accounts = CoaAccount::query()
            ->with('accountType')
            ->whereHas('accountType', fn (Builder $query): Builder => $query->whereIn('code', ['REVENUE', 'EXPENSE']))
            ->withSum([
                'jurnals as debit_total' => fn (Builder $query): Builder => $query
                    ->where('position', 'DEBIT')
                    ->where($this->postedBetween($from, $to)),
            ], 'amount')
            ->withSum([
                'jurnals as credit_total' => fn (Builder $query): Builder => $query
                    ->where('position', 'CREDIT')
                    ->where($this->postedBetween($from, $to)),
            ], 'amount')
            ->orderBy('code')
            ->get();

        /** @var array<string, array{type: string, amount: int, has_movement: bool}> $groups */
        $groups = [];

        foreach ($accounts as $account) {
            $code = (string) $account->code;

            if (strlen($code) < 2) {
                continue;
            }

            $prefix = substr($code, 0, 2);
            $debitCents = $this->toCents($account->debit_total);
            $creditCents = $this->toCents($account->credit_total);
            $amountCents = $account->accountType->code === 'REVENUE'
                ? $creditCents - $debitCents
                : $debitCents - $creditCents;

            if (! isset($groups[$prefix])) {
                $groups[$prefix] = [
                    'type' => $account->accountType->code,
                    'amount' => 0,
                    'has_movement' => false,
                ];
            }

            $groups[$prefix]['amount'] += $amountCents;
            $groups[$prefix]['has_movement'] = $groups[$prefix]['has_movement']
                || $debitCents !== 0
                || $creditCents !== 0;
        }

        $pendapatan = [];
        $beban = [];
        $totalPendapatanCents = 0;
        $totalBebanCents = 0;

        foreach ($groups as $prefix => $group) {
            if (! $group['has_movement']) {
                continue;
            }

            $parent = $accounts->first(
                fn (CoaAccount $account): bool => (string) $account->code === $prefix.'00'
                    && $account->accountType->code === $group['type']
            ) ?? $accounts->first(
                fn (CoaAccount $account): bool => str_starts_with((string) $account->code, $prefix)
                    && $account->accountType->code === $group['type']
            );

            $row = [
                'code' => $parent?->code ?? $prefix.'00',
                'name' => $parent?->name ?? "Akun {$prefix}",
                'amount' => $this->formatCents($group['amount']),
            ];

            if ($group['type'] === 'REVENUE') {
                $pendapatan[] = $row;
                $totalPendapatanCents += $group['amount'];

                continue;
            }

            $beban[] = $row;
            $totalBebanCents += $group['amount'];
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'pendapatan' => $pendapatan,
            'beban' => $beban,
            'total_pendapatan' => $this->formatCents($totalPendapatanCents),
            'total_beban' => $this->formatCents($totalBebanCents),
            'laba_rugi' => $this->formatCents($totalPendapatanCents - $totalBebanCents),
        ];
    }

    private function postedBetween(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return [
            ['status', '=', 'POSTED'],
            ['jurnal_date', '>=', $from->toDateString()],
            ['jurnal_date', '<=', $to->toDateString()],
        ];
    }

    private function toCents(mixed $amount): int
    {
        return Money::toCents((string) ($amount ?? '0.00'));
    }

    private function formatCents(int $amount): string
    {
        return ($amount < 0 ? '-' : '').Money::fromCents(abs($amount));
    }
}
