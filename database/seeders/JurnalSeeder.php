<?php

namespace Database\Seeders;

use App\Models\CoaAccount;
use App\Models\Jurnal;
use App\Models\User;
use App\Services\Accounting\JurnalService;
use Illuminate\Database\Seeder;

class JurnalSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'super@koperasi.id')->firstOrFail();
        $jurnalService = app(JurnalService::class);
        $accounts = CoaAccount::query()->pluck('id', 'code');

        foreach ([
            [
                'jurnal_number' => 'JRN-SEED-001',
                'jurnal_date' => '2026-09-15',
                'debit_account' => '1100',
                'credit_account' => '4000',
                'description' => 'Contoh penerimaan kas untuk laporan.',
                'amount' => '1500000.00',
            ],
            [
                'jurnal_number' => 'JRN-SEED-002',
                'jurnal_date' => '2026-09-18',
                'debit_account' => '5000',
                'credit_account' => '1100',
                'description' => 'Contoh pengeluaran kas untuk laporan.',
                'amount' => '350000.00',
            ],
            [
                'jurnal_number' => 'JRN-SEED-003',
                'jurnal_date' => '2026-09-22',
                'debit_account' => '1100',
                'credit_account' => '4000',
                'description' => 'Contoh penerimaan kas kedua untuk laporan.',
                'amount' => '750000.00',
            ],
            [
                'jurnal_number' => 'JRN-SEED-004',
                'jurnal_date' => '2026-09-24',
                'debit_account' => '1100',
                'credit_account' => '2100',
                'description' => 'Contoh penerimaan kas dari kewajiban.',
                'amount' => '500000.00',
            ],
            [
                'jurnal_number' => 'JRN-SEED-005',
                'jurnal_date' => '2026-09-26',
                'debit_account' => '1100',
                'credit_account' => '3100',
                'description' => 'Contoh setoran modal koperasi.',
                'amount' => '1000000.00',
            ],
        ] as $data) {
            if (Jurnal::query()->where('jurnal_number', $data['jurnal_number'])->exists()) {
                continue;
            }

            $jurnalData = [
                'jurnal_number' => $data['jurnal_number'],
                'jurnal_date' => $data['jurnal_date'],
                'description' => $data['description'],
                'status' => 'DRAFT',
                'created_by' => $user->getKey(),
            ];

            foreach ([
                ['coa_account_id' => $accounts[$data['debit_account']], 'position' => 'DEBIT'],
                ['coa_account_id' => $accounts[$data['credit_account']], 'position' => 'CREDIT'],
            ] as $line) {
                $jurnalService->create([
                    ...$jurnalData,
                    ...$line,
                    'amount' => $data['amount'],
                    'status' => 'POSTED',
                    'posted_at' => now(),
                    'posted_by' => $user->getKey(),
                ]);
            }
        }
    }
}
