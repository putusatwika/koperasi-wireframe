"""Halaman role: ADMIN KOPERASI / BENDAHARA (bagian 1) — dashboard, simulasi masuk,
verifikasi pengajuan, rekomendasi ke ketua."""
from ksu_core import card, tbl, kpi_row, note, stepper, dl, money, pct, pctf, badge, st, bars
from ksu_data import (KAS, ANGGOTA, PRODUK, SIM, SKENARIO, PENGAJUAN, DAFTAR_ANGGOTA,
                      PINJAMAN_AKTIF, tabel_jadwal, tabel_compare)
from ksu_shell import add, layout

STEP = ["Permintaan simulasi", "Verifikasi & rekomendasi", "Persetujuan ketua", "Pencairan", "Cicilan & autodebet"]

REQ_SIM = [
    ("SIM-2026-0143", "Ratna Kusumawati", "AG-0231", "PJ-MG", 120_000_000, 36, "Anuitas", "05 Okt 2026", "Menunggu Simulasi"),
    ("SIM-2026-0142", "Budi Santoso", "AG-0142", "PJ-RG", 25_000_000, 24, "Menurun", "06 Okt 2026", "Simulasi Selesai"),
    ("SIM-2026-0140", "Ahmad Fauzi", "AG-0117", "PJ-DR", 5_000_000, 10, "Tetap (flat)", "04 Okt 2026", "Simulasi Selesai"),
    ("SIM-2026-0139", "Sri Handayani", "AG-0305", "PJ-PD", 18_000_000, 18, "Menurun", "03 Okt 2026", "Menunggu Simulasi"),
    ("SIM-2026-0138", "Joko Prasetyo", "AG-0098", "PJ-RG", 40_000_000, 30, "Anuitas", "02 Okt 2026", "Simulasi Selesai"),
]


