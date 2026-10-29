<?php

namespace Modules\Simpanan\Console\Commands;

use Illuminate\Console\Command;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Simpanan\Services\SimpananService;
use Throwable;

class OpenSimpananAccounts extends Command
{
    protected $signature = 'simpanan:open-accounts {--anggota= : ID anggota tertentu}';

    protected $description = 'Membuka akun simpanan dan kewajiban awal untuk anggota yang sudah ada.';

    public function handle(SimpananService $service): int
    {
        $query = Anggota::query()->whereIn('status', ['active', 'pending']);

        if (filled($this->option('anggota'))) {
            $query->whereKey($this->option('anggota'));
        }

        $processed = 0;

        foreach ($query->get() as $anggota) {
            try {
                $service->registerInitialSavings($anggota);
                $processed++;
            } catch (Throwable $exception) {
                $this->components->error("Anggota {$anggota->getKey()}: {$exception->getMessage()}");

                return self::FAILURE;
            }
        }

        $this->components->info("{$processed} anggota diproses.");

        return self::SUCCESS;
    }
}
