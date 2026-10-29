<?php

namespace App\Support;

use Illuminate\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Module;
use RuntimeException;

class DatabaseModuleActivator implements ActivatorInterface
{
    private ConnectionInterface $database;

    private string $tableName;

    private ?bool $tableExists = null;

    public function __construct(Container $app)
    {
        $this->database = $app->make(DatabaseManager::class)->connection();
        $this->tableName = (string) $app['config']->get('modules.activators.database.table', 'module_statuses');
    }

    public function enable(Module $module): void
    {
        $this->setActive($module, true);
    }

    public function disable(Module $module): void
    {
        $this->setActive($module, false);
    }

    public function hasStatus(Module|string $module, bool $status): bool
    {
        if (! $this->statusTableExists()) {
            return $status === false;
        }

        $name = $module instanceof Module ? $module->getName() : $module;
        $enabled = $this->database->table($this->tableName)
            ->where('name', $name)
            ->value('enabled');

        return $enabled === null ? $status === false : (bool) $enabled === $status;
    }

    public function setActive(Module $module, bool $active): void
    {
        $this->setActiveByName($module->getName(), $active);
    }

    public function setActiveByName(string $name, bool $active): void
    {
        $this->ensureStatusTableExists();

        $timestamp = now();

        $this->database->table($this->tableName)->upsert([
            [
                'name' => $name,
                'enabled' => $active,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ], ['name'], ['enabled', 'updated_at']);
    }

    public function delete(Module $module): void
    {
        if (! $this->statusTableExists()) {
            return;
        }

        $this->database->table($this->tableName)
            ->where('name', $module->getName())
            ->delete();
    }

    public function reset(): void
    {
        if (! $this->statusTableExists()) {
            return;
        }

        $this->database->table($this->tableName)->delete();
    }

    private function statusTableExists(): bool
    {
        return $this->tableExists ??= $this->database->getSchemaBuilder()->hasTable($this->tableName);
    }

    private function ensureStatusTableExists(): void
    {
        $this->tableExists = $this->database->getSchemaBuilder()->hasTable($this->tableName);

        if (! $this->tableExists) {
            throw new RuntimeException("Module status table [{$this->tableName}] does not exist.");
        }
    }
}
