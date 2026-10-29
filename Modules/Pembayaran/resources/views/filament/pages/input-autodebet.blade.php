<x-filament-panels::page>
    <form wire:submit="import" class="flex flex-col gap-6">
        {{ $this->form }}

        <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray">
            Import Autodebet
        </x-filament::button>
    </form>

    <div class="mt-6 rounded-xl border border-gray-200 p-4 text-sm dark:border-white/10">
        <p class="font-medium">Format kolom Excel</p>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            Wajib: <code>nomor_pinjaman</code>, <code>nomor_anggota</code>, dan <code>jumlah</code>.
            Opsional: <code>tanggal_pembayaran</code>, <code>angsuran_ke</code>, <code>pokok</code>,
            <code>bunga</code>, <code>denda</code>, <code>nomor_referensi</code>, dan <code>catatan</code>.
        </p>
    </div>
</x-filament-panels::page>
