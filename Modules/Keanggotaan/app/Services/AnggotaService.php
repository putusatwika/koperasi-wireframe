<?php

namespace Modules\Keanggotaan\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Keanggotaan\Events\AnggotaTerdaftar;
use Modules\Keanggotaan\Models\Anggota;

class AnggotaService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Anggota
    {
        return DB::transaction(function () use ($data): Anggota {
            $user = User::query()->create($this->userData($data));
            $anggota = $user->anggota()->create(Arr::except($data, ['user']));

            AnggotaTerdaftar::dispatch($anggota);

            return $anggota;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Anggota $anggota, array $data): Anggota
    {
        return DB::transaction(function () use ($anggota, $data): Anggota {
            $anggota->loadMissing('user');
            $anggota->user->update($this->userData($data));
            $anggota->update(Arr::except($data, ['user']));

            return $anggota->refresh();
        });
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
}
