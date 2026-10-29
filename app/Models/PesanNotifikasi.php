<?php

namespace App\Models;

use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['deduplication_key', 'sumber_type', 'sumber_id', 'anggota_id', 'channel', 'tujuan', 'template', 'payload', 'status', 'percobaan', 'hasil', 'sent_at'])]
class PesanNotifikasi extends Model
{
    use UseLogger;

    protected $table = 'pesan_notifikasis';

    protected $attributes = [
        'channel' => 'log',
        'status' => 'queued',
        'percobaan' => 0,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }
}
