<?php

namespace Modules\Simpanan\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Keanggotaan\Events\AnggotaTerdaftar;
use Modules\Simpanan\Listeners\CreateInitialSavings;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        AnggotaTerdaftar::class => [
            CreateInitialSavings::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;
}
