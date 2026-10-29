<?php

namespace Modules\Simpanan\Console\Commands;

use Illuminate\Console\Command;
use Modules\Simpanan\Services\SimpananService;
use Throwable;

class GenerateKewajibanSimpanan extends Command
{
    protected $signature = 'simpanan:generate-kewajiban {periode? : Periode dalam format YYYY-MM}';

    protected $description = 'Membuat kewajiban simpanan wajib bulanan untuk periode tertentu.';

    public function handle(SimpananService $service): int
    {
        $periode = $this->argument('periode');

        try {
            $count = $service->generateMonthlyObligations(is_string($periode) && $periode !== '' ? $periode : null);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("{$count} kewajiban simpanan dibuat.");

        return self::SUCCESS;
    }
}
