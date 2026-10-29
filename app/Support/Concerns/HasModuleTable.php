<?php

namespace App\Support\Concerns;

use Illuminate\Support\Str;
use LogicException;

trait HasModuleTable
{
    public function getTable(): string
    {
        $table = parent::getTable();
        $prefix = Str::lower($this->moduleName()).'_';

        return Str::startsWith($table, $prefix) ? $table : $prefix.$table;
    }

    protected function moduleName(): string
    {
        $namespaceParts = explode('\\', static::class);

        if (($namespaceParts[0] ?? null) !== 'Modules' || blank($namespaceParts[1] ?? null)) {
            throw new LogicException('Module models must use a Modules\\{ModuleName} namespace.');
        }

        return (string) $namespaceParts[1];
    }

    protected function moduleTableName(string $moduleName, string $tableName): string
    {
        return Str::lower($moduleName).'_'.$tableName;
    }
}
