<?php

namespace App\Models;

use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['uuid', 'jenis', 'disk', 'path', 'checksum', 'ukuran', 'terenkripsi', 'status', 'created_by', 'verified_at'])]
class ArsipBackup extends Model
{
    use UseLogger;

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'terenkripsi' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }
}
