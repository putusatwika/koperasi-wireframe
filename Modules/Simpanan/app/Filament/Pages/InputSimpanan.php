<?php

namespace Modules\Simpanan\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Simpanan\Enums\TipeTransaksiSimpanan;
use Modules\Simpanan\Models\JenisSimpanan;
use Modules\Simpanan\Services\SimpananService;
use Throwable;
use UnitEnum;

class InputSimpanan extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Simpanan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Input Simpanan';

    protected static ?int $navigationSort = 20;

    protected string $view = 'simpanan::filament.pages.input-simpanan';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('simpanan.transaksi.create') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        $anggotaOptions = Anggota::query()
            ->with('user')
            ->where('status', 'active')
            ->orderBy('member_number')
            ->get()
            ->mapWithKeys(fn (Anggota $anggota): array => [
                $anggota->getKey() => "{$anggota->member_number} - {$anggota->user?->name}",
            ])
            ->all();

        return $schema
            ->statePath('data')
            ->components([
                Select::make('anggota_id')
                    ->label('Anggota')
                    ->options($anggotaOptions)
                    ->searchable()
                    ->required(),
                Select::make('jenis_simpanan_id')
                    ->label('Produk Simpanan')
                    ->options(JenisSimpanan::query()->where('is_active', true)->pluck('nama', 'id')->all())
                    ->searchable()
                    ->required(),
                Select::make('tipe')
                    ->label('Jenis Transaksi')
                    ->options([
                        TipeTransaksiSimpanan::Setor->value => 'Setor',
                        TipeTransaksiSimpanan::Tarik->value => 'Tarik',
                    ])
                    ->required(),
                DatePicker::make('tanggal')->label('Tanggal')->default(now())->required(),
                TextInput::make('nominal')->label('Nominal')->numeric()->minValue(1)->prefix('Rp')->required(),
                TextInput::make('referensi')->label('Referensi')->maxLength(100),
                Textarea::make('keterangan')->label('Keterangan')->maxLength(1000)->columnSpanFull(),
            ]);
    }

    public function submit(SimpananService $service): void
    {
        try {
            $data = $this->form->getState();
            $anggota = Anggota::query()->findOrFail($data['anggota_id']);
            $jenis = JenisSimpanan::query()->findOrFail($data['jenis_simpanan_id']);
            $akun = $service->openAccount($anggota, $jenis);
            $meta = [
                'tanggal' => $data['tanggal'],
                'referensi' => $data['referensi'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
            ];

            if ($data['tipe'] === TipeTransaksiSimpanan::Setor->value) {
                $service->setor($akun, (float) $data['nominal'], $meta);
            } else {
                $service->tarik($akun, (float) $data['nominal'], $meta);
            }
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Transaksi simpanan gagal')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()->title('Transaksi simpanan berhasil dicatat')->success()->send();
        $this->form->fill();
    }
}
