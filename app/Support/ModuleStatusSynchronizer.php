<?php

namespace App\Support;

use App\Models\ModuleStatus;
use Illuminate\Database\ConnectionInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;

class ModuleStatusSynchronizer
{
    public function __construct(
        private readonly RepositoryInterface $modules,
        private readonly ConnectionInterface $database,
    ) {}

    /** @return array{added: int, removed: int, total: int} */
    public function synchronize(): array
    {
        $moduleNames = collect($this->modules->all())
            ->map(fn (Module $module): string => $module->getName())
            ->unique()
            ->values();

        return $this->database->transaction(function () use ($moduleNames): array {
            $timestamp = now();
            $added = ModuleStatus::query()->insertOrIgnore(
                $moduleNames->map(fn (string $name): array => [
                    'name' => $name,
                    'enabled' => false,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])->all(),
            );

            $staleModules = ModuleStatus::query();

            if ($moduleNames->isNotEmpty()) {
                $staleModules->whereNotIn('name', $moduleNames);
            }

            $removed = $staleModules->delete();

            return [
                'added' => $added,
                'removed' => $removed,
                'total' => $moduleNames->count(),
            ];
        });
    }
}
