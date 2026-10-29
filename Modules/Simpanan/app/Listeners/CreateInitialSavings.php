<?php

namespace Modules\Simpanan\Listeners;

use Modules\Keanggotaan\Events\AnggotaTerdaftar;
use Modules\Simpanan\Services\SimpananService;

class CreateInitialSavings
{
    public function __construct(private readonly SimpananService $service) {}

    public function handle(AnggotaTerdaftar $event): void
    {
        $this->service->registerInitialSavings($event->anggota);
    }
}
