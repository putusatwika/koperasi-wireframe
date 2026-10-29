<?php

namespace App\Filament\Pages;

use App\Models\ModuleStatus;
use App\Support\ModuleStatusSynchronizer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Support\View\ComponentAttributeBag;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Module as ModuleInstance;
use Spatie\Permission\Models\Permission;
use UnitEnum;

class ModuleManager extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Manajemen Modul';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.module-manager';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $modules = ModuleStatus::query()
            ->orderByDesc('enabled')
            ->orderBy('name')
            ->get()
            ->map(function (object $moduleStatus): array {
                $module = Module::find($moduleStatus->name);

                return [
                    'name' => $moduleStatus->name,
                    'description' => $module?->getDescription(),
                    'enabled' => (bool) $moduleStatus->enabled,
                    'fileAvailable' => $module !== null,
                ];
            });

        return [
            'gridAttributes' => (new ComponentAttributeBag)
                ->grid(['default' => 1, 'md' => 2, 'xl' => 3])
                ->style(['gap: 1.5rem']),
            'installedModules' => $modules->where('enabled', true)->values(),
            'availableModules' => $modules->where('enabled', false)->values(),
        ];
    }

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshModules')
                ->label('Refresh Modul')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function (ModuleStatusSynchronizer $synchronizer): void {
                    abort_unless(static::canAccess(), 403);

                    $result = $synchronizer->synchronize();

                    Notification::make()
                        ->title('Daftar modul berhasil disinkronkan')
                        ->body("{$result['added']} ditambahkan, {$result['removed']} dihapus, {$result['total']} modul tersedia.")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function installModuleAction(): Action
    {
        return Action::make('installModule')
            ->label('Instal Modul')
            ->color('primary')
            ->modalIcon(Heroicon::OutlinedArrowDownTray)
            ->modalIconColor('primary')
            ->modalHeading(fn (array $arguments): string => "Instal modul {$this->moduleNameFromArguments($arguments)}?")
            ->modalDescription('Migrasi, data awal, dan izin milik modul akan dipasang. Modul kemudian langsung diaktifkan.')
            ->modalSubmitActionLabel('Ya, instal modul')
            ->modalCancelActionLabel('Batal')
            ->requiresConfirmation()
            ->action(fn (array $arguments, Migrator $migrator) => $this->installModule($this->moduleNameFromArguments($arguments), $migrator));
    }

    public function viewModuleAction(): Action
    {
        return Action::make('viewModule')
            ->label('Lihat Modul')
            ->modalHeading(fn (array $arguments): string => "Detail modul {$this->moduleNameFromArguments($arguments)}")
            ->modalContent(fn (array $arguments, Migrator $migrator) => view('filament.pages.module-details', [
                'module' => $this->inspectModule($this->moduleNameFromArguments($arguments), $migrator),
            ]))
            ->modalSubmitAction(function (Action $action, array $arguments, Migrator $migrator): Action|bool {
                $module = $this->inspectModule($this->moduleNameFromArguments($arguments), $migrator);

                if (! $module['installed']) {
                    return $action->label('Instal Modul')->color('primary');
                }

                if (! $module['enabled']) {
                    return $action->label('Aktifkan Modul')->color('primary');
                }

                if ($module['needsUpdate']) {
                    return $action->label('Perbaharui Modul')->color('info');
                }

                return false;
            })
            ->modalCancelActionLabel('Tutup')
            ->action(fn (array $arguments, Migrator $migrator) => $this->runModuleProcess($this->moduleNameFromArguments($arguments), $migrator));
    }

    public function activateModuleAction(): Action
    {
        return Action::make('activateModule')
            ->label('Aktifkan Modul')
            ->color('primary')
            ->modalIcon(Heroicon::OutlinedPower)
            ->modalIconColor('primary')
            ->modalHeading(fn (array $arguments): string => "Aktifkan modul {$this->moduleNameFromArguments($arguments)}?")
            ->modalDescription('Modul akan diaktifkan tanpa menjalankan ulang migration atau data awal.')
            ->modalSubmitActionLabel('Ya, aktifkan modul')
            ->modalCancelActionLabel('Batal')
            ->requiresConfirmation()
            ->action(fn (array $arguments, Migrator $migrator) => $this->activateModule($this->moduleNameFromArguments($arguments), $migrator));
    }

    public function updateModuleAction(): Action
    {
        return Action::make('updateModule')
            ->label('Perbarui Modul')
            ->color('info')
            ->modalIcon(Heroicon::OutlinedArrowPath)
            ->modalIconColor('info')
            ->modalHeading(fn (array $arguments): string => "Perbarui modul {$this->moduleNameFromArguments($arguments)}?")
            ->modalDescription('Migrasi baru, data awal, izin, dan konfigurasi modul akan disinkronkan tanpa menggandakan data yang sudah ada.')
            ->modalSubmitActionLabel('Ya, perbarui modul')
            ->modalCancelActionLabel('Batal')
            ->requiresConfirmation()
            ->action(fn (array $arguments, Migrator $migrator) => $this->updateModule($this->moduleNameFromArguments($arguments), $migrator));
    }

    public function disableModuleAction(): Action
    {
        return Action::make('disableModule')
            ->label('Nonaktifkan')
            ->color('danger')
            ->modalIcon(Heroicon::OutlinedPower)
            ->modalIconColor('danger')
            ->modalHeading(fn (array $arguments): string => "Nonaktifkan modul {$this->moduleNameFromArguments($arguments)}?")
            ->modalDescription('Data dan berkas modul tetap disimpan. Modul yang masih dibutuhkan modul lain tidak dapat dinonaktifkan.')
            ->modalSubmitActionLabel('Ya, nonaktifkan')
            ->modalCancelActionLabel('Batal')
            ->requiresConfirmation()
            ->action(fn (array $arguments) => $this->disableModule($this->moduleNameFromArguments($arguments)));
    }

    private function installModule(string $moduleName, Migrator $migrator): void
    {
        abort_unless(static::canAccess(), 403);
        $module = Module::find($moduleName);
        abort_if($module === null, 404);
        abort_if($this->moduleIsInstalled($module, $migrator, $migrator->getRepository()->getRan()), 422, 'Modul sudah terpasang.');

        $exitCode = Artisan::call('module:install', ['module' => $moduleName, '--force' => true]);
        $this->notifyFromExitCode($exitCode, "Modul {$moduleName} berhasil diinstal dan diaktifkan.");

        if ($exitCode === 0) {
            $this->redirect(static::getUrl());
        }
    }

    private function runModuleProcess(string $moduleName, Migrator $migrator): void
    {
        abort_unless(static::canAccess(), 403);

        $module = $this->inspectModule($moduleName, $migrator);

        if (! $module['installed']) {
            $this->installModule($moduleName, $migrator);

            return;
        }

        if (! $module['enabled'] && ! $module['needsUpdate']) {
            $this->activateModule($moduleName, $migrator);

            return;
        }

        $this->updateModule($moduleName, $migrator);
    }

    private function activateModule(string $moduleName, Migrator $migrator): void
    {
        abort_unless(static::canAccess(), 403);
        $module = Module::find($moduleName);
        abort_if($module === null, 404);
        abort_if(! $this->moduleIsInstalled($module, $migrator, $migrator->getRepository()->getRan()), 422, 'Modul belum terpasang.');

        $exitCode = Artisan::call('module:enable', ['module' => [$moduleName]]);
        $this->notifyFromExitCode($exitCode, "Modul {$moduleName} berhasil diaktifkan.");

        if ($exitCode === 0) {
            $this->redirect(static::getUrl());
        }
    }

    private function updateModule(string $moduleName, Migrator $migrator): void
    {
        abort_unless(static::canAccess(), 403);
        $module = Module::find($moduleName);
        abort_if($module === null, 404);
        abort_if(! $this->inspectModule($moduleName, $migrator)['installed'], 422, 'Modul belum terpasang.');

        $exitCode = Artisan::call('module:update', ['module' => $moduleName, '--force' => true]);
        $this->notifyFromExitCode($exitCode, "Modul {$moduleName} berhasil diperbarui.");

        if ($exitCode === 0) {
            $this->redirect(static::getUrl());
        }
    }

    private function disableModule(string $moduleName): void
    {
        abort_unless(static::canAccess(), 403);
        $module = Module::find($moduleName);
        abort_if($module === null, 404);

        $dependentModules = collect(Module::allEnabled())
            ->filter(fn (ModuleInstance $enabledModule): bool => in_array($moduleName, $enabledModule->get('requires', []), true))
            ->map(fn (ModuleInstance $enabledModule): string => $enabledModule->getName());

        if ($dependentModules->isNotEmpty()) {
            Notification::make()
                ->title('Modul masih diperlukan')
                ->body('Nonaktifkan terlebih dahulu: '.$dependentModules->join(', '))
                ->danger()
                ->send();

            return;
        }

        $exitCode = Artisan::call('module:disable', ['module' => [$moduleName]]);
        $this->notifyFromExitCode($exitCode, "Modul {$moduleName} berhasil dinonaktifkan. Data modul tetap disimpan.");

        if ($exitCode === 0) {
            $this->redirect(static::getUrl());
        }
    }

    /** @param array<string, mixed> $arguments */
    private function moduleNameFromArguments(array $arguments): string
    {
        return (string) ($arguments['moduleName'] ?? '');
    }

    private function notifyFromExitCode(int $exitCode, string $successMessage): void
    {
        $notification = Notification::make();

        if ($exitCode === 0) {
            $notification->title($successMessage)->success()->send();

            return;
        }

        $notification
            ->title('Operasi modul gagal')
            ->body(Artisan::output())
            ->danger()
            ->send();
    }

    /** @param list<string> $ranMigrations */
    private function moduleIsInstalled(ModuleInstance $module, Migrator $migrator, array $ranMigrations): bool
    {
        $migrationPath = $module->getPath().DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
        $migrationFiles = $migrator->getMigrationFiles([$migrationPath]);

        if ($migrationFiles === []) {
            return true;
        }

        return collect($migrationFiles)->keys()->every(
            fn (string $migration): bool => in_array($migration, $ranMigrations, true),
        );
    }

    /** @return array<string, mixed> */
    private function inspectModule(string $moduleName, Migrator $migrator): array
    {
        $module = Module::find($moduleName);
        abort_if($module === null, 404);

        $ranMigrations = $migrator->getRepository()->getRan();
        $migrationPath = $module->getPath().DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
        $migrationFiles = $migrator->getMigrationFiles([$migrationPath]);
        $pendingMigrations = collect($migrationFiles)->keys()
            ->reject(fn (string $migration): bool => in_array($migration, $ranMigrations, true))
            ->values();
        $configKey = str($moduleName)->lower()->toString();
        $configuredPermissions = config("{$configKey}.permissions", []);
        $registeredPermissions = Permission::query()
            ->whereIn('name', $configuredPermissions)
            ->pluck('name');
        $seederPath = $module->getPath().DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'seeders'.DIRECTORY_SEPARATOR.$moduleName.'DatabaseSeeder.php';
        $configPath = $module->getPath().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php';
        $moduleStatus = ModuleStatus::query()->find($moduleName);
        $enabled = $moduleStatus?->enabled ?? false;
        $appliedMigrations = collect($migrationFiles)->keys()
            ->filter(fn (string $migration): bool => in_array($migration, $ranMigrations, true));
        $missingPermissions = collect($configuredPermissions)->diff($registeredPermissions)->values();
        $currentVersion = (string) ($module->get('version') ?? '1.0.0');
        $installedVersion = $moduleStatus?->installed_version;
        $versionNeedsUpdate = $installedVersion === null || version_compare($currentVersion, $installedVersion, '>');

        return [
            'name' => $moduleName,
            'description' => $module->getDescription(),
            'enabled' => $enabled,
            'installed' => $enabled || $appliedMigrations->isNotEmpty(),
            'needsUpdate' => $versionNeedsUpdate || $pendingMigrations->isNotEmpty() || $missingPermissions->isNotEmpty(),
            'currentVersion' => $currentVersion,
            'installedVersion' => $installedVersion,
            'pendingMigrations' => $pendingMigrations->all(),
            'configuredPermissions' => count($configuredPermissions),
            'missingPermissions' => $missingPermissions->all(),
            'configAvailable' => is_file($configPath),
            'seederAvailable' => is_file($seederPath),
        ];
    }
}
