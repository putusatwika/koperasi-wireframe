<x-filament-panels::page>
    <form wire:submit="generateReport" class="flex flex-col gap-6">
        {{ $this->form }}

        <div class="mt-5 flex flex-wrap items-center gap-3">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                Tampilkan Laporan
            </x-filament::button>

            @if (filled($report))
                <x-filament::button
                    tag="a"
                    :href="route('reports.laba-rugi.excel', ['from' => $report['from'], 'to' => $report['to']])"
                    icon="heroicon-o-arrow-down-tray"
                    color="success"
                >
                    Unduh Excel
                </x-filament::button>
            @endif
        </div>
    </form>

    @if (filled($report))
        @php($formatRupiah = static fn (string $amount): string => 'Rp '.number_format((float) $amount, 2, ',', '.'))
        @php($labaRugi = (float) $report['laba_rugi'])

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl p-4" style="border: 1px solid rgba(156, 163, 175, 0.35);">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Pendapatan</p>
                <p class="mt-1 text-xl font-semibold" style="color: #059669;">
                    {{ $formatRupiah($report['total_pendapatan']) }}
                </p>
            </div>

            <div class="rounded-xl p-4" style="border: 1px solid rgba(156, 163, 175, 0.35);">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Beban</p>
                <p class="mt-1 text-xl font-semibold" style="color: #dc2626;">
                    {{ $formatRupiah($report['total_beban']) }}
                </p>
            </div>

            <div class="rounded-xl p-4" style="border: 1px solid rgba(156, 163, 175, 0.35);">
                <p class="text-sm text-gray-500 dark:text-gray-400">Laba / (Rugi) Bersih</p>
                <p class="mt-1 text-xl font-semibold" style="color: {{ $labaRugi < 0 ? '#dc2626' : '#059669' }};">
                    {{ $formatRupiah($report['laba_rugi']) }}
                </p>
            </div>
        </div>

        <x-filament::section heading="Laporan Laba Rugi">
            <x-slot name="description">
                Periode {{ $report['from'] }} sampai {{ $report['to'] }} — hanya jurnal POSTED, digabung per akun induk 2 digit.
            </x-slot>

            <div class="overflow-x-auto rounded-lg" style="border: 1px solid rgba(156, 163, 175, 0.35);">
                <table class="min-w-[720px] w-full text-sm" style="border-collapse: collapse;">
                    <thead>
                        <tr class="text-left" style="background: rgba(156, 163, 175, 0.12);">
                            <th class="whitespace-nowrap" style="width: 120px; padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">Kode</th>
                            <th style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">Nama Akun</th>
                            <th class="whitespace-nowrap text-right" style="width: 180px; padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="font-semibold uppercase tracking-wide">
                            <td colspan="3" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35); background: rgba(156, 163, 175, 0.16);">
                                Pendapatan
                            </td>
                        </tr>

                        @forelse ($report['pendapatan'] as $row)
                            <tr>
                                <td class="whitespace-nowrap" style="padding: 10px 16px; border: 1px solid rgba(156, 163, 175, 0.25);">{{ $row['code'] }}</td>
                                <td style="padding: 10px 16px; border: 1px solid rgba(156, 163, 175, 0.25);">{{ $row['name'] }}</td>
                                <td class="whitespace-nowrap text-right" style="padding: 10px 16px; border: 1px solid rgba(156, 163, 175, 0.25);">{{ $formatRupiah($row['amount']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-gray-500" style="padding: 16px; border: 1px solid rgba(156, 163, 175, 0.25);">
                                    Tidak ada pendapatan pada periode ini.
                                </td>
                            </tr>
                        @endforelse

                        <tr class="font-semibold">
                            <td colspan="2" class="text-right" style="padding: 10px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">Total Pendapatan</td>
                            <td class="whitespace-nowrap text-right" style="padding: 10px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">{{ $formatRupiah($report['total_pendapatan']) }}</td>
                        </tr>

                        <tr class="font-semibold uppercase tracking-wide">
                            <td colspan="3" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35); background: rgba(156, 163, 175, 0.16);">
                                Beban
                            </td>
                        </tr>

                        @forelse ($report['beban'] as $row)
                            <tr>
                                <td class="whitespace-nowrap" style="padding: 10px 16px; border: 1px solid rgba(156, 163, 175, 0.25);">{{ $row['code'] }}</td>
                                <td style="padding: 10px 16px; border: 1px solid rgba(156, 163, 175, 0.25);">{{ $row['name'] }}</td>
                                <td class="whitespace-nowrap text-right" style="padding: 10px 16px; border: 1px solid rgba(156, 163, 175, 0.25);">{{ $formatRupiah($row['amount']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-gray-500" style="padding: 16px; border: 1px solid rgba(156, 163, 175, 0.25);">
                                    Tidak ada beban pada periode ini.
                                </td>
                            </tr>
                        @endforelse

                        <tr class="font-semibold">
                            <td colspan="2" class="text-right" style="padding: 10px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">Total Beban</td>
                            <td class="whitespace-nowrap text-right" style="padding: 10px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">{{ $formatRupiah($report['total_beban']) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="font-bold" style="background: rgba(156, 163, 175, 0.12);">
                            <td colspan="2" class="text-right" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">
                                Laba / (Rugi) Bersih
                            </td>
                            <td class="whitespace-nowrap text-right" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35); color: {{ $labaRugi < 0 ? '#dc2626' : '#059669' }};">
                                {{ $formatRupiah($report['laba_rugi']) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
