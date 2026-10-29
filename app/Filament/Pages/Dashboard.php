<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Modules\Dashboard\Filament\Pages\DashboardKoperasi;
use Modules\Dashboard\Filament\Pages\PortalAnggota;

class Dashboard extends BaseDashboard
{
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return ! $user->hasRole('anggota') && ! $user->can('dashboard.dashboard.view');
    }

    public function mount(): void
    {
        $user = auth()->user();

        if ($user?->hasRole('anggota')) {
            $this->redirect(PortalAnggota::getUrl());

            return;
        }

        if ($user?->can('dashboard.dashboard.view')) {
            $this->redirect(DashboardKoperasi::getUrl());
        }
    }
}
