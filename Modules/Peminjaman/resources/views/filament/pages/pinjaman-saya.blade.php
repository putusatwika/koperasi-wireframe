<x-filament-panels::page>
    @php
        $formatRupiah = fn (mixed $value): string => 'Rp '.number_format((float) $value, 2, ',', '.');
    @endphp

    <x-filament::section>
        <x-slot name="heading">Pinjaman Aktif</x-slot>
        <x-slot name="description">Pinjaman yang sedang berjalan atau dalam proses pengajuan.</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2">Nomor</th>
                        <th class="px-3 py-2 text-right">Plafon</th>
                        <th class="px-3 py-2">Tenor</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2 text-right">Sisa Pokok</th>
                        <th class="px-3 py-2">Angsuran Berikutnya</th>
                        <th class="px-3 py-2 text-right">Denda</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($aktif as $row)
                        @php($peminjaman = $row['peminjaman'])
                        <tr class="border-b last:border-0">
                            <td class="px-3 py-2">{{ $peminjaman->loan_number }}</td>
                            <td class="px-3 py-2 text-right">{{ $formatRupiah($peminjaman->loan_amount) }}</td>
                            <td class="px-3 py-2">{{ $peminjaman->term_months }} bulan</td>
                            <td class="px-3 py-2">{{ $peminjaman->status?->name }}</td>
                            <td class="px-3 py-2 text-right">{{ $row['sisa_pokok'] !== null ? $formatRupiah($row['sisa_pokok']) : '-' }}</td>
                            <td class="px-3 py-2">
                                @if ($row['next'])
                                    {{ $formatRupiah($row['next']->total) }} · {{ $row['next']->jatuh_tempo->toDateString() }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">{{ $row['denda'] > 0 ? $formatRupiah($row['denda']) : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-3 py-4 text-center text-gray-500 dark:text-gray-400" colspan="7">
                                Tidak ada pinjaman aktif.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Pinjaman Ditutup</x-slot>
        <x-slot name="description">Pinjaman yang sudah lunas, ditolak, atau dibatalkan.</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2">Nomor</th>
                        <th class="px-3 py-2 text-right">Plafon</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2 text-right">Sisa Pokok</th>
                        <th class="px-3 py-2">Ditandai Lunas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ditutup as $row)
                        @php($peminjaman = $row['peminjaman'])
                        <tr class="border-b last:border-0">
                            <td class="px-3 py-2">{{ $peminjaman->loan_number }}</td>
                            <td class="px-3 py-2 text-right">{{ $formatRupiah($peminjaman->loan_amount) }}</td>
                            <td class="px-3 py-2">{{ $peminjaman->status?->name }}</td>
                            <td class="px-3 py-2 text-right">{{ $row['sisa_pokok'] !== null ? $formatRupiah($row['sisa_pokok']) : '-' }}</td>
                            <td class="px-3 py-2">{{ $peminjaman->lunas_at?->toDateString() ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-3 py-4 text-center text-gray-500 dark:text-gray-400" colspan="5">
                                Tidak ada riwayat pinjaman ditutup.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>