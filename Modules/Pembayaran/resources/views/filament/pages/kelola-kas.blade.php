<x-filament-panels::page>
    @php
        $formatRupiah = fn (?string $value): string => 'Rp '.number_format((float) ($value ?? 0), 2, ',', '.');
    @endphp

    <x-filament::section>
        <x-slot name="heading">Ringkasan Kas</x-slot>
        <x-slot name="description">Informasi jumlah kas yang masih tersisa pada koperasi.</x-slot>

        @if (! ($summary['cash_account_configured'] ?? false))
            <p class="mb-4 text-sm text-danger-600 dark:text-danger-400">
                Akun kas (COA Pembayaran) belum dikonfigurasi, sehingga mutasi kas belum dapat dihitung.
            </p>
        @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Kas di Awal</p>
                <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                    {{ $formatRupiah($summary['opening_balance'] ?? '0') }}
                </p>
            </div>

            <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Kas Masuk</p>
                <p class="mt-1 text-lg font-semibold text-success-600 dark:text-success-400">
                    {{ $formatRupiah($summary['total_in'] ?? '0') }}
                </p>
            </div>

            <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Kas Keluar</p>
                <p class="mt-1 text-lg font-semibold text-danger-600 dark:text-danger-400">
                    {{ $formatRupiah($summary['total_out'] ?? '0') }}
                </p>
            </div>

            <div class="rounded-xl bg-primary-50 p-4 dark:bg-primary-500/10">
                <p class="text-sm text-primary-600 dark:text-primary-400">Sisa Kas</p>
                <p class="mt-1 text-lg font-semibold text-primary-700 dark:text-primary-300">
                    {{ $formatRupiah($summary['remaining'] ?? '0') }}
                </p>
            </div>
        </div>
    </x-filament::section>

    <form wire:submit="save" class="flex flex-col gap-6">
        {{ $this->form }}

        <x-filament::button type="submit" icon="heroicon-o-banknotes">
            Simpan Kas di Awal
        </x-filament::button>
    </form>
</x-filament-panels::page>
