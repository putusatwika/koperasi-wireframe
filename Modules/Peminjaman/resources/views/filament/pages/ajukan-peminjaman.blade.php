<x-filament-panels::page>
    @if ($draftId === null)
        <form wire:submit="simulate" class="flex flex-col gap-4">
            <x-filament::section heading="Simulasi Pinjaman">
                @if (method_exists($this, 'anggotaOptions'))
                    <div class="fi-fo-field-wrp mb-6">
                        <label for="simulation-anggota-id" class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                            <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">Anggota<span class="text-danger-600">*</span></span>
                        </label>
                        <select
                            id="simulation-anggota-id"
                            wire:model.live="simulationAnggotaId"
                            required
                            class="fi-select-input block w-full rounded-lg border-gray-300 bg-white py-2.5 text-sm shadow-sm outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        >
                            <option value="">Pilih anggota</option>
                            @foreach ($this->anggotaOptions() as $anggotaId => $anggotaLabel)
                                <option value="{{ $anggotaId }}">{{ $anggotaLabel }}</option>
                            @endforeach
                        </select>
                        @error('simulationAnggotaId')
                            <p class="mt-2 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                {{ $this->simulationForm }}
            </x-filament::section>

            <x-filament::button type="submit">
                Hitung Simulasi
            </x-filament::button>
        </form>

        @if ($result)
            <x-filament::section heading="Hasil Simulasi" class="mt-6">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <div class="text-sm text-gray-500">Angsuran per bulan</div>
                        <div class="font-semibold">Rp {{ number_format($result['monthly_payment'], 2, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Total bunga</div>
                        <div class="font-semibold">Rp {{ number_format($result['total_interest'], 2, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Total pengembalian</div>
                        <div class="font-semibold">Rp {{ number_format($result['total_repayment'], 2, ',', '.') }}</div>
                    </div>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="px-3 py-3">Bulan</th>
                                <th class="px-3 py-3 text-right">Saldo Awal</th>
                                <th class="px-3 py-3 text-right">Pokok</th>
                                <th class="px-3 py-3 text-right">Bunga</th>
                                <th class="px-3 py-3 text-right">Total Bayar</th>
                                <th class="px-3 py-3 text-right">Saldo Akhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($result['schedule'] as $installment)
                                <tr class="border-b">
                                    <td class="px-3 py-3">{{ $installment['month'] }}</td>
                                    <td class="px-3 py-3 text-right">Rp {{ number_format($installment['opening_balance'], 2, ',', '.') }}</td>
                                    <td class="px-3 py-3 text-right">Rp {{ number_format($installment['principal'], 2, ',', '.') }}</td>
                                    <td class="px-3 py-3 text-right">Rp {{ number_format($installment['interest'], 2, ',', '.') }}</td>
                                    <td class="px-3 py-3 text-right font-semibold">Rp {{ number_format($installment['payment'], 2, ',', '.') }}</td>
                                    <td class="px-3 py-3 text-right">Rp {{ number_format($installment['ending_balance'], 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-slot name="footer">
                    <x-filament::button wire:click="continueApplication">
                        Lanjutkan ke Pengajuan
                    </x-filament::button>
                </x-slot>
            </x-filament::section>
        @endif
    @else
        <form wire:submit="submit" class="flex flex-col gap-4">
            <x-filament::section heading="Lengkapi Pengajuan Pinjaman">
                {{ $this->form }}
            </x-filament::section>

            <x-filament::button type="submit">
                Kirim Pengajuan
            </x-filament::button>
        </form>
    @endif
</x-filament-panels::page>
