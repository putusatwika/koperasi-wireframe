"""Bangun seluruh berkas HTML statis modul_peminjaman + halaman index + README.

Jalankan:  python3 build.py
Keluaran:  ../modul_peminjaman/**  (HTML statis, Bootstrap via CDN)
"""
import os
import re
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import ksu_core as core                       # noqa: E402
from ksu_core import money, KOPERASI, ROLE_META, CDN_CSS, CDN_ICONS, CDN_JS  # noqa: E402
import ksu_shell                              # noqa: E402
from ksu_shell import PAGES                   # noqa: E402
import ksu_pages_anggota_a                    # noqa: F401,E402  (mendaftarkan halaman)
import ksu_pages_anggota_b                    # noqa: F401,E402
import ksu_pages_admin_a                      # noqa: F401,E402
import ksu_pages_admin_b                      # noqa: F401,E402
import ksu_pages_ketua_pengawas               # noqa: F401,E402

BASE = core.BASE

LANGKAH = [
    (1, "Anggota meminta simulasi pinjaman (produk, pokok, tenor, metode bunga, keperluan).",
     "Anggota", [("anggota/simulasi-form.html", "Form permintaan simulasi")]),
    (2, "Admin koperasi memproses tabel simulasi: jenis bunga (tetap/menurun/anuitas), tenor, "
        "pembayaran bulanan, dan bunga efektif saat ini.",
     "Admin / Bendahara", [("admin/simulasi-masuk.html", "Form proses simulasi")]),
    (3, "Tabel simulasi diserahkan kembali kepada anggota.",
     "Anggota (diterbitkan bendahara)", [("anggota/simulasi-hasil.html", "Hasil simulasi + pembanding 3 metode")]),
    (4, "Anggota mengajukan peminjaman berdasarkan simulasi: keperluan (urgensi), sifat pembayaran "
        "(autodebet / bayar per bulan), unggah dokumen. Bendahara men-setup denda &amp; penalti pelunasan dini.",
     "Anggota + Bendahara", [("anggota/pengajuan-form.html", "Form pengajuan"),
                             ("admin/parameter.html", "Parameter denda &amp; penalti")]),
    (5, "Bendahara memeriksa kas yang tersedia dan kelengkapan dokumen (form kesediaan autodebet, "
        "kolateral BPKB untuk kendaraan, kolateral lain bila ada).",
     "Admin / Bendahara", [("admin/pengajuan.html", "Verifikasi kas &amp; dokumen")]),
    (6, "Berdasarkan hasil pemeriksaan, bendahara merekomendasikan pengajuan kepada ketua koperasi.",
     "Admin / Bendahara", [("admin/rekomendasi.html", "Lembar rekomendasi")]),
    (7, "Ketua melihat tabel peminjam, jumlah kas saat ini, dan urutan berdasarkan prioritas keperluan.",
     "Ketua", [("ketua/daftar-peminjam.html", "Daftar peminjam &amp; prioritas")]),
    (8, "Ketua menyetujui / memberi revisi (terlihat bendahara) / menolak dengan alasan tertentu.",
     "Ketua", [("ketua/persetujuan.html", "Form keputusan")]),
    (9, "Bendahara melihat dashboard, menyiapkan dana pencairan dan bukti transaksi pencairan.",
     "Admin / Bendahara", [("admin/pencairan.html", "Pencairan &amp; bukti transaksi")]),
    (10, "Anggota menerima pencairan dan melakukan cicilan (autodebet atau bayar per bulan).",
     "Anggota", [("anggota/pinjaman-aktif.html", "Cicilan aktif")]),
    (11, "Anggota melihat status cicilan aktif, history hutang, dan kartu piutang setiap pinjaman "
         "(hutang aktif dipisahkan dari yang sudah ditutup).",
     "Anggota", [("anggota/kartu-piutang.html", "Kartu piutang"),
                 ("anggota/riwayat.html", "Hutang aktif vs ditutup")]),
    (12, "Pengawas melihat tabel daftar peminjam dan informasi peminjaman yang dicairkan.",
     "Pengawas", [("pengawas/daftar-peminjam.html", "Daftar peminjam"),
                  ("pengawas/pencairan.html", "Pencairan dicairkan")]),
    (13, "Mekanisme autodebet tanpa API bank: bendahara mengunggah XLSX dari bank (nama nasabah, NIK, "
         "status autodebet); kartu piutang otomatis terupdate; daftar gagal dapat ditindaklanjuti.",
     "Admin / Bendahara", [("admin/autodebet-upload.html", "Upload berkas bank"),
                           ("admin/autodebet-gagal.html", "Autodebet gagal")]),
]


