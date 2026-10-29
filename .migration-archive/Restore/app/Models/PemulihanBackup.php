<?php

namespace Modules\Restore\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['uuid', 'arsip_backup_id', 'safety_backup_id', 'status', 'hasil_verifikasi', 'alasan', 'requested_by', 'confirmed_at', 'completed_at'])]
class PemulihanBackup extends Model
{
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
