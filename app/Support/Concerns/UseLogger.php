<?php

namespace App\Support\Concerns;

use App\Models\AktivitasPengguna;
use Illuminate\Database\Eloquent\Model;
use Throwable;

trait UseLogger
{
    public static function bootUseLogger(): void
    {
        static::created(function (Model $model): void {
            $model->writeActivity('created', [], $model->getAttributes());
        });

        static::updated(function (Model $model): void {
            $model->writeActivity('updated', $model->getRawOriginal(), $model->getAttributes());
        });

        static::deleted(function (Model $model): void {
            $model->writeActivity('deleted', $model->getRawOriginal(), []);
        });
    }

    /** @param array<string, mixed> $before @param array<string, mixed> $after */
    protected function writeActivity(string $action, array $before, array $after): void
    {
        if ($this instanceof AktivitasPengguna) {
            return;
        }

        try {
            $before = $this->maskSensitive($before);
            $after = $this->maskSensitive($after);
            $changed = array_values(array_unique(array_merge(array_keys($before), array_keys($after))));

            AktivitasPengguna::query()->create([
                'aksi' => $action,
                'performed_by' => auth()->id(),
                'status_sebelum' => $before['status'] ?? null,
                'status_sesudah' => $after['status'] ?? null,
                'metadata' => [
                    'model_type' => $this::class,
                    'model_id' => $this->getKey(),
                    'before' => $before,
                    'after' => $after,
                    'changed_fields' => $changed,
                    'ip' => app()->runningInConsole() ? null : request()->ip(),
                    'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** @param array<string, mixed> $values @return array<string, mixed> */
    private function maskSensitive(array $values): array
    {
        foreach (['password', 'password_confirmation', 'remember_token', 'token', 'secret', 'api_key'] as $field) {
            if (array_key_exists($field, $values)) {
                $values[$field] = '[REDACTED]';
            }
        }

        return $values;
    }
}
