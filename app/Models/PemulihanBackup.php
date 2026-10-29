<?php

namespace App\Models;

use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['uuid', 'arsip_backup_id', 'safety_backup_id', 'status', 'hasil_verifikasi', 'alasan', 'requested_by', 'confirmed_at', 'completed_at'])]
class PemulihanBackup extends Model
{
    use UseLogger;

    protected $table = 'pemulihan_backups';

    protected $attributes = [
        'status' => 'requested',
    ];

    public function arsipBackup(): BelongsTo
    {
        return $this->belongsTo(ArsipBackup::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'hasil_verifikasi' => 'array',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
