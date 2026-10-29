<?php

namespace Modules\Keuangan\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class KeuanganPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Keuangan';
    }

    public function getId(): string
    {
        return 'keuangan';
    }

    public function boot(Panel $panel): void {}
}
