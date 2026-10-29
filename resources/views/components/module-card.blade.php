@props(['module'])

@php
    use Illuminate\Support\Js;

    $disableAction = "mountAction('disableModule', ".Js::from(['moduleName' => $module['name']]).')';
    $viewAction = "mountAction('viewModule', ".Js::from(['moduleName' => $module['name']]).')';
@endphp

<x-filament::section
    class="h-full"
    :heading="$module['name']"
    icon="heroicon-o-puzzle-piece"
>
    <x-slot name="afterHeader">
        <x-filament::badge :color="! $module['fileAvailable'] ? 'danger' : ($module['enabled'] ? 'success' : 'gray')">
            {{ ! $module['fileAvailable'] ? 'Berkas hilang' : ($module['enabled'] ? 'Aktif' : 'Nonaktif') }}
        </x-filament::badge>
    </x-slot>

    <p class="text-sm text-gray-600 dark:text-gray-300">
        {{ filled($module['description']) ? $module['description'] : 'Belum ada deskripsi untuk modul ini.' }}
    </p>

    <x-slot name="footer">
        @if (! $module['fileAvailable'])
            <x-filament::button class="w-full" color="gray" disabled>
                Refresh untuk membersihkan
            </x-filament::button>
        @elseif ($module['enabled'])
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <x-filament::button
                    color="info"
                    :wire:click="$viewAction"
                >
                    Lihat Modul
                </x-filament::button>
                <x-filament::button
                    color="danger"
                    :wire:click="$disableAction"
                >
                    Nonaktifkan
                </x-filament::button>
            </div>
        @else
            <x-filament::button
                class="w-full"
                :wire:click="$viewAction"
            >
                Lihat Modul
            </x-filament::button>
        @endif
    </x-slot>
</x-filament::section>
