<x-filament-panels::page>
    <form wire:submit="submit" class="flex flex-col gap-6">
        {{ $this->form }}

        <x-filament::button
            type="submit"
            icon="heroicon-o-arrow-up-tray"
            wire:confirm="Pastikan produk, saldo, dan nominal penarikan sudah benar. Lanjutkan proses penarikan?"
        >
            Proses Penarikan
        </x-filament::button>
    </form>
</x-filament-panels::page>
