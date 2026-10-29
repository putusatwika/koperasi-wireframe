<x-filament-panels::page>
    @if ($installedModules->isNotEmpty())
        <x-filament::section :heading="'Modul Terpasang ('.$installedModules->count().')'">
            <div {{ $gridAttributes }}>
                @foreach ($installedModules as $module)
                    <x-module-card :$module />
                @endforeach
            </div>
        </x-filament::section>
    @endif

    @if ($availableModules->isNotEmpty())
        <x-filament::section :heading="'Modul Belum Terpasang ('.$availableModules->count().')'">
            <div {{ $gridAttributes }}>
                @foreach ($availableModules as $module)
                    <x-module-card :$module />
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
