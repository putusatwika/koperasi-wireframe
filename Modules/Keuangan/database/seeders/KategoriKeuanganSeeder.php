<?php

namespace Modules\Keuangan\Database\Seeders;

use App\Models\CoaAccount;
use Illuminate\Database\Seeder;
use Modules\Keuangan\Enums\TipeKategoriKeuangan;
use Modules\Keuangan\Models\KategoriKeuangan;

class KategoriKeuanganSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            ['kode' => 'PDT-LAIN', 'nama' => 'Pendapatan Lain-lain', 'tipe' => TipeKategoriKeuangan::Pendapatan, 'coa_code' => '4000'],
            ['kode' => 'PDT-DOI', 'nama' => 'Donasi / Iuran', 'tipe' => TipeKategoriKeuangan::Pendapatan, 'coa_code' => '4000'],
            ['kode' => 'PDT-BGA', 'nama' => 'Pendapatan Bunga', 'tipe' => TipeKategoriKeuangan::Pendapatan, 'coa_code' => '4000'],
            ['kode' => 'KLG-KTR', 'nama' => 'Operasional Kantor', 'tipe' => TipeKategoriKeuangan::Pengeluaran, 'coa_code' => '5000'],
            ['kode' => 'KLG-HNR', 'nama' => 'Honor & Perjalanan', 'tipe' => TipeKategoriKeuangan::Pengeluaran, 'coa_code' => '5000'],
            ['kode' => 'KLG-ATK', 'nama' => 'ATK & Administrasi', 'tipe' => TipeKategoriKeuangan::Pengeluaran, 'coa_code' => '5000'],
        ];

        foreach ($kategori as $data) {
            $kategoriKeuangan = KategoriKeuangan::query()->firstOrCreate(
                ['kode' => $data['kode']],
                [
                    'nama' => $data['nama'],
                    'tipe' => $data['tipe'],
                    'coa_account_id' => $this->coaAccountId($data['coa_code']),
                    'is_active' => true,
                ],
            );

            // Sembuhkan pemetaan COA yang masih kosong (mis. dibuat sebelum
            // chart of accounts tersedia). Pengaturan yang sudah diisi
            // bendahara tidak pernah ditimpa.
            if ($kategoriKeuangan->coa_account_id === null) {
                $coaAccountId = $this->coaAccountId($data['coa_code']);

                if (filled($coaAccountId)) {
                    $kategoriKeuangan->update(['coa_account_id' => $coaAccountId]);
                }
            }
        }
    }

    private function coaAccountId(string $code): ?int
    {
        $accountId = CoaAccount::query()->where('code', $code)->value('id');

        return filled($accountId) ? (int) $accountId : null;
    }
}
