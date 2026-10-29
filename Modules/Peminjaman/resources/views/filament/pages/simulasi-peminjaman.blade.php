<x-filament-panels::page>
    <style>
        .simulation-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .simulation-schedule {
            border-collapse: separate;
            border-spacing: 0;
            color: #111827;
        }

        .simulation-schedule th,
        .simulation-schedule td {
            border-bottom: 1px solid #d1d5db;
            border-right: 1px solid #d1d5db;
        }

        .simulation-schedule th {
            background-color: #e5e7eb;
            color: #111827;
        }

        .simulation-schedule tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .simulation-schedule tbody tr:nth-child(even) {
            background-color: #f3f4f6;
        }

        .simulation-schedule th:last-child,
        .simulation-schedule td:last-child {
            border-right: 0;
        }

        .dark .simulation-schedule {
            color: #f9fafb;
        }

        .dark .simulation-schedule th {
            background-color: #374151;
            color: #f9fafb;
        }

        .dark .simulation-schedule th,
        .dark .simulation-schedule td {
            border-color: #4b5563;
        }

        .dark .simulation-schedule tbody tr:nth-child(odd) {
            background-color: #18181b;
        }

        .dark .simulation-schedule tbody tr:nth-child(even) {
            background-color: #27272a;
        }

        @media (max-width: 767px) {
            .simulation-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <form wire:submit="simulate" class="flex flex-col gap-4">
        {{ $this->form }}

        <div class="mt-4" style="margin-top: 1rem;">
            <x-filament::button type="submit">
                Hitung Simulasi
            </x-filament::button>
        </div>
    </form>

    @if ($result)
        <x-filament::section heading="Hasil Simulasi">
            <div class="simulation-summary">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Angsuran awal</div>
                    <div class="font-semibold">Rp {{ number_format($result['monthly_payment'], 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total bunga</div>
                    <div class="font-semibold">Rp {{ number_format($result['total_interest'], 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total pengembalian</div>
                    <div class="font-semibold">Rp {{ number_format($result['total_repayment'], 2, ',', '.') }}</div>
                </div>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="simulation-schedule w-full min-w-[900px] text-sm">
                    <thead>
                        <tr class="text-left">
                            <th class="px-3 py-3">Bulan</th>
                            <th class="px-3 py-3 text-right">Saldo Awal</th>
                            <th class="px-3 py-3 text-right">Pokok</th>
                            <th class="px-3 py-3 text-right">Bunga</th>
                            <th class="px-3 py-3 text-right">Total Bayar</th>
                            <th class="px-3 py-3 text-right">Saldo Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($result['schedule'] as $installment)
                            <tr>
                                <td class="px-3 py-3">{{ $installment['month'] }}</td>
                                <td class="px-3 py-3 text-right">Rp {{ number_format($installment['opening_balance'], 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right">Rp {{ number_format($installment['principal'], 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right">Rp {{ number_format($installment['interest'], 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right font-semibold">Rp {{ number_format($installment['payment'], 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right">Rp {{ number_format($installment['ending_balance'], 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <x-filament::button type="button" wire:click="continueApplication">
                    Lanjutkan ke Pengajuan
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
