<x-filament-panels::page>
    @php
        $formatRupiah = fn (mixed $value): string => 'Rp '.number_format((float) $value, 2, ',', '.');
    @endphp

    @if ($shortcuts !== [])
        <x-filament::section>
            <x-slot name="heading">Aksi Cepat</x-slot>
            <x-slot name="description">Shortcut ke menu yang sering digunakan.</x-slot>

            <div class="flex flex-wrap gap-3">
                @foreach ($shortcuts as $shortcut)
                    <x-filament::button
                        tag="a"
                        href="{{ $shortcut['url'] }}"
                        icon="{{ $shortcut['icon'] }}"
                        color="{{ $shortcut['color'] }}"
                    >
                        {{ $shortcut['label'] }}
                    </x-filament::button>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    <x-filament::section>
        <x-slot name="heading">Kas Koperasi</x-slot>
        <x-slot name="description">Total kas saat ini dan rincian per akun kas.</x-slot>

        <div class="rounded-xl bg-primary-50 p-6 dark:bg-primary-500/10">
            <p class="text-sm text-primary-600 dark:text-primary-400">Total Kas Saat Ini</p>
            <p class="mt-2 text-3xl font-bold text-primary-700 dark:text-primary-300">
                {{ $formatRupiah($kas['total']) }}
            </p>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2">Kode Akun</th>
                        <th class="px-3 py-2">Nama Akun Kas</th>
                        <th class="px-3 py-2 text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kas['accounts'] as $account)
                        <tr class="border-b last:border-0">
                            <td class="px-3 py-2">{{ $account['kode'] }}</td>
                            <td class="px-3 py-2">{{ $account['nama'] }}</td>
                            <td class="px-3 py-2 text-right">{{ $formatRupiah($account['saldo']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-3 py-4 text-center text-gray-500 dark:text-gray-400" colspan="3">
                                Akun kas belum dikonfigurasi pada modul mana pun.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Piutang & Tunggakan</x-slot>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Jumlah Piutang (sisa pokok)</p>
                <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ $formatRupiah($piutang['sisa_pokok']) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $piutang['pinjaman'] }} pinjaman aktif</p>
            </div>
            <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Pembayaran Tertunggak</p>
                <p class="mt-1 text-xl font-semibold text-danger-600 dark:text-danger-400">{{ $formatRupiah($tunggakan['jumlah']) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $tunggakan['angsuran'] }} angsuran terlambat</p>
            </div>
            <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Denda Belum Dicairkan</p>
                <p class="mt-1 text-xl font-semibold text-warning-600 dark:text-warning-400">{{ $formatRupiah($denda) }}</p>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Pendapatan & Pengeluaran</x-slot>
        <x-slot name="description">Periode: {{ $periode['label'] }} ({{ $periode['dari'] }} s/d {{ $periode['sampai'] }})</x-slot>

        <div class="mb-4 flex flex-wrap items-center gap-2">
            <x-filament::button wire:click="$set('filter', 'hari')" :color="$filter === 'hari' ? 'primary' : 'gray'" size="xs">
                Hari ini
            </x-filament::button>
            <x-filament::button wire:click="$set('filter', 'bulan')" :color="$filter === 'bulan' ? 'primary' : 'gray'" size="xs">
                Bulan ini
            </x-filament::button>
            <span class="inline-flex items-center gap-2">
                <input type="date" wire:model.live="dari" class="rounded-lg border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900">
                <input type="date" wire:model.live="sampai" class="rounded-lg border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900">
                <x-filament::button wire:click="applyRentang" size="xs" :color="$filter === 'rentang' ? 'primary' : 'gray'">
                    Terapkan Rentang
                </x-filament::button>
            </span>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-xl bg-success-50 p-4 dark:bg-success-500/10">
                <p class="text-sm text-success-600 dark:text-success-400">Pendapatan</p>
                <p class="mt-1 text-xl font-semibold text-success-700 dark:text-success-300">{{ $formatRupiah($keuangan['pendapatan']) }}</p>
            </div>
            <div class="rounded-xl bg-danger-50 p-4 dark:bg-danger-500/10">
                <p class="text-sm text-danger-600 dark:text-danger-400">Pengeluaran</p>
                <p class="mt-1 text-xl font-semibold text-danger-700 dark:text-danger-300">{{ $formatRupiah($keuangan['pengeluaran']) }}</p>
            </div>
            <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Selisih</p>
                <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ $formatRupiah($keuangan['selisih']) }}</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <p class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Rincian Pendapatan</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-gray-500 dark:text-gray-400">
                                <th class="px-3 py-2">Kode</th>
                                <th class="px-3 py-2">Akun Pendapatan</th>
                                <th class="px-3 py-2 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($keuangan['pendapatan_detail'] as $row)
                                <tr class="border-b last:border-0">
                                    <td class="px-3 py-2">{{ $row['kode'] }}</td>
                                    <td class="px-3 py-2">{{ $row['nama'] }}</td>
                                    <td class="px-3 py-2 text-right">{{ $formatRupiah($row['nominal']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3 py-4 text-center text-gray-500 dark:text-gray-400" colspan="3">
                                        Tidak ada pendapatan pada periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div>
                <p class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Rincian Pengeluaran</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-gray-500 dark:text-gray-400">
                                <th class="px-3 py-2">Kode</th>
                                <th class="px-3 py-2">Akun Beban</th>
                                <th class="px-3 py-2 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($keuangan['pengeluaran_detail'] as $row)
                                <tr class="border-b last:border-0">
                                    <td class="px-3 py-2">{{ $row['kode'] }}</td>
                                    <td class="px-3 py-2">{{ $row['nama'] }}</td>
                                    <td class="px-3 py-2 text-right">{{ $formatRupiah($row['nominal']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3 py-4 text-center text-gray-500 dark:text-gray-400" colspan="3">
                                        Tidak ada pengeluaran pada periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
            Ringkasan mencakup seluruh jurnal akun pendapatan dan beban pada periode terpilih — termasuk
            transaksi kas operasional (modul Keuangan) dan pendapatan pinjaman (administrasi, provisi, bunga, denda).
        </p>
    </x-filament::section>
</x-filament-panels::page>