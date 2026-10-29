<?php

namespace Modules\Dashboard\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class DashboardPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Dashboard';
    }

    public function getId(): string
    {
        return 'dashboard';
    }

    public function boot(Panel $panel): void {}
}
