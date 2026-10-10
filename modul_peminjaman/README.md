# Modul Simpan Pinjam — Wireframe UI (Bootstrap 5.3)

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
| Bunga tetap (flat) | Rp 1.316.667 | Rp 6.600.000 | Rp 31.600.008 | 26,32% |
| Bunga menurun (efektif) | Rp 1.316.667 &rarr; Rp 1.053.125 | Rp 3.437.500 | Rp 28.437.500 | 14,03% |
| Bunga anuitas | Rp 1.190.895 | Rp 3.581.493 | Rp 28.581.480 | 14,03% |

Catatan: bunga flat dihitung dari pokok awal sehingga bunga efektifnya (26,32%) jauh lebih
tinggi daripada metode menurun/anuitas (14,03%) meski tarif per bulannya sama.

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
