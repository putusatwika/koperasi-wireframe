<?php

namespace Modules\Keanggotaan\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class KeanggotaanPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Keanggotaan';
    }

    public function getId(): string
    {
        return 'keanggotaan';
    }

    public function boot(Panel $panel): void {}
}
