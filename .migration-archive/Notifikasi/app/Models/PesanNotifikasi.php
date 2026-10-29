<?php

namespace Modules\Notifikasi\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['deduplication_key', 'sumber_type', 'sumber_id', 'anggota_id', 'channel', 'tujuan', 'template', 'payload', 'status', 'percobaan', 'hasil', 'sent_at'])]
class PesanNotifikasi extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }
}
