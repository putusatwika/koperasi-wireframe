<?php

namespace Modules\Peminjaman\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class PeminjamanPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Peminjaman';
    }

    public function getId(): string
    {
        return 'peminjaman';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
