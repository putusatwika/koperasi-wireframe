<?php

namespace Modules\Keuangan\Models;

use App\Models\CoaAccount;
use App\Support\Concerns\HasModuleTable;
use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Keuangan\Enums\KeuanganCOAConfigEnum;

#[Fillable(['key', 'coa_account_id'])]
class CoaConfig extends Model
{
    use HasModuleTable;
    use UseLogger;

    protected $table = 'coa_config';

    protected function casts(): array
    {
        return [
            'key' => KeuanganCOAConfigEnum::class,
        ];
    }

    public function coaAccount(): BelongsTo
    {
        return $this->belongsTo(CoaAccount::class);
    }
}
