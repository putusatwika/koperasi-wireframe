"""Dummy data + perhitungan angsuran (bunga tetap / menurun / anuitas).

Semua angka di halaman dihitung di sini supaya konsisten antar halaman
(tidak ada angka karangan yang berbeda-beda antar halaman).
"""
from ksu_core import money, jt, pct, pctf, badge, st, tbl

# --------------------------------------------------------------------- profil & kas
KAS = dict(
    saldo_kas=412_750_000,
    kas_terikat=75_000_000,
    piutang_berjalan=1_245_600_000,
    piutang_macet=18_400_000,
    jumlah_anggota=386,
    simpanan_anggota=1_780_400_000,
    autodebet_berhasil=207,
    autodebet_gagal=7,
    pengajuan_menunggu=5,
)

ANGGOTA = dict(
    nama="Budi Santoso, S.Kom., M.T.",
    no_anggota="AG-0142",
    nik="7371 0201 0288 0004",
    unit="Fakultas Teknik — Prodi Informatika",
    masakerja="Anggota sejak 12 Maret 2019",
    telepon="0812-4455-7788",
    email="budi.santoso@koperasi.example.id",
    rekening="BSI 7100 2299 441 (autodebet)",
    simpanan_pokok=500_000,
    simpanan_wajib=12_500_000,
    simpanan_sukarela=2_000_000,
)

# --------------------------------------------------------------------- produk pinjaman
PRODUK = [
    dict(kode="PJ-RG", nama="Pinjaman Reguler", bunga=1.10, metode="Saldo Menurun (efektif)",
         tenor="12 – 36 bulan", plafon=50_000_000, denda=0.50, penalti=1.0, kolateral="Tanpa kolateral (potong gaji / autodebet)"),
    dict(kode="PJ-MG", nama="Pinjaman Multiguna (Kendaraan)", bunga=1.35, metode="Anuitas",
         tenor="24 – 48 bulan", plafon=150_000_000, denda=0.50, penalti=2.0, kolateral="BPKB + form kesediaan autodebet"),
    dict(kode="PJ-PD", nama="Pinjaman Pendidikan", bunga=1.00, metode="Saldo Menurun (efektif)",
         tenor="12 – 24 bulan", plafon=20_000_000, denda=0.50, penalti=0.5, kolateral="Tanpa kolateral"),
    dict(kode="PJ-DR", nama="Pinjaman Darurat", bunga=0.90, metode="Bunga Tetap (flat)",
         tenor="6 – 12 bulan", plafon=5_000_000, denda=0.30, penalti=0.0, kolateral="Tanpa kolateral"),
]


def produk(kode):
    return next(p for p in PRODUK if p["kode"] == kode)


# --------------------------------------------------------------------- jadwal angsuran
def sched(kind, P, n, i):
    """kind: flat | menurun | anuitas. i = bunga per bulan (desimal)."""
    rows, saldo = [], float(P)
    if kind == "flat":
        pokok, bunga = P / n, P * i
        for k in range(1, n + 1):
            sa = saldo
            sk = max(sa - pokok, 0)
            rows.append(dict(k=k, sa=sa, pokok=pokok, bunga=bunga, angs=pokok + bunga, sk=sk))
            saldo = sk
    elif kind == "menurun":
        pokok = P / n
        for k in range(1, n + 1):
            sa = saldo
            bu = sa * i
            pk = min(pokok, sa)
            sk = sa - pk
            rows.append(dict(k=k, sa=sa, pokok=pk, bunga=bu, angs=pk + bu, sk=sk))
            saldo = sk
    else:  # anuitas
        A = P * i / (1 - (1 + i) ** -n)
        for k in range(1, n + 1):
            sa = saldo
            bu = sa * i
            pk = min(A - bu, sa)
            sk = sa - pk
            rows.append(dict(k=k, sa=sa, pokok=pk, bunga=bu, angs=pk + bu, sk=sk))
            saldo = sk
    # pembulatan rupiah penuh + pembersihan sisa pembulatan pada baris terakhir
    for r in rows:
        r["sa"] = round(r["sa"]); r["pokok"] = round(r["pokok"])
        r["bunga"] = round(r["bunga"]); r["angs"] = round(r["angs"]); r["sk"] = round(r["sk"])
    return rows


def _irr_monthly(rows, P):
    lo, hi = 1e-9, 0.5
    for _ in range(200):
        mid = (lo + hi) / 2
        npv = sum(r["angs"] / (1 + mid) ** r["k"] for r in rows) - P
        if npv > 0:
            lo = mid
        else:
            hi = mid
    return (lo + hi) / 2


def ringkas(kind, P, n, i):
    rows = sched(kind, P, n, i)
    tot_bunga = sum(r["bunga"] for r in rows)
    tot_bayar = sum(r["angs"] for r in rows)
    r_eff = _irr_monthly(rows, P)
    return dict(
        rows=rows, angs_min=min(r["angs"] for r in rows), angs_max=max(r["angs"] for r in rows),
        tot_bunga=tot_bunga, tot_bayar=tot_bayar, r_eff_bulan=r_eff,
        r_eff_tahun=(1 + r_eff) ** 12 - 1,
    )


