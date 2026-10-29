<x-filament-panels::page>
    <form wire:submit="save" class="flex flex-col gap-6">
        {{ $this->form }}

        <x-filament::button type="submit">Simpan Konfigurasi COA</x-filament::button>
    </form>
</x-filament-panels::page>