def dashboard():
    pintasan = """<div class="d-grid gap-2">
        <a class="btn btn-outline-primary" href="simulasi-masuk.html">1. Proses permintaan simulasi</a>
        <a class="btn btn-outline-primary" href="pengajuan.html">2. Verifikasi kas &amp; dokumen</a>
        <a class="btn btn-outline-primary" href="rekomendasi.html">3. Rekomendasi ke ketua</a>
        <a class="btn btn-outline-primary" href="pencairan.html">4. Pencairan dana &amp; bukti</a>
        <a class="btn btn-outline-primary" href="autodebet-upload.html">5. Upload autodebet bank</a>
        <a class="btn btn-outline-primary" href="autodebet-gagal.html">6. Tindak lanjut autodebet gagal</a>
      </div>"""

    kpi = kpi_row([
        ("Kas tersedia", money(KAS["saldo_kas"]), "kas terikat " + money(KAS["kas_terikat"]), "success", "bi-safe2"),
        ("Piutang berjalan", money(KAS["piutang_berjalan"]), "dari 214 pinjaman aktif anggota", "primary", "bi-cash-coin"),
        ("Menunggu diproses", "12 tugas", "3 simulasi &middot; 5 pengajuan &middot; 4 pencairan", "warning", "bi-inbox"),
        ("Autodebet gagal (Okt)", "7 anggota", "dari 214 transaksi terkirim bank", "danger", "bi-x-circle"),
    ])

    antrian = tbl(["Antrian", "Jumlah", "Nilai", "Batas waktu", "Status", "Aksi"],
                  [["Permintaan simulasi belum diproses", "3 permintaan", money(163_000_000), "hari ini",
                    badge("Prioritas sedang", "warning"),
                    '<a class="btn btn-sm btn-primary" href="simulasi-masuk.html">Proses</a>'],
                   ["Pengajuan menunggu verifikasi dokumen", "5 pengajuan", money(197_000_000), "hari ini",
                    badge("Prioritas tinggi", "danger"),
                    '<a class="btn btn-sm btn-primary" href="pengajuan.html">Verifikasi</a>'],
                   ["Pengajuan siap direkomendasikan", "2 pengajuan", money(155_000_000), "besok",
                    badge("Prioritas tinggi", "danger"),
                    '<a class="btn btn-sm btn-primary" href="pengajuan.html">Rekomendasikan</a>'],
                   ["Pinjaman disetujui siap dicairkan", "4 pinjaman", money(88_500_000), "2 hari kerja",
                    badge("Prioritas tinggi", "danger"),
                    '<a class="btn btn-sm btn-primary" href="pencairan.html">Siapkan dana</a>'],
                   ["Autodebet gagal perlu tindak lanjut", "7 anggota", money(9_240_000), "segera",
                    badge("Prioritas tinggi", "danger"),
                    '<a class="btn btn-sm btn-primary" href="autodebet-gagal.html">Tindak lanjut</a>'],
                   ["Upload file autodebet bank", "1 berkas", "&mdash;", "tanggal 5 tiap bulan",
                    badge("Rutin", "info"), '<a class="btn btn-sm btn-outline-primary" href="autodebet-upload.html">Unggah</a>']])

    kas = bars([("Kas tersedia", money(KAS["saldo_kas"]), 88), ("Kas terikat (jaminan)", money(KAS["kas_terikat"]), 16),
                ("Piutang macet", money(KAS["piutang_macet"]), 4)])

    aktivitas = tbl(["Waktu", "Aktivitas", "Oleh", "Status"],
                    [["10 Okt 2026 09:12", "Upload file autodebet bank Oktober 2026 (214 baris)", "S. Rahmawati", st("Diterima")],
                     ["09 Okt 2026 16:40", "Rekomendasi pengajuan PGJ-2026-0088 ke ketua", "S. Rahmawati", st("Direkomendasikan")],
                     ["09 Okt 2026 14:05", "Pencairan PJM-2026-0033 (Dewi Lestari)", "S. Rahmawati", st("Dicairkan")],
                     ["08 Okt 2026 15:02", "Revisi tenor dari ketua untuk PGJ-2026-0088", "H. Wijaya", st("Revisi")],
                     ["07 Okt 2026 15:40", "Tabel simulasi SIM-2026-0142 diterbitkan", "S. Rahmawati", st("Simulasi Selesai")]])

    body = f"""
{kpi}
<div class="row g-4">
  <div class="col-xl-8">
    {card("Antrian tugas bendahara", antrian,
          subtitle="Semua pekerjaan operasional harian dalam satu daftar",
          tools='<button class="btn btn-sm btn-outline-secondary" type="button">Ekspor daftar tugas</button>')}
    {card("Aktivitas terbaru", aktivitas, subtitle="Jejak audit untuk pengawas koperasi")}
  </div>
  <div class="col-xl-4">
    {card("Kesehatan likuiditas koperasi", kas,
          subtitle="Kas tersedia vs dana terikat dan piutang macet")}
    {card("Ringkasan portofolio", dl([
        ("Jumlah anggota", str(KAS["jumlah_anggota"]) + " orang"),
        ("Simpanan anggota", money(KAS["simpanan_anggota"])),
        ("Pinjaman aktif", "214 pinjaman"),
        ("Bunga efektif acuan", "12,00% p.a. (1,10%/bln menurun)"),
        ("Autodebet berhasil", "207 dari 214 transaksi"),
    ]), footer="Data dummy per 10 Okt 2026")}
    {card("Pintasan", pintasan, subtitle="Alur kerja bagian simpan pinjam")}
  </div>
</div>"""
    return layout("admin", "dashboard.html", "Dashboard Bendahara", "Langkah 2, 5, 6, 9",
                  body, desc="Antrian kerja harian: simulasi, verifikasi, pencairan, autodebet",
                  crumb=[("Back Office Bendahara", "dashboard.html"), ("Dashboard", None)],
                  actions='<a class="btn btn-primary" href="simulasi-masuk.html">'
                          '<i class="bi bi-list-check"></i> Buka antrian simulasi</a>')


