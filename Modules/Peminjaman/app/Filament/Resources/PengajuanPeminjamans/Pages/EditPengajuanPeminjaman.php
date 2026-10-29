<?php

namespace Modules\Peminjaman\Filament\Resources\PengajuanPeminjamans\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Filament\Resources\PengajuanPeminjamans\PengajuanPeminjamanResource;
use Modules\Peminjaman\Models\Peminjaman;
use Modules\Peminjaman\Services\PeminjamanService;

class EditPengajuanPeminjaman extends EditRecord
{
    protected static string $resource = PengajuanPeminjamanResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Peminjaman $record */
        if (! in_array($record->status, [StatusPeminjaman::Draft, StatusPeminjaman::Submitted, StatusPeminjaman::Rejected], true)) {
            abort(422, 'Pengajuan hanya dapat diubah sebelum pemeriksaan atau setelah ditolak.');
        }

        return app(PeminjamanService::class)->updateApplication($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->authorize('peminjaman.pengajuan.view'),
            Action::make('kirimPengajuan')
                ->label('Kirim Pengajuan')
                ->color('success')
                ->authorize('peminjaman.pengajuan.create')
                ->visible(fn (): bool => $this->record->status === StatusPeminjaman::Draft)
                ->requiresConfirmation()
                ->action(function (PeminjamanService $service): void {
                    $service->submit($this->getRecord());
                    Notification::make()->title('Pengajuan berhasil dikirim')->success()->send();
                    $this->redirect(PengajuanPeminjamanResource::getUrl('view', ['record' => $this->getRecord()]));
                }),
            DeleteAction::make()->authorize('peminjaman.pengajuan.delete'),
            ForceDeleteAction::make()->authorize('peminjaman.pengajuan.delete'),
            RestoreAction::make()->authorize('peminjaman.pengajuan.delete'),
        ];
    }
}
