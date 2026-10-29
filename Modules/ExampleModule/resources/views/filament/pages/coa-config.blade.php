<x-filament-panels::page>
    <form wire:submit="save" class="flex flex-col gap-6">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit" wire:loading.attr="disabled">
                Simpan
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
