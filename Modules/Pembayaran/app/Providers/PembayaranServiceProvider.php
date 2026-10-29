<?php

namespace Modules\Pembayaran\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class PembayaranServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Pembayaran';

    protected string $nameLower = 'pembayaran';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
