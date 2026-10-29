<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module as ModuleInstance;

#[Signature('module:update {module : The module name} {--force : Force update in production}')]
#[Description('Run pending migrations, seeders, and permission synchronization for an installed module')]
class UpdateModuleCommand extends Command
{
    public function __construct(private readonly RepositoryInterface $modules)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $moduleName = (string) $this->argument('module');
        $module = $this->modules->find($moduleName);

        if (! $module instanceof ModuleInstance) {
            $this->components->error("Module [{$moduleName}] does not exist.");

            return self::FAILURE;
        }

        if (! $this->moduleHasBeenInstalled($module)) {
            $this->components->error("Module [{$moduleName}] is not installed.");

            return self::FAILURE;
        }

        return $this->call('module:install', [
            'module' => $moduleName,
            '--force' => (bool) $this->option('force'),
            '--update' => true,
        ]);
    }

    private function moduleHasBeenInstalled(ModuleInstance $module): bool
    {
        $migrator = app(Migrator::class);
        $migrationPath = $module->getPath().DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
        $migrationFiles = $migrator->getMigrationFiles([$migrationPath]);

        if ((bool) DB::table('module_statuses')->where('name', $module->getName())->value('enabled')) {
            return true;
        }

        return collect($migrationFiles)->keys()->contains(
            fn (string $migration): bool => in_array($migration, $migrator->getRepository()->getRan(), true),
        );
    }
}
