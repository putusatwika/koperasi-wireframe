<?php

namespace Modules\Simpanan\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class SimpananPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Simpanan';
    }

    public function getId(): string
    {
        return 'simpanan';
    }

    public function boot(Panel $panel): void {}
}