def index_page():
    baris = []
    for no, aktivitas, role, hal in LANGKAH:
        tautan = " ".join('<a href="' + h + '">' + t + "</a>" for h, t in hal)
        baris.append(['<span class="badge text-bg-primary rounded-pill">' + str(no) + "</span>",
                      aktivitas, role, tautan])
    tabel = core.tbl(["Langkah", "Aktivitas pada alur koperasi", "Role pemilik layar", "Halaman wireframe"],
                     baris, footer="Peta ini dipakai saat diskusi dengan programmer: satu langkah bisnis = "
                                   "satu atau lebih layar.")

    kartu_role = ""
    for r, meta in ROLE_META.items():
        items = "".join('<li class="list-group-item"><a href="' + r + "/" + h + '">' + label + "</a></li>"
                        for h, label in core.NAV[r])
        kartu_role += ('<div class="col-lg-6 col-xxl-3"><div class="card h-100">'
                       '<div class="card-header bg-' + meta["theme"] + '" data-bs-theme="dark">'
                       '<span class="fw-semibold text-white">' + meta["label"] + "</span></div>"
                       '<div class="card-body"><p class="small text-body-secondary">' + meta["portal"] +
                       "</p><ul class='list-group list-group-flush'>" + items + "</ul></div></div></div>")

    komponen = core.tbl(["Kebutuhan tampilan", "Komponen Bootstrap yang dipakai"],
                        [["Navigasi per role", "navbar, dropdown, badge"],
                         ["Ringkasan angka", "card, card-group, ikon Bootstrap Icons"],
                         ["Tabel data &amp; jadwal angsuran", "table, table-responsive, table-hover, badge status"],
                         ["Form pengajuan &amp; parameter", "form-control, input-group, form-select, form-check, fieldset"],
                         ["Unggah dokumen &amp; berkas XLSX", "input type=file, form-text, list-group"],
                         ["Jejak proses pengajuan", "list-group, badge, nav-tabs (tab bootstrap)"],
                         ["Detail anggota &amp; konfirmasi", "modal (bawaan Bootstrap, tanpa JS tambahan)"],
                         ["Visual progres &amp; perbandingan", "progress, ratio (placeholder), alert"]])

    html = """<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Modul Simpan Pinjam — Wireframe UI · KSU Mitra Sejahtera</title>
  <link href="__CSS__" rel="stylesheet">
  <link href="__ICONS__" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-expand-lg bg-dark border-bottom" data-bs-theme="dark">
  <div class="container-xxl">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.html">
      <i class="bi bi-bank2"></i><span class="fw-semibold">KSU Mitra Sejahtera</span>
    </a>
    <span class="badge text-bg-light d-none d-xl-inline">Modul Simpan Pinjam — Wireframe</span>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navIndex"
            aria-controls="navIndex" aria-expanded="false" aria-label="Buka navigasi">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navIndex">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="anggota/dashboard.html">Anggota</a></li>
        <li class="nav-item"><a class="nav-link" href="admin/dashboard.html">Admin / Bendahara</a></li>
        <li class="nav-item"><a class="nav-link" href="ketua/dashboard.html">Ketua</a></li>
        <li class="nav-item"><a class="nav-link" href="pengawas/dashboard.html">Pengawas</a></li>
      </ul>
    </div>
  </div>
</nav>
<main class="container-xxl py-4">
  <h1 class="h3 fw-bold mb-2">Wireframe UI — Modul Kegiatan Simpan Pinjam</h1>
  <p class="text-body-secondary mb-4">
    Koperasi Serba Usaha Mitra Sejahtera &middot; dokumen kerja untuk diskusi dengan programmer.
    Semua halaman statis (tanpa backend), memakai <strong>Bootstrap 5.3</strong> lewat CDN tanpa CSS/JS kustom,
    dan diisi data dummy agar alurnya mudah dipahami.
  </p>
  <div class="alert alert-info d-flex gap-2">
    <i class="bi bi-signpost-split"></i>
    <div class="small">
      Cara memakai: buka <code>index.html</code> ini di browser, lalu masuk ke dashboard tiap role melalui menu di
      atas (ada juga menu <em>Pindah role</em> pada navbar tiap halaman). Empat role: anggota, admin koperasi
      (bendahara), ketua, dan pengawas koperasi.
    </div>
  </div>
  <h2 class="h5 fw-bold mb-3">1. Peta alur sistem (12 langkah koperasi + mekanisme autodebet)</h2>
  __TABEL__
  <h2 class="h5 fw-bold mt-5 mb-3">2. Daftar halaman per role</h2>
  <div class="row g-4">__KARTU_ROLE__</div>
  <h2 class="h5 fw-bold mt-5 mb-3">3. Komponen Bootstrap yang dipakai</h2>
  __KOMPONEN__
  <div class="row g-4 mt-4">
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header bg-body-tertiary"><h3 class="h6 fw-bold mb-0">Catatan teknis untuk programmer</h3></div>
        <div class="card-body">
          <ul class="ps-3 mb-0 small">
            <li>Semua halaman HTML statis: <code>bootstrap@5.3.3</code> (CSS + bundle JS) dan
              <code>bootstrap-icons</code> dari CDN jsDelivr.</li>
            <li>Tidak ada CSS kustom, tidak ada JavaScript tambahan &mdash; interaksi memakai komponen bawaan
              Bootstrap (modal, dropdown, tab, collapse) lewat atribut <code>data-bs-*</code>.</li>
            <li>Tata letak memakai grid + utility class (row, col, d-flex, gap, table-responsive, ratio).</li>
            <li>Angka angsuran dihitung nyata (metode flat, menurun, dan anuitas) sehingga konsisten antar halaman
              dan bisa dipakai programmer sebagai contoh keluaran yang diharapkan.</li>
            <li>Form belum terhubung ke backend: nilai terisi statis, tombol hanya menggambarkan alur.</li>
          </ul>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header bg-body-tertiary"><h3 class="h6 fw-bold mb-0">Perlu dikonfirmasi ke pengguna</h3></div>
        <div class="card-body">
          <ul class="ps-3 mb-0 small">
            <li>Kriteria <strong>skor prioritas keperluan</strong> untuk ketua (urgensi, tenggat, kolektibilitas).</li>
            <li>Rumus <strong>denda</strong> dan <strong>penalti pelunasan dipercepat</strong> per jenis pinjaman.</li>
            <li>Kebijakan <strong>autodebet gagal</strong>: percobaan ulang, batas waktu denda, eskalasi.</li>
            <li>Apakah pengawas cukup read-only atau perlu fitur catatan temuan yang tersimpan.</li>
            <li>Format berkas XLSX dari bank (nama kolom &amp; validasi NIK) untuk proses impor.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</main>
<footer class="border-top bg-body py-4 mt-5">
  <div class="container-xxl small text-body-secondary d-flex justify-content-between flex-wrap gap-2">
    <span><i class="bi bi-bank2"></i> __KOPERASI__ &mdash; Modul Simpan Pinjam (wireframe statis)</span>
    <span>Bootstrap 5.3 &middot; tanpa backend &middot; data dummy</span>
  </div>
</footer>
<script src="__JS__"></script>
</body>
</html>
"""
    html = (html.replace("__CSS__", CDN_CSS).replace("__ICONS__", CDN_ICONS).replace("__JS__", CDN_JS)
            .replace("__TABEL__", tabel).replace("__KARTU_ROLE__", kartu_role)
            .replace("__KOMPONEN__", komponen).replace("__KOPERASI__", KOPERASI))
    return html


