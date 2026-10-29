<?php

namespace Modules\Simpanan\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Simpanan\Console\Commands\CheckOverdueSavings;
use Modules\Simpanan\Console\Commands\GenerateKewajibanSimpanan;
use Modules\Simpanan\Console\Commands\OpenSimpananAccounts;
use Modules\Simpanan\Console\Commands\ProcessSimpananInterest;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SimpananServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Simpanan';

    protected string $nameLower = 'simpanan';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    protected array $commands = [
        CheckOverdueSavings::class,
        GenerateKewajibanSimpanan::class,
        OpenSimpananAccounts::class,
        ProcessSimpananInterest::class,
    ];

    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->command('simpanan:generate-kewajiban')->monthlyOn(1, '00:10');
        $schedule->command('simpanan:process-interest')->monthlyOn(1, '00:20');
        $schedule->command('simpanan:check-overdue')->dailyAt('01:00');
    }
}
