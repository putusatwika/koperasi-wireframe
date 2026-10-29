<?php

namespace Modules\Keanggotaan\Filament\Resources\Anggotas\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Keanggotaan\Filament\Resources\Anggotas\AnggotaResource;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Keanggotaan\Services\AnggotaService;

class EditAnggota extends EditRecord
{
    protected static string $resource = AnggotaResource::class;

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('user');
        $data['user'] = $this->record->user->only(['name', 'email']);

        return $data;
    }

    /** @param  array<string, mixed>  $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Anggota $record */
        return app(AnggotaService::class)->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->authorize('keanggotaan.anggota.view'),
            DeleteAction::make()->authorize('keanggotaan.anggota.delete'),
            ForceDeleteAction::make()->authorize('keanggotaan.anggota.delete'),
            RestoreAction::make()->authorize('keanggotaan.anggota.delete'),
        ];
    }
}
