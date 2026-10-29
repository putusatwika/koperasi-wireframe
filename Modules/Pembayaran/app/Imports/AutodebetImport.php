<?php

namespace Modules\Pembayaran\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Modules\Pembayaran\Services\PembayaranService;

class AutodebetImport implements SkipsEmptyRows, ToCollection, WithHeadingRow, WithValidation
{
    private int $importedCount = 0;

    public function __construct(private readonly PembayaranService $service) {}

    public function collection(Collection $rows): void
    {
        $this->importedCount = $this->service->importAutodebetRows($rows);
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            '*.nomor_pinjaman' => ['required', 'string'],
            '*.nomor_anggota' => ['required', 'string'],
            '*.jumlah' => ['required', 'numeric', 'min:0.01'],
            '*.tanggal_pembayaran' => ['nullable'],
            '*.angsuran_ke' => ['nullable', 'integer', 'min:1'],
            '*.pokok' => ['nullable', 'numeric', 'min:0'],
            '*.bunga' => ['nullable', 'numeric', 'min:0'],
            '*.denda' => ['nullable', 'numeric', 'min:0'],
            '*.nomor_referensi' => ['nullable', 'string', 'max:100'],
            '*.catatan' => ['nullable', 'string'],
        ];
    }
}
