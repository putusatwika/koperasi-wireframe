<?php

namespace Modules\ExampleModule\Services;

use App\Services\Accounting\JurnalService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\ExampleModule\Enums\ExampleModuleCOAConfigEnum;
use Modules\ExampleModule\Models\CoaConfig;
use Modules\ExampleModule\Models\ExampleData;
use RuntimeException;

class ExampleDataService
{
    public function __construct(private JurnalService $jurnalService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ExampleData
    {
        return DB::transaction(function () use ($data): ExampleData {
            $userId = auth()->id();

            if (blank($userId)) {
                throw new RuntimeException('An authenticated user is required to create ExampleData.');
            }

            $amount = $data['amount'] ?? null;

            if (! is_numeric($amount) || (float) $amount <= 0) {
                throw new InvalidArgumentException('The ExampleData amount must contain a positive amount.');
            }

            $coaAccountId = CoaConfig::query()
                ->where('key', ExampleModuleCOAConfigEnum::ExampleDataAmount->value)
                ->value('coa_account_id');

            if (blank($coaAccountId)) {
                throw new RuntimeException('The ExampleData amount COA configuration is required.');
            }

            $exampleData = ExampleData::query()->create($data);

            $this->jurnalService->create([
                'jurnal_number' => "EXAMPLE-DATA-{$exampleData->getKey()}",
                'jurnal_date' => now()->toDateString(),
                'source_module' => 'ExampleModule',
                'source_id' => $exampleData->getKey(),
                'description' => "Amount ExampleData: {$exampleData->name}",
                'status' => 'POSTED',
                'created_by' => $userId,
                'coa_account_id' => $coaAccountId,
                'position' => 'DEBIT',
                'amount' => $exampleData->amount,
                'posted_at' => now(),
                'posted_by' => $userId,
            ]);

            return $exampleData;
        });
    }
}
