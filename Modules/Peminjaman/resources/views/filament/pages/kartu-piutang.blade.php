<x-filament-panels::page>
    @php
        $formatRupiah = fn (mixed $value): string => 'Rp '.number_format((float) $value, 2, ',', '.');
    @endphp

    @forelse ($loans as $loan)
        <x-filament::section>
            <x-slot name="heading">Pinjaman {{ $loan['peminjaman']->loan_number }}</x-slot>
            <x-slot name="description">
                Plafon {{ $formatRupiah($loan['peminjaman']->loan_amount) }} · Tenor {{ $loan['peminjaman']->term_months }} bulan · Status {{ $loan['peminjaman']->status?->name }}
            </x-slot>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sisa Pokok</p>
                    <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $formatRupiah($loan['sisa_pokok']) }}</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pembayaran Terdekat</p>
                    <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                        @if ($loan['next'])
                            {{ $formatRupiah($loan['next']->total) }} · {{ $loan['next']->jatuh_tempo->toDateString() }}
                        @else
                            Lunas
                        @endif
                    </p>
                </div>
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tunggakan</p>
                    <p class="mt-1 text-lg font-semibold {{ $loan['tunggakan'] > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-950 dark:text-white' }}">
                        {{ $loan['tunggakan'] }} angsuran
                    </p>
                </div>
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Denda Belum Dibayar</p>
                    <p class="mt-1 text-lg font-semibold {{ $loan['denda'] > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-gray-950 dark:text-white' }}">
                        {{ $formatRupiah($loan['denda']) }}
                    </p>
                </div>
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Penalti Pelunasan Dipercepat</p>
                    <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                        {{ $formatRupiah($loan['penalti']) }}
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            ({{ (float) $loan['peminjaman']->penalti_pelunasan_persen }}% sisa pokok)
                        </span>
                    </p>
                </div>
            </div>

            <div class="mt-6 overflow-x-auto">
                <div class="mb-3 flex justify-end">
                    <a href="{{ route('module.peminjaman.kartu-piutang.cetak', $loan['peminjaman']) }}"
                       target="_blank"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.4 42.4 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096 2.947 4.171m-2.947-4.171c-3.437 0-6.875.5-10.136 1.5M12 15.75v-3.75m0 3.75a42.4 42.4 0 0 1-10.56 0m12.156 0 2.947 4.171" />
                        </svg>
                        Cetak Kartu Piutang
                    </a>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2">Ke</th>
                            <th class="px-3 py-2">Jatuh Tempo</th>
                            <th class="px-3 py-2 text-right">Pokok</th>
                            <th class="px-3 py-2 text-right">Bunga</th>
                            <th class="px-3 py-2 text-right">Total</th>
                            <th class="px-3 py-2 text-right">Dibayar</th>
                            <th class="px-3 py-2 text-right">Denda</th>
                            <th class="px-3 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($loan['schedule'] as $row)
                            @php($installment = $row['installment'])
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-2">{{ $installment->angsuran_ke }}</td>
                                <td class="px-3 py-2">{{ $installment->jatuh_tempo->toDateString() }}</td>
                                <td class="px-3 py-2 text-right">{{ $formatRupiah($installment->pokok) }}</td>
                                <td class="px-3 py-2 text-right">{{ $formatRupiah($installment->bunga) }}</td>
                                <td class="px-3 py-2 text-right">{{ $formatRupiah($installment->total) }}</td>
                                <td class="px-3 py-2 text-right">
                                    {{ $formatRupiah((float) $installment->dibayar_pokok + (float) $installment->dibayar_bunga) }}
                                </td>
                                <td class="px-3 py-2 text-right">{{ $row['denda'] > 0 ? $formatRupiah($row['denda']) : '-' }}</td>
                                <td class="px-3 py-2">{{ $installment->status?->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @empty
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-300">Belum ada pinjaman aktif.</p>
        </x-filament::section>
    @endforelse
</x-filament-panels::page>
