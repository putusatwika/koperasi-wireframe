<?php

namespace Modules\Pembayaran\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class PembayaranPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Pembayaran';
    }

    public function getId(): string
    {
        return 'pembayaran';
    }

    public function boot(Panel $panel): void {}
}