README = """# Modul Simpan Pinjam — Wireframe UI (Bootstrap 5.3)

Wireframe **high-fidelity** (statis, tanpa backend) untuk modul kegiatan simpan pinjam
Koperasi Serba Usaha. Dokumen ini dipakai sebagai bahan diskusi dengan programmer:
setiap langkah bisnis dipetakan ke layar konkret, dengan data dummy yang konsisten.

- **Nama proyek:** `modul_peminjaman`
- **Teknologi:** HTML statis + Bootstrap 5.3.3 (CDN jsDelivr) + Bootstrap Icons 1.11.3
- **Tanpa** CSS kustom, **tanpa** JavaScript tambahan (interaksi memakai komponen bawaan
  Bootstrap: modal, dropdown, tab, collapse).
- **Data:** dummy, tetapi **angka angsuran dihitung sungguhan** (metode bunga tetap/flat,
  saldo menurun/efektif, dan anuitas) sehingga konsisten di seluruh halaman.

## Cara membuka

    # cara paling sederhana
    open index.html

    # atau lewat server lokal (disarankan agar semua tautan mulus)
    python3 -m http.server 8080
    # lalu buka http://localhost:8080

Mulai dari `index.html` (peta alur 12 langkah + daftar halaman per role). Pada navbar tiap
halaman tersedia menu **Pindah role** untuk berpindah cepat antar role.

## Struktur folder

    modul_peminjaman/
    |-- index.html                  peta alur, daftar halaman, catatan teknis
    |-- README.md
    |-- anggota/                    1) permintaan simulasi, 2) pengajuan, 4) cicilan,
    |   |                           kartu piutang, riwayat hutang aktif vs ditutup
    |   |-- dashboard.html
    |   |-- simulasi-form.html      anggota meminta simulasi pinjaman
    |   |-- simulasi-hasil.html     tabel simulasi dari bendahara (3 metode bunga)
    |   |-- pengajuan-form.html     pengajuan: keperluan/urgensi, autodebet vs bulanan, dokumen
    |   |-- pengajuan-status.html   jejak proses + contoh revisi/penolakan ketua
    |   |-- pinjaman-aktif.html     jadwal cicilan, denda, pelunasan dipercepat
    |   |-- kartu-piutang.html      mutasi piutang per pinjaman
    |   `-- riwayat.html            hutang aktif vs hutang yang sudah ditutup
    |-- admin/                      back office bendahara
    |   |-- dashboard.html          antrian kerja harian
    |   |-- simulasi-masuk.html     proses permintaan simulasi (jenis bunga, tenor, bunga efektif)
    |   |-- pengajuan.html          periksa kas + kelengkapan dokumen (autodebet, kolateral)
    |   |-- rekomendasi.html        lembar rekomendasi ke ketua
    |   |-- pencairan.html          siapkan dana + bukti transaksi pencairan
    |   |-- autodebet-upload.html   unggah XLSX dari bank, kartu piutang otomatis terupdate
    |   |-- autodebet-gagal.html    daftar gagal + detail anggota & tindak lanjut
    |   |-- parameter.html          parameter produk: bunga, denda, penalti lunas dini
    |   `-- anggota.html            data anggota koperasi
    |-- ketua/
    |   |-- dashboard.html
    |   |-- daftar-peminjam.html    tabel peminjam + kas + urutan prioritas keperluan
    |   |-- persetujuan.html        setujui / revisi / tolak dengan alasan
    |   `-- pencairan.html          monitoring realisasi pencairan
    `-- pengawas/
        |-- dashboard.html          temuan, portofolio, jejak audit
        |-- daftar-peminjam.html    tabel peminjam (read-only)
        `-- pencairan.html          informasi peminjaman yang dicairkan

## Pemetaan kebutuhan -> halaman

| Langkah | Kebutuhan | Halaman |
|---|---|---|
| 1 | Anggota meminta simulasi pinjaman | `anggota/simulasi-form.html` |
| 2 | Admin memproses tabel simulasi (jenis bunga, tenor, pembayaran bulanan, bunga efektif saat ini) | `admin/simulasi-masuk.html` |
| 3 | Tabel diserahkan kepada anggota | `anggota/simulasi-hasil.html` |
| 4 | Pengajuan: keperluan/urgensi, sifat autodebet atau bayar per bulan, dokumen | `anggota/pengajuan-form.html` |
| 4 | Bendahara setup denda & mekanisme pelunasan dini (penalti per jenis pinjaman) | `admin/parameter.html` |
| 5 | Periksa kas + kelengkapan dokumen (form kesediaan autodebet, kolateral BPKB) | `admin/pengajuan.html` |
| 6 | Rekomendasi bendahara ke ketua | `admin/rekomendasi.html` |
| 7 | Ketua: tabel peminjam, kas saat ini, urutan prioritas keperluan | `ketua/daftar-peminjam.html` |
| 8 | Ketua: setujui / revisi / tolak dengan alasan | `ketua/persetujuan.html` |
| 9 | Bendahara: siapkan dana + bukti transaksi pencairan | `admin/pencairan.html` |
| 10 | Anggota menerima pencairan & mencicil | `anggota/pinjaman-aktif.html` |
| 11 | Status cicilan aktif, history hutang, kartu piutang (aktif vs ditutup) | `anggota/kartu-piutang.html`, `anggota/riwayat.html` |
| 12 | Pengawas: daftar peminjam + peminjaman yang dicairkan | `pengawas/daftar-peminjam.html`, `pengawas/pencairan.html` |
| — | Autodebet tanpa API bank: unggah XLSX (nama nasabah, NIK, status) & daftar gagal | `admin/autodebet-upload.html`, `admin/autodebet-gagal.html` |

## Angka acuan yang dipakai (dummy, tetapi konsisten)

Skenario utama: pinjaman reguler **Rp 25.000.000**, tenor **24 bulan**, tarif **1,10%/bulan**.
Angka di bawah dihitung nyata oleh generator (amortisasi + IRR), jadi bisa dipakai programmer
sebagai contoh keluaran yang diharapkan.

| Metode | Angsuran/bulan | Total bunga | Total dibayar | Bunga efektif per tahun |
|---|---|---|---|---|
| Bunga tetap (flat) | {flata} | {flatb} | {flatc} | {flatd} |
| Bunga menurun (efektif) | {mena} | {menb} | {menc} | {mend} |
| Bunga anuitas | {ana} | {anb} | {anc} | {and_} |

Catatan: bunga flat dihitung dari pokok awal sehingga bunga efektifnya ({flatd}) jauh lebih
tinggi daripada metode menurun/anuitas ({mend}) meski tarif per bulannya sama.

Nilai tiap baris jadwal angsuran ada di `anggota/simulasi-hasil.html` dan
`anggota/kartu-piutang.html`.

## Asumsi yang perlu dikonfirmasi

1. Kriteria/skor **prioritas keperluan** untuk ketua (urgensi, tenggat, kolektibilitas, senioritas).
2. Rumus **denda keterlambatan** dan **penalti pelunasan dipercepat** per jenis pinjaman.
3. Kebijakan **autodebet gagal**: jumlah percobaan ulang, kapan denda mulai dikenakan, eskalasi.
4. Format baku berkas **XLSX dari bank** (nama kolom, validasi NIK, penanganan NIK tidak cocok).
5. Apakah **pengawas** hanya read-only atau dapat menyimpan catatan temuan.
6. Perlu tidaknya **jejak audit** yang mencatat setiap perubahan parameter (draf sudah ada di
   `admin/parameter.html`).

## Generator (di luar folder kerja ini)

Halaman-halaman ini dihasilkan oleh skrip Python di `../_build/` (Bootstrap murni, tanpa CSS/JS kustom).
Jika ingin menambah halaman/varian dengan pola yang sama: ubah data di `_build/ksu_data.py`,
tambah halaman di modul `ksu_pages_*.py`, lalu jalankan `python3 build.py` dari folder `_build`.
"""


