<?php

namespace Modules\Keanggotaan\Services;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Keanggotaan\Events\AnggotaTerdaftar;
use Modules\Keanggotaan\Models\Anggota;
use Spatie\Permission\Models\Role;

class AnggotaService
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): Anggota
    {
        $initialSavings = $this->initialSavings($data);

        unset($data['initial_savings']);

        try {
            return DB::transaction(function () use ($data, $initialSavings): Anggota {
                $data['member_number'] ??= $this->generateMemberNumber();

                $requestedStatus = $data['status'] ?? null;

                $user = User::query()->create($this->userData($data));
                $user->assignRole(Role::findOrCreate('anggota', config('auth.defaults.guard', 'web')));
                $anggota = $user->anggota()->create(Arr::except($data, ['user']));

                AnggotaTerdaftar::dispatch($anggota, $initialSavings);

                $this->applyRequestedStatus($anggota, $requestedStatus);

                return $anggota->refresh();
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw $this->duplicateDataException($exception);
        }
    }

    public function generateMemberNumber(): string
    {
        $prefix = 'AGT-';

        $lastNumber = Anggota::query()
            ->withTrashed()
            ->where('member_number', 'like', $prefix.'%')
            ->pluck('member_number')
            ->map(fn (string $memberNumber): int => (int) substr($memberNumber, strlen($prefix)))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($lastNumber + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(Anggota $anggota, array $data): Anggota
    {
        try {
            return DB::transaction(function () use ($anggota, $data): Anggota {
                $anggota->loadMissing('user');
                $anggota->user->update($this->userData($data));
                $anggota->update(Arr::except($data, ['user', 'initial_savings']));

                return $anggota->refresh();
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw $this->duplicateDataException($exception);
        }
    }

    /** @param  array<string, mixed>  $data */
    private function userData(array $data): array
    {
        $userData = Arr::get($data, 'user', []);

        if (blank($userData['password'] ?? null)) {
            unset($userData['password']);
        }

        return $userData;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{pokok?: float, wajib?: float}
     */
    private function initialSavings(array $data): array
    {
        $savings = Arr::get($data, 'initial_savings', []);

        $payments = [];

        if (filter_var($savings['pokok_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $payments['pokok'] = (float) ($savings['pokok'] ?? 0);
        }

        if (filter_var($savings['wajib_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $payments['wajib'] = (float) ($savings['wajib'] ?? 0);
        }

        return $payments;
    }

    private function applyRequestedStatus(Anggota $anggota, ?string $requestedStatus): void
    {
        if ($requestedStatus === 'inactive') {
            $anggota->update(['status' => 'inactive']);

            return;
        }

        if ($requestedStatus === 'active' && $anggota->status !== 'active') {
            $anggota->update(['status' => 'active']);
        }
    }

    private function duplicateDataException(UniqueConstraintViolationException $exception): ValidationException
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'account_number')) {
            return ValidationException::withMessages([
                'data.account_number' => 'Nomor rekening ini sudah dipakai oleh anggota lain. Periksa kembali nomor rekening atau gunakan nomor yang berbeda.',
            ]);
        }

        if (str_contains($message, 'identity_number')) {
            return ValidationException::withMessages([
                'data.identity_number' => 'Nomor KTP ini sudah terdaftar pada anggota lain. Periksa kembali nomor KTP.',
            ]);
        }

        if (str_contains($message, 'email')) {
            return ValidationException::withMessages([
                'data.user.email' => 'Email ini sudah digunakan oleh pengguna lain. Gunakan alamat email yang berbeda.',
            ]);
        }

        return ValidationException::withMessages([
            'data.account_number' => 'Data anggota tidak dapat disimpan karena ada data yang sudah digunakan. Periksa kembali nomor rekening dan nomor KTP.',
        ]);
    }
}
