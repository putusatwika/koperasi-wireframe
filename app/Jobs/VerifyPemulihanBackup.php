<?php

namespace App\Jobs;

use App\Models\PemulihanBackup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class VerifyPemulihanBackup implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [1, 5, 10];

    public int $uniqueFor = 3600;

    public function __construct(public PemulihanBackup $pemulihan) {}

    public function uniqueId(): string
    {
        return (string) $this->pemulihan->getKey();
    }

    public function handle(): void
    {
        $this->pemulihan->update(['status' => 'verifying']);
        $backup = $this->pemulihan->arsipBackup;
        $exists = Storage::disk($backup->disk)->exists($backup->path);
        $checksum = $exists ? $this->checksum($backup->disk, $backup->path) : null;
        $valid = $exists && hash_equals($backup->checksum, (string) $checksum);

        $this->pemulihan->update([
            'status' => $valid ? 'verified' : 'failed',
            'hasil_verifikasi' => ['exists' => $exists, 'checksum_valid' => $valid],
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->pemulihan->update(['status' => 'failed', 'hasil_verifikasi' => ['error' => $exception?->getMessage()]]);
    }

    private function checksum(string $disk, string $path): string
    {
        $stream = Storage::disk($disk)->readStream($path);
        $context = hash_init('sha256');

        while (! feof($stream)) {
            hash_update($context, fread($stream, 1024 * 1024));
        }

        fclose($stream);

        return hash_final($context);
    }
}