def simulasi_masuk():
    rows = []
    for no, nama, ag, produk_k, pokok, tenor, metode, tgl, status in REQ_SIM:
        rows.append([f'<span class="fw-semibold">{no}</span>', nama + '<div class="small text-body-secondary">' + ag + "</div>",
                     produk_k, money(pokok), str(tenor) + " bln", metode, tgl, st(status),
                     '<a class="btn btn-sm btn-primary" href="#panel-proses">Proses</a>'])

    tabel_req = tbl(["No. simulasi", "Anggota", "Produk", "Pokok", "Tenor", "Metode diminta",
                     "Tanggal", "Status", "Aksi"], rows,
                    footer="3 permintaan masih berstatus Menunggu Simulasi. Bendahara wajib memakai bunga efektif "
                           "yang berlaku saat memproses (bukan tarif lama).")

    form = """<form class="row g-3">
  <div class="col-12">
    <div class="alert alert-info py-2 mb-0 small">
      <i class="bi bi-info-circle"></i> Bunga efektif yang berlaku saat ini: <strong>1,10% per bulan</strong>
      (setara 14,03% efektif per tahun) untuk PJ-RG, berlaku sejak 01 Okt 2026 (SK Pengurus No. 07/SK/X/2026).
    </div>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="nosim">Nomor simulasi</label>
    <input type="text" class="form-control" id="nosim" value="SIM-2026-0143" readonly>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="anggota">Anggota pemohon</label>
    <input type="text" class="form-control" id="anggota" value="Ratna Kusumawati (AG-0231)" readonly>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="produk">Produk pinjaman</label>
    <select class="form-select" id="produk">
      <option>PJ-MG &mdash; Multiguna / Kendaraan (anuitas 1,35%/bln)</option>
      <option selected>PJ-RG &mdash; Reguler (menurun 1,10%/bln)</option>
      <option>PJ-PD &mdash; Pendidikan (menurun 1,00%/bln)</option>
      <option>PJ-DR &mdash; Darurat (tetap 0,90%/bln)</option>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label" for="pokok">Pokok pinjaman yang disimulasikan</label>
    <div class="input-group">
      <span class="input-group-text">Rp</span>
      <input type="number" class="form-control" id="pokok" value="25000000" step="1000000">
    </div>
  </div>
  <div class="col-md-3">
    <label class="form-label" for="tenor">Tenor final (bulan)</label>
    <input type="number" class="form-control" id="tenor" value="24" min="6" max="48">
  </div>
  <div class="col-md-3">
    <label class="form-label" for="rate">Bunga efektif saat ini (% / bulan)</label>
    <input type="number" class="form-control" id="rate" value="1.10" step="0.05">
    <div class="form-text">Diambil dari parameter produk &amp; SK bunga berjalan.</div>
  </div>
  <div class="col-md-3">
    <label class="form-label" for="tipe">Jenis perhitungan bunga</label>
    <select class="form-select" id="tipe">
      <option>Bunga tetap (flat)</option>
      <option selected>Bunga menurun (efektif)</option>
      <option>Bunga anuitas</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="adm">Biaya administrasi</label>
    <div class="input-group">
      <span class="input-group-text">%</span>
      <input type="number" class="form-control" id="adm" value="0.50" step="0.05">
      <span class="input-group-text">dari pokok</span>
    </div>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="asuransi">Asuransi / dana risiko</label>
    <div class="input-group">
      <span class="input-group-text">Rp</span>
      <input type="number" class="form-control" id="asuransi" value="0" step="50000">
    </div>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="bulat">Pembulatan angsuran</label>
    <select class="form-select" id="bulat">
      <option>Rupiah penuh</option>
      <option selected>Ke atas per Rp 1.000</option>
      <option>Ke atas per Rp 10.000</option>
    </select>
  </div>
  <div class="col-12">
    <label class="form-label" for="catatan">Catatan untuk anggota (tampil di tabel hasil simulasi)</label>
    <textarea class="form-control" id="catatan" rows="2">Simulasi memakai bunga menurun 1,10%/bulan dengan tenor
24 bulan. Angsuran menurun setiap bulan dari Rp 1.466.667 menjadi Rp 1.218.750.</textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="berlaku">Tabel berlaku sampai</label>
    <input type="date" class="form-control" id="berlaku" value="2026-11-07">
  </div>
  <div class="col-md-6 d-flex align-items-end">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="bandingkan" checked>
      <label class="form-check-label" for="bandingkan">Sertakan tabel pembanding tiga jenis bunga untuk anggota</label>
    </div>
  </div>
  <div class="col-12 d-flex flex-wrap gap-2">
    <button class="btn btn-primary" type="button"><i class="bi bi-calculator"></i> Hitung tabel angsuran</button>
    <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#modalKirim">
      <i class="bi bi-send"></i> Kirim tabel ke anggota</button>
    <button class="btn btn-outline-secondary" type="button">Simpan draft simulasi</button>
  </div>
</form>"""

    hasil = dl([
        ("Angsuran pertama", money(SIM["menurun"]["rows"][0]["angs"])),
        ("Angsuran terakhir", money(SIM["menurun"]["rows"][-1]["angs"])),
        ("Total bunga", money(SIM["menurun"]["tot_bunga"])),
        ("Total pembayaran", money(SIM["menurun"]["tot_bayar"])),
        ("Bunga efektif per tahun", pctf(SIM["menurun"]["r_eff_tahun"]) + " p.a."),
        ("Biaya administrasi", money(125_000)),
    ])

    compare = tbl(["Metode", "Angsuran / bulan", "Total bunga", "Total bayar", "Bunga efektif bulanan", "Bunga efektif per tahun"],
                  tabel_compare(),
                  footer="Tabel inilah yang diserahkan ke anggota (Langkah 3) agar bisa dibandingkan sebelum mengajukan.")

    modal = """<div class="modal fade" id="modalKirim" tabindex="-1" aria-labelledby="modalKirimLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalKirimLabel">Kirim tabel simulasi ke anggota</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p class="small">Tabel SIM-2026-0143 akan dikirim ke Ratna Kusumawati (AG-0231) melalui portal anggota dan
          e-mail <code>ratna@koperasi.example.id</code>.</p>
        <ul class="small">
          <li>Pokok Rp 25.000.000 &middot; tenor 24 bulan &middot; bunga menurun 1,10%/bulan.</li>
          <li>Termasuk tabel pembanding (tetap / menurun / anuitas).</li>
          <li>Status permintaan berubah menjadi <em>Simulasi Selesai</em>.</li>
        </ul>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="setsim" checked>
          <label class="form-check-label small" for="setsim">Kirim juga salinan ke bendahara dan arsipkan di berkas
            simulasi anggota.</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <a class="btn btn-success" href="simulasi-masuk.html">Kirim sekarang</a>
      </div>
    </div>
  </div>
</div>"""

    body = f"""
{stepper(STEP, 1)}
{note("<strong>Langkah 2:</strong> admin koperasi memproses tabel simulasi pinjaman berdasarkan informasi jenis bunga "
      "(tetap/menurun/anuitas), lama tenor, pembayaran bulanan, dan bunga efektif saat ini. "
      "<strong>Langkah 3:</strong> tabel hasil diserahkan kembali ke anggota.")}
<div class="row g-4">
  <div class="col-xl-12">
    {card("Permintaan simulasi dari anggota", tabel_req,
          subtitle="Diurutkan dari yang paling lama menunggu",
          tools='<div class="input-group input-group-sm"><input type="search" class="form-control" placeholder="Cari nama / nomor">'
                '<button class="btn btn-outline-secondary" type="button">Cari</button></div>')}
  </div>
  <div class="col-xl-8">
    <div id="panel-proses"></div>
    {card("Form proses simulasi", form, subtitle="Bendahara menetapkan parameter final sebelum tabel dikirim")}
  </div>
  <div class="col-xl-4">
    {card("Pratinjau hasil perhitungan", hasil,
          footer="Angka mengikuti skenario dummy: Rp 25,0 jt / 24 bulan / 1,10% per bulan (menurun).")}
    {card("Parameter produk terpilih", dl([
        (p["nama"], pct(p["bunga"]) + "/bln") for p in PRODUK[:0]
    ]) if False else tbl(["Produk", "Bunga", "Metode", "Denda", "Penalti lunas dini"],
                         [[p["nama"], pct(p["bunga"]) + "/bln", p["metode"],
                           pct(p["denda"], 2) + "/bln", pct(p["penalti"], 1) + " dari sisa pokok"] for p in PRODUK]),
        subtitle="Dipakai bendahara saat memproses simulasi")}
  </div>
  <div class="col-12">
    {card("Tabel pembanding yang akan dikirim ke anggota", compare)}
  </div>
</div>"""
    return layout("admin", "simulasi-masuk.html", "Simulasi Pinjaman Masuk", "Langkah 2&ndash;3",
                  body, desc="Proses permintaan simulasi anggota dengan bunga efektif yang berlaku",
                  crumb=[("Back Office Bendahara", "dashboard.html"), ("Simulasi masuk", None)],
                  modals=modal)


