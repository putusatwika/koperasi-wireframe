<?php

namespace Modules\Simpanan\Console\Commands;

use Illuminate\Console\Command;
use Modules\Simpanan\Services\SimpananService;
use Throwable;

class CheckOverdueSavings extends Command
{
    protected $signature = 'simpanan:check-overdue';

    protected $description = 'Menandai anggota pending ketika simpanan wajib menunggak tiga bulan berturut-turut.';

    public function handle(SimpananService $service): int
    {
        try {
            $count = $service->markOverdueMembersPending();
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("{$count} anggota ditandai pending.");

        return self::SUCCESS;
    }
}
