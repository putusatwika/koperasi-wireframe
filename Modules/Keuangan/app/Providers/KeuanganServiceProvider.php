<?php

namespace Modules\Keuangan\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class KeuanganServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Keuangan';

    protected string $nameLower = 'keuangan';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
