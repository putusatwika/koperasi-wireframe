<?php

namespace App\Jobs;

use App\Models\PesanNotifikasi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessPesanNotifikasi implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [1, 5, 10];

    public int $uniqueFor = 3600;

    public function __construct(public PesanNotifikasi $pesan) {}

    public function uniqueId(): string
    {
        return (string) $this->pesan->getKey();
    }

    public function handle(): void
    {
        $this->pesan->update([
            'status' => 'processing',
            'percobaan' => $this->pesan->percobaan + 1,
        ]);

        Log::info('System notification processed.', [
            'notification_id' => $this->pesan->getKey(),
            'channel' => $this->pesan->channel,
            'tujuan' => $this->pesan->tujuan,
        ]);

        $this->pesan->update([
            'status' => 'sent',
            'hasil' => 'Processed by the core notification queue.',
            'sent_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->pesan->update([
            'status' => 'failed',
            'hasil' => $exception?->getMessage(),
        ]);
    }
}
