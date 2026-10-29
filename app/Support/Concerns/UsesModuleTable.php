<?php

namespace App\Support\Concerns;

use Illuminate\Support\Str;

trait UsesModuleTable
{
    protected function moduleTable(string $moduleName, string $tableName): string
    {
        return Str::lower($moduleName).'_'.$tableName;
    }
}
