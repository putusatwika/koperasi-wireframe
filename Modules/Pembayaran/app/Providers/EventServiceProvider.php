<?php

namespace Modules\Pembayaran\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Pembayaran\Listeners\RecordFirstInstallmentDeduction;
use Modules\Peminjaman\Events\PinjamanDicairkan;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        PinjamanDicairkan::class => [
            RecordFirstInstallmentDeduction::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;
}