def pengajuan():
    rows = []
    for p in PENGAJUAN:
        rows.append([f'<span class="fw-semibold">{p["id"]}</span>',
                     p["nama"] + '<div class="small text-body-secondary">' + p["anggota"] + "</div>",
                     p["produk"], money(p["pokok"]), str(p["tenor"]) + " bln", p["keperluan"],
                     badge(p["urgensi"], {"Tinggi": "danger", "Sedang": "warning", "Rendah": "secondary"}[p["urgensi"]]),
                     p["sifat"], p["dok"], st(p["status"]),
                     '<a class="btn btn-sm btn-primary" href="#detail">Periksa</a>'])
    tabel_pengajuan = tbl(["No. pengajuan", "Anggota", "Produk", "Pokok", "Tenor", "Keperluan", "Urgensi",
                           "Sifat bayar", "Dokumen", "Status", "Aksi"], rows,
                          footer="Pengajuan yang dokumennya belum lengkap tidak boleh direkomendasikan ke ketua.")

    dok_rows = [
        ["Scan KTP pemohon", badge("Ada", "success"), "&mdash;", "&mdash;"],
        ["Slip gaji / SK 3 bulan", badge("Ada", "success"), "&mdash;", "&mdash;"],
        ["Form kesediaan autodebet bermaterai", badge("Ada", "success"), "&mdash;", "&mdash;"],
        ["Rekening koran 3 bulan", badge("Ada", "success"), "&mdash;", "&mdash;"],
        ["Bukti kolateral (BPKB)", badge("Tidak diperlukan", "secondary", outline=True), "Produk PJ-RG tanpa kolateral", "&mdash;"],
        ["Surat kuasa potong gaji / pernyataan", badge("Ada", "success"), "&mdash;", "&mdash;"],
        ["Dokumen pendukung keperluan (SK pendidikan)", badge("Ada", "success"), "&mdash;", "&mdash;"],
    ]
    dokumen = tbl(["Dokumen", "Status berkas", "Catatan pemeriksa", "Aksi"], dok_rows)

    kas_cek = tbl(["Komponen", "Nilai"],
                  [["Kas tersedia saat ini", money(KAS["saldo_kas"])],
                   ["Dana terikat / komitmen pencairan lain", money(88_500_000)],
                   ["Kebutuhan pencairan pengajuan ini", money(SKENARIO["P"])],
                   ["Kemampuan setelah pencairan", money(KAS["saldo_kas"] - 88_500_000 - SKENARIO["P"])]],
                  footer="Batas aman kas minimum koperasi: Rp 150.000.000 (kebijakan pengurus).")

    form_verifikasi = """<form class="row g-3">
  <div class="col-md-4">
    <label class="form-label" for="kasok">Ketersediaan kas</label>
    <select class="form-select" id="kasok">
      <option selected>Cukup &mdash; siap direkomendasikan</option>
      <option>Cukup dengan catatan (menunggu dana masuk)</option>
      <option>Belum cukup &mdash; tunda pencairan</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="dokok">Kelengkapan dokumen</label>
    <select class="form-select" id="dokok">
      <option selected>Lengkap (6/6)</option>
      <option>Lengkap dengan catatan</option>
      <option>Belum lengkap &mdash; minta kelengkapan</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="sifatv">Verifikasi sifat pembayaran</label>
    <select class="form-select" id="sifatv">
      <option selected>Autodebet &mdash; form kesediaan valid</option>
      <option>Bayar per bulan &mdash; surat kuasa potong gaji valid</option>
      <option>Perlu konfirmasi ke bank</option>
    </select>
  </div>
  <div class="col-12">
    <label class="form-label" for="analisis">Analisis bendahara (tampil di lembar rekomendasi)</label>
    <textarea class="form-control" id="analisis" rows="3">Anggota aktif sejak 2019, kolektibilitas lancar (8/8 cicilan
tepat waktu). Rasio cicilan terhadap penghasilan 34%, di bawah batas 44%. Keperluan pendidikan anak bersifat mendesak
dan sesuai produk PJ-RG. Rekomendasi: disetujui dengan tenor sesuai simulasi.</textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="rekom">Sikap bendahara</label>
    <select class="form-select" id="rekom">
      <option selected>Rekomendasikan untuk disetujui</option>
      <option>Rekomendasikan dengan catatan</option>
      <option>Tunda &mdash; minta kelengkapan dokumen</option>
      <option>Tidak direkomendasikan</option>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="lampiran">Lampiran untuk ketua</label>
    <input type="file" class="form-control" id="lampiran" multiple>
    <div class="form-text">Hasil pemeriksaan kas, salinan dokumen, dan tabel simulasi.</div>
  </div>
  <div class="col-12 d-flex flex-wrap gap-2">
    <a class="btn btn-primary" href="rekomendasi.html"><i class="bi bi-arrow-right-circle"></i> Simpan &amp; susun lembar rekomendasi</a>
    <button class="btn btn-outline-warning" type="button">Minta kelengkapan ke anggota</button>
    <button class="btn btn-outline-secondary" type="button">Simpan sebagai draft verifikasi</button>
  </div>
</form>"""

    body = f"""
{stepper(STEP, 1)}
{note("<strong>Langkah 5:</strong> berdasarkan isian anggota, bendahara memeriksa khas (kas) yang tersedia dan "
      "kelengkapan dokumen &mdash; termasuk form kesediaan autodebet, kolateral BPKB untuk pinjaman kendaraan, atau "
      "kolateral lain bila ada. <strong>Langkah 6:</strong> bendahara merekomendasikan ke ketua koperasi.")}
{kpi_row([
    ("Pengajuan menunggu verifikasi", "5", "nilai total " + money(197_000_000), "warning", "bi-inbox"),
    ("Kas tersedia", money(KAS["saldo_kas"]), "siap untuk pencairan yang disetujui", "success", "bi-safe2"),
    ("Dokumen belum lengkap", "2 pengajuan", "perlu tindak lanjut anggota", "danger", "bi-file-earmark-x"),
    ("Siap direkomendasikan", "2 pengajuan", "menunggu lampiran lembar rekomendasi", "info", "bi-send-check"),
])}
<div class="row g-4">
  <div class="col-12">
    {card("Daftar pengajuan pinjaman", tabel_pengajuan,
          tools='<div class="d-flex gap-2"><select class="form-select form-select-sm"><option>Semua status</option>'
                '<option>Diajukan</option><option>Verifikasi Dokumen</option><option>Direkomendasikan</option></select>'
                '<button class="btn btn-sm btn-outline-secondary" type="button">Saring</button></div>')}
  </div>
  <div class="col-xl-8">
    <div id="detail"></div>
    {card("Pemeriksaan dokumen &mdash; PGJ-2026-0088 (Budi Santoso)",
          dokumen, subtitle="Form kesediaan autodebet adalah berkas kunci untuk pinjaman autodebet")}
    {card("Form verifikasi &amp; analisis bendahara", form_verifikasi,
          subtitle="Hasil bagian ini menjadi lampiran lembar rekomendasi ke ketua")}
  </div>
  <div class="col-xl-4">
    {card("Pemeriksaan ketersediaan kas", kas_cek,
          subtitle="Perbandingan kebutuhan pencairan dengan kas yang tersedia")}
    {card("Ringkasan pengajuan yang diperiksa", dl([
        ("No. pengajuan", "PGJ-2026-0088"),
        ("Pokok", money(SKENARIO["P"])),
        ("Tenor", "24 bulan"),
        ("Simulasi acuan", "SIM-2026-0142"),
        ("Sifat bayar", "Autodebet BSI 7100 2299 441"),
        ("Agunan", "Tanpa kolateral (potong gaji/autodebet)"),
        ("Rasio cicilan", "34% dari penghasilan"),
    ]))}
    {card("Catatan produk &amp; penalti", tbl(["Ketentuan", "Nilai"],
        [["Denda keterlambatan", "0,50% / bulan dari tunggakan"],
         ["Penalti pelunasan dipercepat", "1% dari sisa pokok"],
         ["Biaya administrasi", "0,5% dari pokok"],
         ["Jaminan pinjaman kendaraan", "BPKB + asuransi"]]),
        footer="Parameter lengkap diatur di halaman Parameter Pinjaman.")}
  </div>
</div>"""
    return layout("admin", "pengajuan.html", "Verifikasi Pengajuan Peminjaman", "Langkah 5&ndash;6",
                  body, desc="Periksa kas, dokumen, dan sifat pembayaran sebelum merekomendasikan",
                  crumb=[("Back Office Bendahara", "dashboard.html"), ("Verifikasi pengajuan", None)],
                  actions='<a class="btn btn-outline-primary" href="parameter.html">Atur parameter pinjaman</a>')


