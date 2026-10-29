<x-filament-panels::page>
    @php
        $formatRupiah = fn (mixed $value): string => 'Rp '.number_format((float) $value, 2, ',', '.');
        $tanggal = fn (mixed $value): string => $value?->toDateString() ?? '-';
    @endphp

    @if ($data === null)
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-300">Data anggota belum tersedia untuk akun ini.</p>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">Aksi Cepat</x-slot>

            <div class="flex flex-wrap gap-3">
                <x-filament::button tag="a" href="{{ $links['simulasi'] }}" icon="heroicon-o-calculator" color="gray">
                    Simulasi Kredit
                </x-filament::button>
                <x-filament::button tag="a" href="{{ $links['ajukan'] }}" icon="heroicon-o-document-plus" color="primary">
                    Ajukan Pinjaman
                </x-filament::button>
                <x-filament::button tag="a" href="{{ $links['kartu_piutang'] }}" icon="heroicon-o-banknotes" color="gray">
                    Kartu Piutang
                </x-filament::button>
                <x-filament::button tag="a" href="{{ $links['pinjaman_saya'] }}" icon="heroicon-o-document-chart-bar" color="gray">
                    Pinjaman Saya
                </x-filament::button>
                <x-filament::button tag="a" href="{{ $links['simpanan'] }}" icon="heroicon-o-archive-box" color="gray">
                    Simpanan Saya
                </x-filament::button>
                <x-filament::button tag="a" href="{{ $links['histori'] }}" icon="heroicon-o-clock" color="gray">
                    Histori Pembayaran
                </x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Total Simpanan</x-slot>
            <x-slot name="description">Seluruh produk simpanan Anda.</x-slot>

            <div class="rounded-xl bg-primary-50 p-6 dark:bg-primary-500/10">
                <p class="text-sm text-primary-600 dark:text-primary-400">Total Saldo Simpanan</p>
                <p class="mt-2 text-3xl font-bold text-primary-700 dark:text-primary-300">{{ $formatRupiah($data['simpanan']['total']) }}</p>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($data['simpanan']['accounts'] as $account)
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $account['jenis'] }} ({{ $account['kategori'] }})</p>
                        <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $formatRupiah($account['saldo']) }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $account['nomor_rekening'] ?? 'Belum ada rekening' }}
                            @if ($account['jatuh_tempo'])
                                · Jatuh tempo {{ $account['jatuh_tempo'] }}
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Pembayaran Terdekat</x-slot>

            @if ($data['pembayaran_terdekat'])
                <div class="rounded-xl bg-warning-50 p-4 dark:bg-warning-500/10">
                    <p class="text-sm text-warning-600 dark:text-warning-400">
                        Angsuran pinjaman {{ $data['pembayaran_terdekat']['nomor_pinjaman'] }} jatuh tempo {{ $data['pembayaran_terdekat']['tanggal'] }}
                    </p>
                    <p class="mt-1 text-2xl font-semibold text-warning-700 dark:text-warning-300">
                        {{ $formatRupiah($data['pembayaran_terdekat']['nominal']) }}
                    </p>
                </div>
            @else
                <p class="text-sm text-gray-600 dark:text-gray-300">Tidak ada angsuran yang harus dibayar.</p>
            @endif

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Sisa Pokok</p>
                    <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $formatRupiah($data['total_sisa_pokok']) }}</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Denda Belum Dibayar</p>
                    <p class="mt-1 text-lg font-semibold {{ $data['total_denda'] > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-950 dark:text-white' }}">
                        {{ $formatRupiah($data['total_denda']) }}
                    </p>
                </div>
            </div>

            <div class="mt-4 rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Kewajiban Simpanan Belum Dibayar</p>
                <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                    {{ $data['kewajiban']['jumlah'] }} kewajiban · {{ $formatRupiah($data['kewajiban']['total']) }}
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Kredit Aktif</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2">Nomor</th>
                            <th class="px-3 py-2 text-right">Plafon</th>
                            <th class="px-3 py-2 text-right">Sisa Pokok</th>
                            <th class="px-3 py-2">Angsuran Berikutnya</th>
                            <th class="px-3 py-2 text-right">Denda</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data['kredit_aktif'] as $row)
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-2">{{ $row['peminjaman']->loan_number }}</td>
                                <td class="px-3 py-2 text-right">{{ $formatRupiah($row['peminjaman']->loan_amount) }}</td>
                                <td class="px-3 py-2 text-right">{{ $formatRupiah($row['sisa_pokok']) }}</td>
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
                                <td class="px-3 py-4 text-center text-gray-500 dark:text-gray-400" colspan="5">
                                    Tidak ada kredit aktif.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Status Pengajuan</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2">Nomor</th>
                            <th class="px-3 py-2 text-right">Jumlah</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Diajukan</th>
                            <th class="px-3 py-2">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data['pengajuan'] as $row)
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-2">{{ $row['peminjaman']->loan_number }}</td>
                                <td class="px-3 py-2 text-right">{{ $formatRupiah($row['peminjaman']->loan_amount) }}</td>
                                <td class="px-3 py-2">{{ $row['status']?->name }}</td>
                                <td class="px-3 py-2">{{ $tanggal($row['diajukan']) }}</td>
                                <td class="px-3 py-2">{{ $row['alasan_penolakan'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-4 text-center text-gray-500 dark:text-gray-400" colspan="5">
                                    Tidak ada pengajuan dalam proses.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Riwayat Pinjaman</x-slot>
            <x-slot name="description">Pinjaman yang sudah lunas, ditolak, atau dibatalkan.</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2">Nomor</th>
                            <th class="px-3 py-2 text-right">Plafon</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Ditandai Lunas</th>
                            <th class="px-3 py-2 text-right">Sisa Pokok</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data['riwayat'] as $row)
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-2">{{ $row['peminjaman']->loan_number }}</td>
                                <td class="px-3 py-2 text-right">{{ $formatRupiah($row['peminjaman']->loan_amount) }}</td>
                                <td class="px-3 py-2">{{ $row['peminjaman']->status?->name }}</td>
                                <td class="px-3 py-2">{{ $row['peminjaman']->lunas_at?->toDateString() ?? '-' }}</td>
                                <td class="px-3 py-2 text-right">{{ $formatRupiah($row['sisa_pokok']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-4 text-center text-gray-500 dark:text-gray-400" colspan="5">
                                    Belum ada riwayat pinjaman.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Pembayaran Terakhir</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2">Tanggal</th>
                            <th class="px-3 py-2">Nomor Pinjaman</th>
                            <th class="px-3 py-2 text-right">Jumlah</th>
                            <th class="px-3 py-2">Jenis</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data['pembayaran'] as $row)
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-2">{{ $tanggal($row['tanggal']) }}</td>
                                <td class="px-3 py-2">{{ $row['nomor_pinjaman'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-right">{{ $formatRupiah($row['jumlah']) }}</td>
                                <td class="px-3 py-2">{{ $row['jenis']?->name }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-4 text-center text-gray-500 dark:text-gray-400" colspan="4">
                                    Belum ada pembayaran.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>