<?php

namespace Database\Seeders;

use App\Models\AccountType;
use App\Models\ReportGroup;
use App\Models\ReportGroupLine;
use Illuminate\Database\Seeder;

class ReportGroupSeeder extends Seeder
{
    public function run(): void
    {
        $reportGroups = [
            'Neraca' => ['ASSET', 'LIABILITY', 'EQUITY'],
            'Laporan Laba Rugi' => ['REVENUE', 'EXPENSE'],
        ];

        foreach ($reportGroups as $name => $accountTypeCodes) {
            $reportGroup = ReportGroup::query()->firstOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );

            foreach ($accountTypeCodes as $index => $accountTypeCode) {
                $accountType = AccountType::query()
                    ->where('code', $accountTypeCode)
                    ->firstOrFail();

                ReportGroupLine::query()->firstOrCreate(
                    [
                        'report_group_id' => $reportGroup->getKey(),
                        'account_type_id' => $accountType->getKey(),
                    ],
                    ['display_order' => $index + 1],
                );
            }
        }
    }
}
