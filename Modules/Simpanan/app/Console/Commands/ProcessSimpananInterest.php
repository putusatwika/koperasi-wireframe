<?php

namespace Modules\Simpanan\Console\Commands;

use Illuminate\Console\Command;
use Modules\Simpanan\Services\SimpananService;
use Throwable;

class ProcessSimpananInterest extends Command
{
    protected $signature = 'simpanan:process-interest {periode? : Periode dalam format YYYY-MM}';

    protected $description = 'Memposting bunga simpanan yang berbunga untuk periode tertentu.';

    public function handle(SimpananService $service): int
    {
        $periode = $this->argument('periode');

        try {
            $periode = is_string($periode) && $periode !== ''
                ? $periode
                : now()->subMonthNoOverflow()->format('Y-m');

            $count = $service->prosesBunga($periode);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("{$count} transaksi bunga simpanan diposting.");

        return self::SUCCESS;
    }
}