# skenario yang dipakai konsisten di seluruh wireframe
SKENARIO = dict(P=25_000_000, n=24, i=0.011, kode="PJ-RG", keperluan="Biaya pendidikan anak (S2)")
SIM = {
    "flat": ringkas("flat", SKENARIO["P"], SKENARIO["n"], SKENARIO["i"]),
    "menurun": ringkas("menurun", SKENARIO["P"], SKENARIO["n"], SKENARIO["i"]),
    "anuitas": ringkas("anuitas", SKENARIO["P"], SKENARIO["n"], SKENARIO["i"]),
}
LABEL_METODE = {"flat": "Bunga Tetap (flat)", "menurun": "Bunga Menurun (efektif)", "anuitas": "Bunga Anuitas"}


def tabel_jadwal(kind, limit=None, mulai=1):
    """Baris tabel jadwal angsuran untuk halaman simulasi/kartu piutang."""
    r = SIM[kind]["rows"]
    sel = [x for x in r if x["k"] >= mulai]
    if limit:
        sel = sel[:limit]
    out = []
    for x in sel:
        out.append([
            f'<span class="fw-semibold">{x["k"]}</span>',
            money(x["sa"]), money(x["pokok"]), money(x["bunga"]),
            f'<span class="fw-semibold">{money(x["angs"])}</span>', money(x["sk"]),
        ])
    return out


def tabel_compare(rows_extra=None):
    """Tabel pembanding 3 metode bunga untuk skenario yang sama."""
    out = []
    for k in ("flat", "menurun", "anuitas"):
        s = SIM[k]
        out.append([
            f'<span class="fw-semibold">{LABEL_METODE[k]}</span>',
            money(s["angs_min"]) + " – " + money(s["angs_max"]),
            money(s["tot_bunga"]), money(s["tot_bayar"]),
            pctf(s["r_eff_bulan"]) + " <span class='text-body-secondary small'>/bln</span>",
            st(pctf(s["r_eff_tahun"]) + " p.a."),
        ])
    return out


# --------------------------------------------------------------------- pengajuan
PENGAJUAN = [
    dict(id="PGJ-2026-0088", nama="Budi Santoso", anggota="AG-0142", pokok=25_000_000, tenor=24,
         produk="PJ-RG", keperluan="Biaya pendidikan anak (S2)", urgensi="Tinggi",
         tanggal="06 Okt 2026", status="Direkomendasikan", sifat="Autodebet", dok="6/6", kas_ok=True),
    dict(id="PGJ-2026-0087", nama="Ratna Kusumawati", anggota="AG-0231", pokok=120_000_000, tenor=36,
         produk="PJ-MG", keperluan="Beli mobil untuk mobilitas kerja", urgensi="Sedang",
         tanggal="05 Okt 2026", status="Menunggu Persetujuan", sifat="Autodebet", dok="6/6", kas_ok=True),
    dict(id="PGJ-2026-0086", nama="Ahmad Fauzi", anggota="AG-0117", pokok=5_000_000, tenor=10,
         produk="PJ-DR", keperluan="Biaya pengobatan orang tua", urgensi="Tinggi",
         tanggal="05 Okt 2026", status="Disetujui", sifat="Bayar Bulanan", dok="5/6", kas_ok=True),
    dict(id="PGJ-2026-0085", nama="Sri Handayani", anggota="AG-0305", pokok=18_000_000, tenor=18,
         produk="PJ-PD", keperluan="Renovasi ringan rumah", urgensi="Rendah",
         tanggal="04 Okt 2026", status="Menunggu Persetujuan", sifat="Autodebet", dok="4/6", kas_ok=False),
    dict(id="PGJ-2026-0084", nama="Joko Prasetyo", anggota="AG-0098", pokok=40_000_000, tenor=30,
         produk="PJ-RG", keperluan="Tambah modal usaha keluarga", urgensi="Rendah",
         tanggal="02 Okt 2026", status="Ditolak", sifat="Autodebet", dok="6/6", kas_ok=True),
    dict(id="PGJ-2026-0083", nama="Dewi Lestari", anggota="AG-0264", pokok=15_000_000, tenor=12,
         produk="PJ-PD", keperluan="Biaya pendidikan anak (S1)", urgensi="Tinggi",
         tanggal="29 Sep 2026", status="Dicairkan", sifat="Autodebet", dok="6/6", kas_ok=True),
]

URGENSI_COLOR = {"Tinggi": "danger", "Sedang": "warning", "Rendah": "secondary"}


def urg(x):
    return badge(x, URGENSI_COLOR.get(x, "secondary"))


