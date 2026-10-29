<x-filament-panels::page>
    <x-filament::section heading="Pencairan Pinjaman">
        <div>
            @forelse ($peminjamans as $peminjaman)
                <div>
                    {{ $peminjaman->loan_number }} - {{ $peminjaman->anggota->user->name }} -
                    Rp {{ number_format((float) $peminjaman->loan_amount, 2, ',', '.') }}
                </div>
            @empty
                <p>Belum ada pinjaman yang dicairkan.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-panels::page>
