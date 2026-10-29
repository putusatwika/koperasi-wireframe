<?php

namespace App\Console\Commands;

use App\Models\ModuleStatus;
use Illuminate\Console\Application as ArtisanApplication;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;
use ReflectionClass;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

#[Signature('module:install {module : The module name} {--force : Force installation in production} {--update : Update an already installed module}')]
#[Description('Enable a module and run its installation command')]
class InstallModuleCommand extends Command
{
    public function __construct(private readonly RepositoryInterface $modules)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $moduleName = (string) $this->argument('module');
        $installedModules = [];

        return $this->install($moduleName, $installedModules, []);
    }

    /**
     * @param  array<string, true>  $installedModules
     * @param  list<string>  $installationPath
     */
    private function install(string $moduleName, array &$installedModules, array $installationPath): int
    {
        if (isset($installedModules[$moduleName])) {
            return self::SUCCESS;
        }

        $module = $this->modules->find($moduleName);

        if ($module === null) {
            $this->components->error("Module [{$moduleName}] does not exist.");

            return self::FAILURE;
        }

        if (in_array($moduleName, $installationPath, true)) {
            $cycle = implode(' -> ', [...$installationPath, $moduleName]);
            $this->components->error("Circular module dependency detected: {$cycle}.");

            return self::FAILURE;
        }

        $installationPath[] = $moduleName;
        foreach ($module->get('requires', []) as $requiredModule) {
            if (! is_string($requiredModule) || blank($requiredModule)) {
                continue;
            }

            $exitCode = $this->install($requiredModule, $installedModules, $installationPath);
            if ($exitCode !== self::SUCCESS) {
                $this->components->error("Unable to install dependency [{$requiredModule}] required by [{$moduleName}].");

                return $exitCode;
            }
        }

        $installationCommand = $module->get('install-command');

        $wasDisabled = $module->isDisabled();

        try {
            if ($wasDisabled) {
                $module->enable();
                $module->register();
                $this->registerModuleCommands($module);
                $module->boot();
            }

            if (filled($installationCommand) && ! $this->getApplication()?->has($installationCommand)) {
                $this->components->error("Installation command [{$installationCommand}] is not available.");

                if ($wasDisabled) {
                    $module->disable();
                }

                return self::FAILURE;
            }

            $exitCode = filled($installationCommand) && ! $this->option('update')
                ? $this->call($installationCommand, ['--force' => (bool) $this->option('force')])
                : $this->installUsingModuleDefaults($moduleName);

            if ($exitCode !== self::SUCCESS && $wasDisabled) {
                $module->disable();
            }

            if ($exitCode === self::SUCCESS) {
                ModuleStatus::query()->updateOrCreate(
                    ['name' => $moduleName],
                    [
                        'enabled' => true,
                        'installed_version' => (string) ($module->get('version') ?? '1.0.0'),
                    ],
                );
                $installedModules[$moduleName] = true;

                $this->callSilently('filament:clear-cached-components');
            }

            return $exitCode;
        } catch (Throwable $exception) {
            if ($wasDisabled) {
                $module->disable();
            }

            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function installUsingModuleDefaults(string $moduleName): int
    {
        $parameters = [
            'module' => [$moduleName],
            '--force' => (bool) $this->option('force'),
        ];

        if ($this->call('module:migrate', $parameters) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($this->call('module:seed', $parameters) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->installModulePermissions(str($moduleName)->lower()->toString());
        $message = $this->option('update')
            ? "{$moduleName} module updated successfully."
            : "{$moduleName} module installed successfully.";

        $this->components->info($message);

        return self::SUCCESS;
    }

    private function registerModuleCommands(Module $module): void
    {
        $artisan = $this->getApplication();

        if (! $artisan instanceof ArtisanApplication) {
            return;
        }

        foreach ($module->get('providers', []) as $providerClass) {
            $provider = app()->getProvider($providerClass);

            if ($provider === null) {
                continue;
            }

            $commandsProperty = (new ReflectionClass($provider))->getProperty('commands');
            $artisan->resolveCommands($commandsProperty->getValue($provider));
        }
    }

    private function installModulePermissions(string $configKey): void
    {
        $guardName = config('auth.defaults.guard', 'web');
        collect(config("{$configKey}.permissions", []))
            ->mapWithKeys(function (string $name) use ($guardName): array {
                return [$name => Permission::query()->firstOrCreate([
                    'name' => $name,
                    'guard_name' => $guardName,
                ])];
            })
            ->all();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
