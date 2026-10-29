<?php

namespace Modules\Keanggotaan\Filament\Resources\Anggotas\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Keanggotaan\Filament\Resources\Anggotas\AnggotaResource;
use Modules\Keanggotaan\Services\AnggotaService;

class CreateAnggota extends CreateRecord
{
    protected static string $resource = AnggotaResource::class;

    /** @param  array<string, mixed>  $data */
    protected function handleRecordCreation(array $data): Model
    {
        return app(AnggotaService::class)->create($data);
    }

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['member_number'] = app(AnggotaService::class)->generateMemberNumber();

        return $data;
    }
}
