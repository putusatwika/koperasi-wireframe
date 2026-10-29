<x-filament-panels::page>
    @if ($draftId === null)
        <form wire:submit="simulate" class="flex flex-col gap-4">
            <x-filament::section heading="Simulasi Pinjaman Anggota">
                {{ $this->simulationForm }}
            </x-filament::section>

            <x-filament::button type="submit">Hitung Simulasi</x-filament::button>
        </form>

        @if ($result)
            <x-filament::section heading="Hasil Simulasi" class="mt-6">
                <div class="grid gap-4 md:grid-cols-3">
                    <div><div class="text-sm text-gray-500">Angsuran per bulan</div><div class="font-semibold">Rp {{ number_format($result['monthly_payment'], 2, ',', '.') }}</div></div>
                    <div><div class="text-sm text-gray-500">Total bunga</div><div class="font-semibold">Rp {{ number_format($result['total_interest'], 2, ',', '.') }}</div></div>
                    <div><div class="text-sm text-gray-500">Total pengembalian</div><div class="font-semibold">Rp {{ number_format($result['total_repayment'], 2, ',', '.') }}</div></div>
                </div>
                <div class="mt-6 overflow-x-auto">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead><tr class="border-b text-left"><th class="px-3 py-3">Bulan</th><th class="px-3 py-3 text-right">Saldo Awal</th><th class="px-3 py-3 text-right">Pokok</th><th class="px-3 py-3 text-right">Bunga</th><th class="px-3 py-3 text-right">Total Bayar</th><th class="px-3 py-3 text-right">Saldo Akhir</th></tr></thead>
                        <tbody>
                            @foreach ($result['schedule'] as $installment)
                                <tr class="border-b"><td class="px-3 py-3">{{ $installment['month'] }}</td><td class="px-3 py-3 text-right">Rp {{ number_format($installment['opening_balance'], 2, ',', '.') }}</td><td class="px-3 py-3 text-right">Rp {{ number_format($installment['principal'], 2, ',', '.') }}</td><td class="px-3 py-3 text-right">Rp {{ number_format($installment['interest'], 2, ',', '.') }}</td><td class="px-3 py-3 text-right font-semibold">Rp {{ number_format($installment['payment'], 2, ',', '.') }}</td><td class="px-3 py-3 text-right">Rp {{ number_format($installment['ending_balance'], 2, ',', '.') }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-slot name="footer"><x-filament::button wire:click="continueApplication">Lanjutkan ke Pengajuan</x-filament::button></x-slot>
            </x-filament::section>
        @endif
    @else
        <form wire:submit="submit" class="flex flex-col gap-4">
            <x-filament::section heading="Lengkapi Pengajuan Pinjaman Anggota">{{ $this->form }}</x-filament::section>
            <x-filament::button type="submit">Kirim Pengajuan</x-filament::button>
        </form>
    @endif
</x-filament-panels::page>