def readme_text():
    """README dengan angka acuan yang dihitung nyata dari data amortisasi."""
    from ksu_data import SIM
    from ksu_core import pctf
    f_, m_, a_ = SIM["flat"], SIM["menurun"], SIM["anuitas"]
    return README.format(
        flata=money(f_["angs_min"]), flatb=money(f_["tot_bunga"]), flatc=money(f_["tot_bayar"]),
        flatd=pctf(f_["r_eff_tahun"]),
        mena=money(m_["angs_max"]) + " &rarr; " + money(m_["angs_min"]),
        menb=money(m_["tot_bunga"]), menc=money(m_["tot_bayar"]), mend=pctf(m_["r_eff_tahun"]),
        ana=money(a_["angs_min"]), anb=money(a_["tot_bunga"]), anc=money(a_["tot_bayar"]),
        and_=pctf(a_["r_eff_tahun"]),
    ).replace("{and_}", pctf(a_["r_eff_tahun"]))


def tulis():
    os.makedirs(BASE, exist_ok=True)
    for rel, meta in sorted(PAGES.items()):
        path = os.path.join(BASE, rel)
        os.makedirs(os.path.dirname(path), exist_ok=True)
        with open(path, "w", encoding="utf-8") as f:
            f.write(meta["html"])
    with open(os.path.join(BASE, "index.html"), "w", encoding="utf-8") as f:
        f.write(index_page())
    with open(os.path.join(BASE, "README.md"), "w", encoding="utf-8") as f:
        f.write(readme_text())
    print("Halaman ditulis:", len(PAGES) + 1, "berkas HTML")


def periksa():
    """Cek tautan internal & sisa penanda placeholder."""
    semua = set(PAGES.keys()) | {"index.html", "README.md"}
    rusak, sisa = [], []
    for rel, meta in PAGES.items():
        src = meta["html"]
        for href in re.findall(r'href="([^"#]+)"', src):
            if href.startswith(("http", "mailto:", "#")):
                continue
            target = os.path.normpath(os.path.join(os.path.dirname(rel), href))
            if href.startswith("../"):
                target = os.path.normpath(os.path.join(os.path.dirname(rel), href))
            if target not in semua:
                rusak.append((rel, href))
        for tok in re.findall(r"@[A-Z]{2,}@", src):
            sisa.append((rel, tok))
    print("Tautan internal rusak:", len(rusak))
    for r in rusak[:40]:
        print("   ", r[0], "->", r[1])
    print("Placeholder belum terisi:", len(sisa))
    for r in sisa[:20]:
        print("   ", r)


if __name__ == "__main__":
    tulis()
    periksa()
