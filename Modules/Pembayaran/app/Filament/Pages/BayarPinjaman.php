<?php

namespace Modules\Pembayaran\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Modules\Pembayaran\Services\PembayaranService;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Models\Peminjaman;
use Throwable;
use UnitEnum;

class BayarPinjaman extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Pembayaran';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Input Pembayaran';

    protected static ?int $navigationSort = 20;

    protected string $view = 'pembayaran::filament.pages.bayar-pinjaman';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('pembayaran.pembayaran.create') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        $loanOptions = Peminjaman::query()
            ->with('anggota.user')
            ->where('status', StatusPeminjaman::Disbursed)
            ->orderByDesc('disbursed_at')
            ->get()
            ->mapWithKeys(fn (Peminjaman $peminjaman): array => [
                $peminjaman->getKey() => sprintf(
                    '%s — %s — Rp %s',
                    $peminjaman->loan_number,
                    $peminjaman->anggota?->user?->name ?? '-',
                    number_format((float) $peminjaman->loan_amount, 2, ',', '.'),
                ),
            ])
            ->all();

        return $schema
            ->statePath('data')
            ->components([
                Select::make('peminjaman_id')
                    ->label('Pinjaman')
                    ->options($loanOptions)
                    ->searchable()
                    ->live()
                    ->required(),
                Toggle::make('pelunasan_dipercepat')
                    ->label('Pelunasan Dipercepat')
                    ->live(),
                Placeholder::make('rincian_pelunasan')
                    ->label('Rincian Pelunasan Dipercepat')
                    ->visible(fn (Get $get): bool => (bool) $get('pelunasan_dipercepat'))
                    ->content(function (Get $get): string {
                        $peminjaman = Peminjaman::query()->find($get('peminjaman_id'));

                        if ($peminjaman === null) {
                            return 'Pilih pinjaman terlebih dahulu.';
                        }

                        $ringkasan = app(PembayaranService::class)->ringkasanPelunasanDipercepat($peminjaman);

                        return sprintf(
                            'Sisa pokok: Rp %s · Sisa bunga: Rp %s · Penalti: Rp %s · Total: Rp %s',
                            number_format($ringkasan['sisa_pokok'], 2, ',', '.'),
                            number_format($ringkasan['sisa_bunga'], 2, ',', '.'),
                            number_format($ringkasan['penalti'], 2, ',', '.'),
                            number_format($ringkasan['total'], 2, ',', '.'),
                        );
                    }),
                DatePicker::make('payment_date')->label('Tanggal Pembayaran')->default(now())->required(),
                TextInput::make('installment_number')
                    ->label('Angsuran Ke')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->visible(fn (Get $get): bool => ! (bool) $get('pelunasan_dipercepat')),
                TextInput::make('amount')
                    ->label('Jumlah Pembayaran')
                    ->numeric()
                    ->minValue(1)
                    ->prefix('Rp')
                    ->required()
                    ->visible(fn (Get $get): bool => ! (bool) $get('pelunasan_dipercepat')),
                TextInput::make('principal_amount')
                    ->label('Pokok')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp')
                    ->visible(fn (Get $get): bool => ! (bool) $get('pelunasan_dipercepat')),
                TextInput::make('interest_amount')
                    ->label('Bunga')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp')
                    ->visible(fn (Get $get): bool => ! (bool) $get('pelunasan_dipercepat')),
                TextInput::make('penalty_amount')
                    ->label('Denda')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp')
                    ->visible(fn (Get $get): bool => ! (bool) $get('pelunasan_dipercepat')),
                TextInput::make('reference_number')->label('Nomor Referensi')->maxLength(100),
                FileUpload::make('receipt_document')
                    ->label('Bukti Pembayaran')
                    ->disk('public')
                    ->directory('pembayaran/bukti')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(10240),
                Textarea::make('notes')->label('Catatan')->maxLength(1000)->columnSpanFull(),
            ]);
    }

    public function submit(PembayaranService $service): void
    {
        try {
            $data = Arr::except($this->form->getState(), ['pelunasan_dipercepat', 'rincian_pelunasan']);

            if ($this->form->getState()['pelunasan_dipercepat'] ?? false) {
                $peminjaman = Peminjaman::query()->findOrFail($data['peminjaman_id']);
                $service->settleEarly($peminjaman, [
                    'payment_date' => $data['payment_date'],
                    'reference_number' => $data['reference_number'] ?? null,
                    'receipt_document' => $data['receipt_document'] ?? null,
                    'notes' => filled($data['notes'] ?? null) ? $data['notes'] : 'Pelunasan dipercepat',
                ]);
            } else {
                $service->createManualPayment($data);
            }
        } catch (Throwable $exception) {
            Notification::make()->title('Pembayaran gagal')->body($exception->getMessage())->danger()->persistent()->send();

            return;
        }

        Notification::make()->title('Pembayaran berhasil dicatat')->success()->send();
        $this->form->fill();
    }
}
