<?php

namespace App\Models;

use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'enabled', 'installed_version'])]
class ModuleStatus extends Model
{
    use UseLogger;

    protected $primaryKey = 'name';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }
}
