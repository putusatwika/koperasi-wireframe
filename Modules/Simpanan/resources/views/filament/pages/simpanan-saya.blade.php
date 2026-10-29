<x-filament-panels::page>
    @php
        $formatRupiah = fn (mixed $value): string => 'Rp '.number_format((float) $value, 2, ',', '.');
    @endphp

    @if ($anggota === null)
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Data anggota belum tersedia untuk akun ini.
            </p>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">Ringkasan Simpanan</x-slot>
            <x-slot name="description">Total saldo seluruh produk simpanan Anda.</x-slot>

            <div class="rounded-xl bg-primary-50 p-4 dark:bg-primary-500/10">
                <p class="text-sm text-primary-600 dark:text-primary-400">Total Saldo</p>
                <p class="mt-1 text-2xl font-semibold text-primary-700 dark:text-primary-300">
                    {{ $formatRupiah($saldo['total']) }}
                </p>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2">No. Rekening</th>
                            <th class="px-3 py-2">Produk</th>
                            <th class="px-3 py-2">Kategori</th>
                            <th class="px-3 py-2 text-right">Saldo</th>
                            <th class="px-3 py-2">Jatuh Tempo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($saldo['accounts'] as $account)
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-2">{{ $account['nomor_rekening'] ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $account['jenis'] }}</td>
                                <td class="px-3 py-2">{{ $account['kategori'] }}</td>
                                <td class="px-3 py-2 text-right">{{ $formatRupiah($account['saldo']) }}</td>
                                <td class="px-3 py-2">{{ $account['jatuh_tempo'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-4 text-center text-gray-500 dark:text-gray-400" colspan="5">
                                    Belum ada rekening simpanan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Kewajiban Belum Dibayar</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2">Produk</th>
                            <th class="px-3 py-2">Periode</th>
                            <th class="px-3 py-2 text-right">Nominal</th>
                            <th class="px-3 py-2">Jatuh Tempo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kewajibans as $kewajiban)
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-2">{{ $kewajiban->jenisSimpanan?->nama }}</td>
                                <td class="px-3 py-2">{{ $kewajiban->periode }}</td>
                                <td class="px-3 py-2 text-right">{{ $formatRupiah($kewajiban->nominal) }}</td>
                                <td class="px-3 py-2">{{ $kewajiban->jatuh_tempo?->toDateString() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-4 text-center text-gray-500 dark:text-gray-400" colspan="4">
                                    Tidak ada kewajiban simpanan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Riwayat Transaksi Terakhir</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2">Tanggal</th>
                            <th class="px-3 py-2">Produk</th>
                            <th class="px-3 py-2">Tipe</th>
                            <th class="px-3 py-2 text-right">Nominal</th>
                            <th class="px-3 py-2">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transaksis as $transaksi)
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-2">{{ $transaksi->tanggal?->toDateString() }}</td>
                                <td class="px-3 py-2">{{ $transaksi->akun?->jenisSimpanan?->nama }}</td>
                                <td class="px-3 py-2">{{ $transaksi->tipe?->name }}</td>
                                <td class="px-3 py-2 text-right">{{ $formatRupiah($transaksi->nominal) }}</td>
                                <td class="px-3 py-2">{{ $transaksi->keterangan ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-4 text-center text-gray-500 dark:text-gray-400" colspan="5">
                                    Belum ada transaksi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