def rekomendasi():
    alur = """<ol class="ps-3 mb-0 small">
        <li>Ketua melihat daftar peminjam &amp; prioritas keperluan (Langkah 7).</li>
        <li>Ketua menyetujui / memberi revisi / menolak dengan alasan (Langkah 8).</li>
        <li>Bila disetujui, bendahara menyiapkan dana pencairan dan bukti transaksi (Langkah 9).</li>
        <li>Revisi dari ketua tampil di halaman ini dan di portal anggota.</li>
      </ol>"""

    riwayat_rek = tbl(["Tanggal", "No. pengajuan", "Anggota", "Pokok", "Sikap bendahara", "Status di ketua", "Catatan"],
                      [["09 Okt 2026", "PGJ-2026-0088", "Budi Santoso", money(25_000_000),
                        badge("Direkomendasikan", "success"), st("Menunggu Persetujuan"),
                        "Minta tabel angsuran 36 bulan (revisi ketua)"],
                       ["09 Okt 2026", "PGJ-2026-0087", "Ratna Kusumawati", money(120_000_000),
                        badge("Direkomendasikan", "success"), st("Menunggu Persetujuan"),
                        "Kolateral BPKB sudah diverifikasi bendahara"],
                       ["08 Okt 2026", "PGJ-2026-0086", "Ahmad Fauzi", money(5_000_000),
                        badge("Direkomendasikan", "success"), st("Disetujui"), "Disetujui tanpa revisi"],
                       ["07 Okt 2026", "PGJ-2026-0084", "Joko Prasetyo", money(40_000_000),
                        badge("Tidak direkomendasikan", "danger"), st("Ditolak"),
                        "Tidak direkomendasikan: rasio cicilan 51%"],
                       ["05 Okt 2026", "PGJ-2026-0083", "Dewi Lestari", money(15_000_000),
                        badge("Direkomendasikan", "success"), st("Dicairkan"), "Dicairkan 09 Okt 2026"]])

    lembar = """<div class="card">
      <div class="card-body">
        <div class="text-center mb-4">
          <h3 class="h5 fw-bold mb-0">LEMBAR REKOMENDASI PINJAMAN</h3>
          <div class="small text-body-secondary">Koperasi Serba Usaha Mitra Sejahtera &mdash; Unit Simpan Pinjam</div>
          <div class="small text-body-secondary">No. REK-2026-0088 &middot; 09 Oktober 2026</div>
        </div>
        <dl class="row g-0 small">
          <dt class="col-sm-4 fw-normal text-body-secondary py-2 border-bottom">Nama anggota</dt>
          <dd class="col-sm-8 py-2 mb-0 border-bottom">Budi Santoso, S.Kom., M.T. (AG-0142)</dd>
          <dt class="col-sm-4 fw-normal text-body-secondary py-2 border-bottom">Jumlah pinjaman</dt>
          <dd class="col-sm-8 py-2 mb-0 border-bottom">Rp 25.000.000 (dua puluh lima juta rupiah)</dd>
          <dt class="col-sm-4 fw-normal text-body-secondary py-2 border-bottom">Tenor &amp; metode bunga</dt>
          <dd class="col-sm-8 py-2 mb-0 border-bottom">24 bulan &middot; bunga menurun 1,10%/bulan</dd>
          <dt class="col-sm-4 fw-normal text-body-secondary py-2 border-bottom">Angsuran per bulan</dt>
          <dd class="col-sm-8 py-2 mb-0 border-bottom">Rp 1.466.667 (menurun sampai Rp 1.218.750)</dd>
          <dt class="col-sm-4 fw-normal text-body-secondary py-2 border-bottom">Keperluan &amp; urgensi</dt>
          <dd class="col-sm-8 py-2 mb-0 border-bottom">Biaya pendidikan anak &middot; urgensi tinggi</dd>
          <dt class="col-sm-4 fw-normal text-body-secondary py-2 border-bottom">Sifat pembayaran</dt>
          <dd class="col-sm-8 py-2 mb-0 border-bottom">Autodebet BSI 7100 2299 441</dd>
          <dt class="col-sm-4 fw-normal text-body-secondary py-2 border-bottom">Hasil pemeriksaan kas</dt>
          <dd class="col-sm-8 py-2 mb-0 border-bottom">Kas tersedia Rp 412.750.000 &mdash; cukup untuk pencairan</dd>
          <dt class="col-sm-4 fw-normal text-body-secondary py-2 border-bottom">Kelengkapan dokumen</dt>
          <dd class="col-sm-8 py-2 mb-0 border-bottom">6 dari 6 dokumen lengkap &middot; form kesediaan autodebet valid</dd>
          <dt class="col-sm-4 fw-normal text-body-secondary py-2 border-bottom">Kolateral</dt>
          <dd class="col-sm-8 py-2 mb-0 border-bottom">Tidak diperlukan untuk produk PJ-RG</dd>
          <dt class="col-sm-4 fw-normal text-body-secondary py-2 border-bottom">Analisis bendahara</dt>
          <dd class="col-sm-8 py-2 mb-0 border-bottom">Kolektibilitas lancar, rasio cicilan 34% dari penghasilan
            (batas 44%), riwayat pinjaman sebelumnya lunas tepat waktu.</dd>
          <dt class="col-sm-4 fw-normal text-body-secondary py-2 border-bottom">Rekomendasi</dt>
          <dd class="col-sm-8 py-2 mb-0 border-bottom"><span class="badge text-bg-success">Direkomendasikan untuk
            disetujui</span></dd>
        </dl>
        <div class="row g-4 mt-4 text-center small">
          <div class="col-4">Diajukan oleh,<div class="border-bottom mt-4 mb-1"></div>Siti Rahmawati (Bendahara)</div>
          <div class="col-4">Diperiksa oleh,<div class="border-bottom mt-4 mb-1"></div>Pengurus Unit Simpan Pinjam</div>
          <div class="col-4">Diputuskan oleh,<div class="border-bottom mt-4 mb-1"></div>Dr. Hendra Wijaya (Ketua)</div>
        </div>
      </div>
    </div>"""

    form = """<form class="row g-3">
  <div class="col-md-6">
    <label class="form-label" for="rjns">Sikap bendahara</label>
    <select class="form-select" id="rjns">
      <option selected>Rekomendasikan untuk disetujui</option>
      <option>Rekomendasikan dengan catatan</option>
      <option>Tunda &mdash; minta kelengkapan</option>
      <option>Tidak direkomendasikan</option>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="rprio">Usulan urutan prioritas</label>
    <select class="form-select" id="rprio">
      <option selected>1 &mdash; keperluan pendidikan anak (tenggat Desember 2026)</option>
      <option>2 &mdash; kesehatan</option>
      <option>3 &mdash; modal usaha</option>
      <option>4 &mdash; lainnya</option>
    </select>
  </div>
  <div class="col-12">
    <label class="form-label" for="rnota">Catatan untuk ketua</label>
    <textarea class="form-control" id="rnota" rows="3">Mohon diputuskan sebelum 15 November 2026 agar biaya masuk
sekolah anak dapat dibayar tepat waktu. Kolateral tidak diperlukan untuk produk PJ-RG; anggota bersedia autodebet.</textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="rlamp">Lampiran</label>
    <input type="file" class="form-control" id="rlamp" multiple>
    <div class="form-text">Tabel simulasi, kartu piutang pinjaman lama, hasil pemeriksaan kas.</div>
  </div>
  <div class="col-md-6 d-flex align-items-end">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="rtembus" checked>
      <label class="form-check-label" for="rtembus">Kirim salinan rekomendasi ke e-mail ketua dan arsip koperasi</label>
    </div>
  </div>
  <div class="col-12 d-flex flex-wrap gap-2">
    <a class="btn btn-primary" href="../ketua/persetujuan.html"><i class="bi bi-send"></i> Kirim ke ketua koperasi</a>
    <button class="btn btn-outline-secondary" type="button"><i class="bi bi-printer"></i> Cetak lembar rekomendasi</button>
    <a class="btn btn-link" href="pengajuan.html">Kembali ke daftar pengajuan</a>
  </div>
</form>"""

    body = f"""
{stepper(STEP, 2)}
{note("<strong>Langkah 6:</strong> berdasarkan hasil pemeriksaan kas dan dokumen, bendahara merekomendasikan "
      "pengajuan kepada ketua koperasi. Revisi / penolakan ketua akan kembali terlihat di halaman ini "
      "(status pengajuan pada tabel riwayat).")}
<div class="row g-4">
  <div class="col-xl-8">
    {card("Lembar rekomendasi &mdash; PGJ-2026-0088", lembar,
          subtitle="Dokumen ini yang dibaca ketua sebelum memutuskan",
          tools='<button class="btn btn-sm btn-outline-secondary" type="button"><i class="bi bi-printer"></i> Cetak</button>')}
    {card("Form pengiriman rekomendasi", form, subtitle="Langkah 6 &mdash; kirim ke meja ketua")}
  </div>
  <div class="col-xl-4">
    {card("Ringkasan kas saat mengajukan rekomendasi", dl([
        ("Kas tersedia", money(KAS["saldo_kas"])),
        ("Komitmen pencairan", money(88_500_000)),
        ("Kebutuhan pengajuan ini", money(SKENARIO["P"])),
        ("Batas kas minimum", money(150_000_000)),
        ("Status", badge("Cukup", "success")),
    ]))}
    {card("Alur setelah rekomendasi dikirim", alur)}
  </div>
  <div class="col-12">
    {card("Riwayat rekomendasi bendahara", riwayat_rek,
          subtitle="Termasuk hasil keputusan ketua: disetujui, revisi, atau ditolak")}
  </div>
</div>"""
    return layout("admin", "rekomendasi.html", "Rekomendasi ke Ketua Koperasi", "Langkah 6",
                  body, desc="Lembar rekomendasi dan pengiriman keputusan ke ketua",
                  crumb=[("Back Office Bendahara", "dashboard.html"), ("Rekomendasi ke ketua", None)],
                  actions='<a class="btn btn-outline-primary" href="../ketua/daftar-peminjam.html">'
                          'Lihat layar ketua</a>')


add("admin/dashboard.html", "admin", "Dashboard Bendahara", dashboard())
add("admin/simulasi-masuk.html", "admin", "Simulasi Masuk", simulasi_masuk())
add("admin/pengajuan.html", "admin", "Verifikasi Pengajuan", pengajuan())
add("admin/rekomendasi.html", "admin", "Rekomendasi ke Ketua", rekomendasi())