# --------------------------------------------------------------------- pinjaman berjalan
PINJAMAN_AKTIF = [
    dict(id="PJM-2026-0031", produk="PJ-RG", pokok=25_000_000, tenor=24, angsuran=1_316_667, ke=9,
         mulai="Feb 2026", jatuh="10 Okt 2026", sisa_pokok=16_666_667, status="Aktif", sifat="Autodebet"),
    dict(id="PJM-2025-0114", produk="PJ-PD", pokok=12_000_000, tenor=18, angsuran=786_667, ke=15,
         mulai="Sep 2025", jatuh="10 Nov 2026", sisa_pokok=2_666_662, status="Aktif", sifat="Bayar Bulanan"),
    dict(id="PJM-2023-0117", produk="PJ-RG", pokok=10_000_000, tenor=12, angsuran=933_333, ke=12,
         mulai="Agu 2023", jatuh="—", sisa_pokok=0, status="Lunas", sifat="Autodebet"),
]


def tbl_pinjaman():
    rows = []
    for p in PINJAMAN_AKTIF:
        rows.append([
            f'<span class="fw-semibold">{p["id"]}</span>',
            p["produk"] + " · " + p["sifat"],
            money(p["pokok"]), f'{p["ke"]}/{p["tenor"]}',
            money(p["angsuran"]), money(p["sisa_pokok"]),
            f'{p["jatuh"]}', st(p["status"]),
            '<a href="kartu-piutang.html" class="btn btn-sm btn-outline-primary">Kartu piutang</a>',
        ])
    return rows


# --------------------------------------------------------------------- autodebet (file bank)
AUTODEBET = [
    ("Budi Santoso", "7371 0201 0288 0004", "PJM-2026-0031", 1_316_667, "Berhasil", "—"),
    ("Ratna Kusumawati", "7371 0201 0117 0021", "PJM-2026-0029", 2_450_000, "Berhasil", "—"),
    ("Ahmad Fauzi", "7371 0201 0330 0098", "PJM-2026-0030", 1_120_000, "Gagal", "Saldo tidak cukup"),
    ("Sri Handayani", "7371 0201 0412 0033", "PJM-2026-0018", 890_000, "Berhasil", "—"),
    ("Joko Prasetyo", "7371 0201 0087 0011", "PJM-2026-0011", 1_540_000, "Gagal", "Rekening beku"),
    ("Dewi Lestari", "7371 0201 0221 0064", "PJM-2026-0007", 1_150_000, "Berhasil", "—"),
    ("Hendra Gunawan", "7371 0201 0512 0088", "PJM-2026-0004", 2_010_000, "Gagal", "Kartu kedaluwarsa"),
    ("Marlina Sari", "7371 0201 0345 0057", "PJM-2026-0033", 980_000, "Berhasil", "—"),
    ("Yusuf Hamid", "7371 0201 0198 0022", "PJM-2025-0402", 1_400_000, "Berhasil", "—"),
    ("Nurul Aini", "7371 0201 0076 0009", "PJM-2026-0022", 1_260_000, "Gagal", "Rekening tidak ditemukan"),
]


def tbl_autodebet(only_gagal=False):
    rows = []
    for nama, nik, pjm, nominal, status, ket in AUTODEBET:
        if only_gagal and status != "Gagal":
            continue
        aksi = ('<button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" '
                'data-bs-target="#modalAnggota">Detail anggota</button>')
        rows.append([
            f'<span class="fw-semibold">{nama}</span>', f'<code>{nik}</code>', pjm,
            money(nominal), st(status), ket, aksi,
        ])
    return rows


# --------------------------------------------------------------------- daftar anggota
DAFTAR_ANGGOTA = [
    ("AG-0142", "Budi Santoso", "Fakultas Teknik", 12_500_000, 25_000_000, "Aktif"),
    ("AG-0231", "Ratna Kusumawati", "Fakultas Ekonomi", 18_250_000, 120_000_000, "Aktif"),
    ("AG-0117", "Ahmad Fauzi", "Bagian Umum", 6_400_000, 5_000_000, "Aktif"),
    ("AG-0305", "Sri Handayani", "Perpustakaan", 9_100_000, 18_000_000, "Aktif"),
    ("AG-0098", "Joko Prasetyo", "Fakultas Teknik", 21_800_000, 40_000_000, "Aktif"),
    ("AG-0264", "Dewi Lestari", "Fakultas Keguruan", 7_650_000, 15_000_000, "Aktif"),
    ("AG-0402", "Yusuf Hamid", "Laboratorium", 4_200_000, 0, "Tidak ada pinjaman"),
    ("AG-0333", "Marlina Sari", "Fakultas MIPA", 11_000_000, 9_500_000, "Aktif"),
]


def tbl_anggota(aksi=True):
    rows = []
    for no, nama, unit, simpanan, pinjaman, status in DAFTAR_ANGGOTA:
        kol = [f'<span class="fw-semibold">{no}</span>', nama, unit, money(simpanan),
               money(pinjaman) if pinjaman else '<span class="text-body-secondary">—</span>',
               st(status)]
        if aksi:
            kol.append('<div class="btn-group btn-group-sm"><a class="btn btn-outline-secondary" '
                       'href="anggota.html">Detail</a><button class="btn btn-outline-secondary" '
                       'type="button">Riwayat</button></div>')
        rows.append(kol)
    return rows
