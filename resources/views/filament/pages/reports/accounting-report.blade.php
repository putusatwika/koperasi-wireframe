<x-filament-panels::page>
    <form wire:submit="generateReport" class="flex flex-col gap-6">
        {{ $this->form }}

        <div class="mt-5">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                Generate Report
            </x-filament::button>
        </div>
    </form>

    @if (filled($report))
        <x-filament::section :heading="$report['report_group_name']">
            <x-slot name="description">
                Periode {{ $report['from'] }} sampai {{ $report['to'] }} — transaksi POSTED
            </x-slot>

            @php($groupedRows = collect($report['rows'])->groupBy('account_type'))

            <div class="overflow-x-auto rounded-lg" style="border: 1px solid rgba(156, 163, 175, 0.35);">
                <table class="min-w-[1100px] w-full text-sm" style="border-collapse: collapse;">
                    <thead>
                        <tr class="text-left" style="background: rgba(156, 163, 175, 0.12);">
                            <th class="whitespace-nowrap" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">Code</th>
                            <th class="whitespace-nowrap" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">Account</th>
                            <th class="whitespace-nowrap" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">Account Type</th>
                            <th class="whitespace-nowrap" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">Normal Balance</th>
                            <th class="whitespace-nowrap text-right" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">Debit</th>
                            <th class="whitespace-nowrap text-right" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">Credit</th>
                            <th class="whitespace-nowrap text-right" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($groupedRows as $accountType => $rows)
                            <tr class="font-semibold uppercase tracking-wide">
                                <td colspan="7" style="padding: 12px 16px; border-top: 2px solid rgba(156, 163, 175, 0.55); border-bottom: 1px solid rgba(156, 163, 175, 0.35); background: rgba(156, 163, 175, 0.16);">{{ $accountType }}</td>
                            </tr>

                            @foreach ($rows as $row)
                                <tr>
                                    <td class="whitespace-nowrap font-medium" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.25);">{{ $row['code'] }}</td>
                                    <td style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.25);">{{ $row['name'] }}</td>
                                    <td class="whitespace-nowrap" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.25);">{{ $row['account_type'] }}</td>
                                    <td class="whitespace-nowrap" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.25);">{{ $row['normal_balance'] }}</td>
                                    <td class="whitespace-nowrap text-right" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.25);">Rp {{ $row['debit'] }}</td>
                                    <td class="whitespace-nowrap text-right" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.25);">Rp {{ $row['credit'] }}</td>
                                    <td class="whitespace-nowrap text-right font-medium" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.25);">Rp {{ $row['balance'] }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-gray-500" style="padding: 24px 16px; border: 1px solid rgba(156, 163, 175, 0.25);">No COA accounts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="font-semibold" style="background: rgba(156, 163, 175, 0.12);">
                            <td colspan="4" class="text-right" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">Total</td>
                            <td class="whitespace-nowrap text-right" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">Rp {{ $report['totals']['debit'] }}</td>
                            <td class="whitespace-nowrap text-right" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">Rp {{ $report['totals']['credit'] }}</td>
                            <td class="whitespace-nowrap text-right" style="padding: 12px 16px; border: 1px solid rgba(156, 163, 175, 0.35);">Rp {{ $report['totals']['balance'] }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
