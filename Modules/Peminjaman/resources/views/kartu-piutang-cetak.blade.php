<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Piutang {{ $peminjaman->loan_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111; margin: 24px; }
        h1 { font-size: 16px; margin: 0; }
        h2 { font-size: 14px; margin: 0 0 12px; text-align: center; letter-spacing: 1px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 16px; }
        .header .right { text-align: right; }
        .info { display: flex; gap: 24px; margin-bottom: 16px; }
        .info > div { flex: 1; }
        .info table { width: 100%; border-collapse: collapse; }
        .info td { padding: 2px 0; vertical-align: top; }
        .info td:first-child { width: 38%; color: #444; }
        table.grid { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.grid th, table.grid td { border: 1px solid #999; padding: 4px 6px; }
        table.grid th { background: #eee; text-align: left; }
        .num { text-align: right; }
        .summary { display: flex; gap: 24px; margin-bottom: 24px; }
        .summary > div { flex: 1; border: 1px solid #999; padding: 8px; }
        .summary .label { font-size: 10px; color: #444; }
        .summary .value { font-size: 14px; font-weight: bold; }
        .footer { display: flex; justify-content: space-between; margin-top: 40px; }
        .footer .ttd { text-align: center; width: 220px; }
        .footer .line { border-top: 1px solid #111; margin-top: 48px; padding-top: 4px; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>{{ config('app.name') }}</h1>
            <div>Kartu Piutang Anggota</div>
        </div>
        <div class="right">
            <strong>{{ $peminjaman->loan_number }}</strong><br>
            Status: {{ $peminjaman->status?->name }}
        </div>
    </div>

    <h2>KARTU PIUTANG</h2>

    <div class="info">
        <div>
            <table>
                <tr><td>Nama Anggota</td><td>: {{ $peminjaman->anggota?->user?->name }}</td></tr>
                <tr><td>Nomor Anggota</td><td>: {{ $peminjaman->anggota?->member_number }}</td></tr>
                <tr><td>Nomor Rekening</td><td>: {{ $peminjaman->account_number }}</td></tr>
            </table>
        </div>
        <div>
            <table>
                <tr><td>Plafon Pinjaman</td><td>: Rp {{ number_format((float) $peminjaman->loan_amount, 2, ',', '.') }}</td></tr>
                <tr><td>Bunga per Bulan</td><td>: {{ number_format((float) $peminjaman->interest_rate / 12, 2, ',', '.') }}% ({{ $peminjaman->interest_type?->name }})</td></tr>
                <tr><td>Tenor</td><td>: {{ $peminjaman->term_months }} bulan</td></tr>
                <tr><td>Tanggal Pencairan</td><td>: {{ $peminjaman->disbursed_at?->toDateString() ?? '-' }}</td></tr>
            </table>
        </div>
    </div>

    <table class="grid">
        <thead>
            <tr>
                <th>Ke</th>
                <th>Jatuh Tempo</th>
                <th class="num">Pokok</th>
                <th class="num">Bunga</th>
                <th class="num">Total</th>
                <th class="num">Dibayar</th>
                <th class="num">Denda</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php($installment = $row['installment'])
                <tr>
                    <td>{{ $installment->angsuran_ke }}</td>
                    <td>{{ $installment->jatuh_tempo->toDateString() }}</td>
                    <td class="num">{{ number_format((float) $installment->pokok, 2, ',', '.') }}</td>
                    <td class="num">{{ number_format((float) $installment->bunga, 2, ',', '.') }}</td>
                    <td class="num">{{ number_format((float) $installment->total, 2, ',', '.') }}</td>
                    <td class="num">{{ number_format((float) $installment->dibayar_pokok + (float) $installment->dibayar_bunga, 2, ',', '.') }}</td>
                    <td class="num">{{ $row['denda'] > 0 ? number_format($row['denda'], 2, ',', '.') : '-' }}</td>
                    <td>{{ $installment->status?->name }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;">Tidak ada jadwal angsuran.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary">
        <div>
            <div class="label">Sisa Pokok</div>
            <div class="value">Rp {{ number_format($sisaPokok, 2, ',', '.') }}</div>
        </div>
        <div>
            <div class="label">Denda Belum Dibayar</div>
            <div class="value">Rp {{ number_format($denda, 2, ',', '.') }}</div>
        </div>
        <div>
            <div class="label">Penalti Pelunasan Dipercepat ({{ (float) $peminjaman->penalti_pelunasan_persen }}% sisa pokok)</div>
            <div class="value">Rp {{ number_format($sisaPokok * (float) $peminjaman->penalti_pelunasan_persen / 100, 2, ',', '.') }}</div>
        </div>
    </div>

    <div class="footer">
        <div class="ttd">
            <div>Dicetak oleh</div>
            <div class="line">{{ $dicetakOleh }}</div>
            <div>{{ now()->translatedFormat('d F Y') }}</div>
        </div>
        <div class="ttd">
            <div>Bendahara Koperasi</div>
            <div class="line">&nbsp;</div>
            <div>&nbsp;</div>
        </div>
    </div>

    <script>
        window.onload = function () { window.print(); };
    </script>
</body>
</html>