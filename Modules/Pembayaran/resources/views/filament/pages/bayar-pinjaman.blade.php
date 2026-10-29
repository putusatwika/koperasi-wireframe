<x-filament-panels::page>
    <form wire:submit="submit" class="flex flex-col gap-6">
        {{ $this->form }}

        <x-filament::button type="submit" icon="heroicon-o-banknotes">
            Simpan Pembayaran
        </x-filament::button>
    </form>
</x-filament-panels::page>
