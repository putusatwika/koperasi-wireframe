"""Halaman role: KETUA KOPERASI dan PENGAWAS KOPERASI."""
from ksu_core import card, tbl, kpi_row, note, stepper, dl, money, pct, badge, st, bars
from ksu_data import KAS, ANGGOTA, PRODUK, SIM, SKENARIO, PENGAJUAN, PINJAMAN_AKTIF, DAFTAR_ANGGOTA
from ksu_shell import add, layout

URGENSI_COLOR = {"Tinggi": "danger", "Sedang": "warning", "Rendah": "secondary"}
STEP_KETUA = ["Bendahara memverifikasi", "Rekomendasi ke ketua", "Keputusan ketua", "Pencairan bendahara"]
STEP_PENGAWAS = ["Pencairan pinjaman", "Cicilan & autodebet", "Pengawasan portofolio"]


def urg(x):
    return badge(x, URGENSI_COLOR.get(x, "secondary"))


# ==================================================================== KETUA: dashboard
def k_dashboard():
    menunggu = [p for p in PENGAJUAN if p["status"] in ("Menunggu Persetujuan", "Direkomendasikan")]
    nilai = sum(p["pokok"] for p in menunggu)
    kpi = kpi_row([
        ("Menunggu keputusan saya", str(len(menunggu)) + " pengajuan", "nilai " + money(nilai), "warning", "bi-inbox"),
        ("Kas koperasi saat ini", money(KAS["saldo_kas"]), "kas minimum kebijakan " + money(150_000_000), "success", "bi-safe2"),
        ("Disetujui bulan ini", "3 pinjaman", "nilai " + money(29_500_000), "primary", "bi-check2-circle"),
        ("Revisi / ditolak bulan ini", "2 pengajuan", "1 revisi tenor, 1 penolakan", "danger", "bi-arrow-counterclockwise"),
    ])

    rows = []
    prio = 1
    for p in menunggu:
        rows.append([str(prio), p["id"], p["nama"] + '<div class="small text-body-secondary">' + p["anggota"] + "</div>",
                     p["keperluan"], urg(p["urgensi"]), money(p["pokok"]), str(p["tenor"]) + " bln", p["sifat"],
                     st(p["status"]),
                     '<a class="btn btn-sm btn-primary" href="persetujuan.html">Buka permohonan</a>'])
        prio += 1
    tabel = tbl(["Prioritas", "No. pengajuan", "Anggota", "Keperluan", "Urgensi", "Pokok", "Tenor",
                 "Sifat bayar", "Status", "Aksi"], rows,
                footer="Urutan prioritas diusulkan bendahara dan dapat disesuaikan ketua (Langkah 7).")

    kas = bars([("Kas tersedia", money(KAS["saldo_kas"]), 88), ("Komitmen pencairan", money(149_500_000), 32),
                ("Piutang berjalan", money(KAS["piutang_berjalan"]), 100)])

    revisi = """<ul class="list-group list-group-flush small">
        <li class="list-group-item px-0">08 Okt 2026 &mdash; PGJ-2026-0088 (Budi Santoso): minta tabel angsuran 36 bulan.
          <span class="badge text-bg-warning">Menunggu hitung ulang bendahara</span></li>
        <li class="list-group-item px-0">02 Okt 2026 &mdash; PGJ-2026-0084 (Joko Prasetyo): ditolak karena rasio cicilan 51%.
          <span class="badge text-bg-danger">Selesai</span></li>
        <li class="list-group-item px-0">26 Sep 2026 &mdash; PGJ-2026-0079 (Marlina Sari): setujui dengan catatan
          wajib kolateral tambahan. <span class="badge text-bg-success">Selesai</span></li>
      </ul>"""

    body = f"""
{kpi}
<div class="row g-4">
  <div class="col-xl-8">
    {card("Menunggu keputusan ketua", tabel,
          subtitle="Diurutkan menurut prioritas keperluan yang diusulkan bendahara",
          tools='<a class="btn btn-sm btn-outline-primary" href="daftar-peminjam.html">Lihat semua peminjam</a>')}
  </div>
  <div class="col-xl-4">
    {card("Posisi kas koperasi", kas, subtitle="Bahan pertimbangan sebelum menyetujui pencairan")}
    {card("Revisi &amp; penolakan yang saya minta", revisi,
          subtitle="Bendahara melihat catatan ini untuk menindaklanjuti")}
    {card("Ringkasan portofolio simpan pinjam", dl([
        ("Pinjaman aktif", "214 pinjaman"),
        ("Piutang berjalan", money(KAS["piutang_berjalan"])),
        ("Piutang macet", money(KAS["piutang_macet"])),
        ("Rasio kredit bermasalah", "1,48% dari piutang"),
        ("Anggota terdaftar", str(KAS["jumlah_anggota"]) + " orang"),
    ]))}
  </div>
</div>"""
    return layout("ketua", "dashboard.html", "Dashboard Ketua Koperasi", "Langkah 7&ndash;8",
                  body, desc="Ringkasan permohonan yang menunggu keputusan dan posisi kas koperasi",
                  crumb=[("Ruang Ketua", "dashboard.html"), ("Dashboard", None)],
                  actions='<a class="btn btn-primary" href="persetujuan.html">'
                          '<i class="bi bi-check2-square"></i> Ambil keputusan</a>')


# ==================================================================== KETUA: daftar peminjam
def k_daftar():
    data = [
        (1, "Budi Santoso", "AG-0142", "Biaya pendidikan anak (S2)", "Tinggi", 25_000_000, 24, "Menurun 1,10%", "Menunggu Persetujuan", "Lancar"),
        (2, "Ahmad Fauzi", "AG-0117", "Biaya pengobatan orang tua", "Tinggi", 5_000_000, 10, "Tetap 0,90%", "Disetujui", "Lancar"),
        (3, "Ratna Kusumawati", "AG-0231", "Beli mobil untuk mobilitas kerja", "Sedang", 120_000_000, 36, "Anuitas 1,35%", "Menunggu Persetujuan", "Lancar"),
        (4, "Dewi Lestari", "AG-0264", "Biaya pendidikan anak (S1)", "Tinggi", 15_000_000, 12, "Menurun 1,00%", "Dicairkan", "Lancar"),
        (5, "Sri Handayani", "AG-0305", "Renovasi ringan rumah", "Rendah", 18_000_000, 18, "Menurun 1,00%", "Menunggu Persetujuan", "Perhatian khusus"),
        (6, "Joko Prasetyo", "AG-0098", "Tambah modal usaha keluarga", "Rendah", 40_000_000, 30, "Anuitas 1,10%", "Ditolak", "Perhatian khusus"),
    ]
    rows = []
    for p, nama, ag, kep, ug, pokok, tenor, bunga, status, kolek in data:
        rows.append([('<span class="badge text-bg-primary rounded-pill">' + str(p) + "</span>"), nama,
                     '<span class="small text-body-secondary">' + ag + "</span>", kep, urg(ug), money(pokok),
                     str(tenor) + " bln", bunga, money(pokok / tenor), kolek, st(status),
                     '<a class="btn btn-sm btn-outline-primary" href="persetujuan.html">Detail</a>'])
    tabel = tbl(["Prioritas", "Anggota", "No. anggota", "Keperluan (urgensi)", "Tingkat urgensi", "Pokok",
                 "Tenor", "Metode bunga", "Angsuran/bln", "Kolektibilitas", "Status", "Aksi"], rows,
                footer="Prioritas 1&ndash;6 disusun dari urgensi keperluan, kolektibilitas, dan tenggat waktu "
                       "kebutuhan dana. Kriteria ini perlu dikonfirmasi pengurus.")

    kas_impact = tbl(["Skenario keputusan", "Nilai pencairan", "Kas setelah pencairan", "Status kas"],
                     [["Setujui 3 pengajuan berstatus menunggu", money(163_000_000),
                       money(KAS["saldo_kas"] - 163_000_000), badge("Di atas kas minimum", "success")],
                      ["Setujui semua permohonan bulan ini (5 pengajuan)", money(197_000_000),
                       money(KAS["saldo_kas"] - 197_000_000), badge("Di atas kas minimum", "success")],
                      ["Setujui termasuk multiguna Rp 120 jt", money(316_500_000),
                       money(KAS["saldo_kas"] - 316_500_000), badge("Tunda sampai dana masuk", "warning")]],
                     footer="Kas terikat (jaminan & komitmen) sebesar " + money(KAS["kas_terikat"]) +
                            " tidak diperhitungkan sebagai dana bebas.")

    body = f"""
{note("<strong>Langkah 7:</strong> ketua koperasi dapat melihat tabel peminjam, jumlah kas saat ini, dan urutan "
      "berdasarkan prioritas keperluan. Tabel di bawah adalah layar read-only yang menjadi dasar keputusan "
      "(Langkah 8).")}
{kpi_row([
    ("Kas saat ini", money(KAS["saldo_kas"]), "minimum kebijakan " + money(150_000_000), "success", "bi-safe2"),
    ("Kas bebas setelah komitmen", money(KAS["saldo_kas"] - 149_500_000), "komitmen pencairan " + money(149_500_000), "warning", "bi-wallet2"),
    ("Peminjam berjalan", "214 anggota", "piutang " + money(KAS["piutang_berjalan"]), "primary", "bi-people"),
    ("Menunggu keputusan", "3 pengajuan", "nilai " + money(163_000_000), "info", "bi-inbox"),
])}
<div class="row g-4">
  <div class="col-12">
    {card("Daftar peminjam menurut prioritas keperluan", tabel,
          tools='<div class="d-flex gap-2"><select class="form-select form-select-sm"><option>Semua urgensi</option>'
                '<option>Tinggi</option><option>Sedang</option><option>Rendah</option></select>'
                '<select class="form-select form-select-sm"><option>Semua status</option><option>Menunggu Persetujuan</option>'
                '<option>Disetujui</option><option>Dicairkan</option></select>'
                '<button class="btn btn-sm btn-outline-secondary" type="button">Terapkan</button></div>')}
  </div>
  <div class="col-xl-8">
    {card("Dampak keputusan terhadap kas koperasi", kas_impact,
          subtitle="Simulasi cepat sebelum menyetujui pencairan")}
  </div>
  <div class="col-xl-4">
    {card("Dasar urutan prioritas", "@PRIO@",
          subtitle="Usulan bendahara &mdash; mohon dikonfirmasi ketua")}
  </div>
</div>"""

    prio = """<ol class="ps-3 mb-0 small">
        <li><strong>Urgensi keperluan</strong> &mdash; pendidikan/kesehatan di atas konsumtif.</li>
        <li><strong>Tenggat kebutuhan dana</strong> &mdash; semakin dekat semakin tinggi.</li>
        <li><strong>Kolektibilitas anggota</strong> &mdash; riwayat cicilan lancar.</li>
        <li><strong>Rasio cicilan terhadap penghasilan</strong> &mdash; maksimal 44%.</li>
        <li><strong>Jumlah pinjaman &amp; dampak ke kas</strong> &mdash; menjaga kas minimum.</li>
      </ol>"""
    body = body.replace("@PRIO@", prio)

    return layout("ketua", "daftar-peminjam.html", "Daftar Peminjam &amp; Prioritas", "Langkah 7",
                  body, desc="Tabel peminjam, posisi kas, dan urutan prioritas keperluan",
                  crumb=[("Ruang Ketua", "dashboard.html"), ("Daftar peminjam", None)],
                  actions='<a class="btn btn-outline-primary" href="pencairan.html">Monitoring pencairan</a>')


# ==================================================================== KETUA: persetujuan
def k_persetujuan():
    ident = dl([
        ("Nama anggota", ANGGOTA["nama"] + " (" + ANGGOTA["no_anggota"] + ")"),
        ("Unit kerja", ANGGOTA["unit"]),
        ("Produk &amp; metode bunga", "PJ-RG &middot; menurun (efektif) 1,10%/bulan"),
        ("Pokok / tenor", money(SKENARIO["P"]) + " / 24 bulan"),
        ("Angsuran pertama", money(SIM["menurun"]["rows"][0]["angs"]) + " (menurun tiap bulan)"),
        ("Sifat pembayaran", "Autodebet BSI 7100 2299 441"),
        ("Keperluan", "Biaya pendidikan anak (S2)"),
        ("Tingkat urgensi", urg("Tinggi")),
        ("Tenggat kebutuhan dana", "15 November 2026"),
    ])

    verifikasi = dl([
        ("Kelengkapan dokumen", badge("6 dari 6 lengkap", "success")),
        ("Form kesediaan autodebet", badge("Diverifikasi bendahara", "success")),
        ("Kolateral", "Tidak diperlukan untuk produk PJ-RG"),
        ("Kas tersedia", money(KAS["saldo_kas"]) + " (cukup)"),
        ("Kas setelah pencairan", money(KAS["saldo_kas"] - SKENARIO["P"])),
        ("Kolektibilitas anggota", badge("Lancar", "success") + " &middot; 8/8 cicilan tepat waktu"),
        ("Rasio cicilan / penghasilan", "34% (batas 44%)"),
        ("Rekomendasi bendahara", badge("Direkomendasikan", "success")),
    ])

    dokumen = tbl(["Dokumen", "Status", "Aksi"],
                  [["Scan KTP pemohon", badge("Ada", "success"),
                    '<button class="btn btn-sm btn-outline-secondary" type="button">Buka</button>'],
                   ["Slip gaji / SK 3 bulan", badge("Ada", "success"),
                    '<button class="btn btn-sm btn-outline-secondary" type="button">Buka</button>'],
                   ["Form kesediaan autodebet bermaterai", badge("Ada", "success"),
                    '<button class="btn btn-sm btn-outline-secondary" type="button">Buka</button>'],
                   ["Rekening koran 3 bulan", badge("Ada", "success"),
                    '<button class="btn btn-sm btn-outline-secondary" type="button">Buka</button>'],
                   ["Dokumen pendukung keperluan", badge("Ada", "success"),
                    '<button class="btn btn-sm btn-outline-secondary" type="button">Buka</button>'],
                   ["Lembar rekomendasi bendahara", badge("Ada", "success"),
                    '<button class="btn btn-sm btn-outline-secondary" type="button">Buka</button>']])

    tab_setuju = """<p class="small">Menyetujui pinjaman sesuai pengajuan. Bendahara akan menyiapkan dana dan
        bukti pencairan.</p>
      <div class="mb-3">
        <label class="form-label" for="sacc">Catatan persetujuan (opsional)</label>
        <textarea class="form-control" id="sacc" rows="2">Disetujui. Mohon pencairan dilakukan paling lambat
15 November 2026.</textarea>
      </div>
      <button class="btn btn-success" type="button"><i class="bi bi-check2"></i> Setujui pinjaman</button>"""

    tab_revisi = """<p class="small">Menyetujui dengan perubahan. Revisi terlihat oleh bendahara (untuk hitung ulang
        tabel angsuran) dan anggota.</p>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label" for="rtenor">Tenor menjadi (bulan)</label>
          <input type="number" class="form-control" id="rtenor" value="36">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="rplafon">Pokok menjadi</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="number" class="form-control" id="rplafon" value="20000000" step="1000000">
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="rsifat">Sifat bayar</label>
          <select class="form-select" id="rsifat">
            <option selected>Autodebet (tetap)</option>
            <option>Bayar per bulan</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label" for="rcat">Alasan revisi (wajib)</label>
          <textarea class="form-control" id="rcat" rows="2">Angsuran 24 bulan membuat rasio cicilan terlalu tinggi
karena ada cicilan kendaraan. Ubah tenor menjadi 36 bulan.</textarea>
        </div>
        <div class="col-12">
          <label class="form-label" for="rlamp2">Lampiran tambahan yang diminta</label>
          <select class="form-select" id="rlamp2" multiple size="3">
            <option selected>Surat pernyataan penghasilan keluarga</option>
            <option>Bukti kolateral tambahan</option>
            <option>Rekening koran pasangan</option>
          </select>
        </div>
      </div>
      <button class="btn btn-warning mt-3" type="button"><i class="bi bi-arrow-repeat"></i> Kirim revisi ke bendahara</button>"""

    tab_tolak = """<p class="small">Menolak pengajuan dengan alasan yang jelas. Alasan tampil di portal anggota.</p>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="talasan">Kategori alasan</label>
          <select class="form-select" id="talasan">
            <option>Rasio cicilan melebihi batas</option>
            <option>Masih ada pinjaman aktif / tunggakan</option>
            <option>Keperluan belum mendesak</option>
            <option>Dokumen tidak dapat diverifikasi</option>
            <option>Kas koperasi belum mencukupi</option>
            <option>Lainnya</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="tkesempatan">Dapat mengajukan kembali</label>
          <input type="text" class="form-control" id="tkesempatan" value="Setelah PJM-2025-0114 lunas (Des 2026)">
        </div>
        <div class="col-12">
          <label class="form-label" for="tcat">Uraian alasan penolakan (wajib)</label>
          <textarea class="form-control" id="tcat" rows="2"></textarea>
        </div>
      </div>
      <button class="btn btn-danger mt-3" type="button"><i class="bi bi-x-circle"></i> Tolak pengajuan</button>"""

    tabs = """<ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab"
          data-bs-target="#tabSetuju" type="button" role="tab">Setujui</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab"
          data-bs-target="#tabRevisi2" type="button" role="tab">Setujui dengan revisi</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab"
          data-bs-target="#tabTolak2" type="button" role="tab">Tolak</button></li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="tabSetuju" role="tabpanel">""" + tab_setuju + """</div>
        <div class="tab-pane fade" id="tabRevisi2" role="tabpanel">""" + tab_revisi + """</div>
        <div class="tab-pane fade" id="tabTolak2" role="tabpanel">""" + tab_tolak + """</div>
      </div>"""

    riwayat = tbl(["Tanggal", "No. pengajuan", "Anggota", "Pokok", "Keputusan", "Catatan"],
                  [["05 Okt 2026", "PGJ-2026-0086", "Ahmad Fauzi", money(5_000_000), badge("Disetujui", "success"),
                    "Disetujui tanpa revisi"],
                   ["02 Okt 2026", "PGJ-2026-0084", "Joko Prasetyo", money(40_000_000), badge("Ditolak", "danger"),
                    "Rasio cicilan 51%, melebihi batas 44%"],
                   ["26 Sep 2026", "PGJ-2026-0079", "Marlina Sari", money(9_500_000),
                    badge("Disetujui dengan Revisi", "success"), "Wajib kolateral tambahan"],
                   ["20 Sep 2026", "PGJ-2026-0076", "Yusuf Hamid", money(12_000_000), badge("Disetujui", "success"),
                    "Disetujui, pencairan bertahap"]])

    body = f"""
{stepper(STEP_KETUA, 3)}
{note("<strong>Langkah 8:</strong> ketua melakukan persetujuan untuk diserahkan kembali kepada bendahara. Ketua dapat "
      "menyetujui, memberi <strong>revisi</strong> (terlihat bendahara untuk hitung ulang), atau "
      "<strong>menolak</strong> dengan alasan tertentu.")}
<div class="row g-4">
  <div class="col-xl-8">
    {card("Permohonan yang diputuskan &mdash; PGJ-2026-0088", ident,
          subtitle="Ringkasan permohonan Budi Santoso (urgensi tinggi)",
          tools='<span class="badge text-bg-warning">Menunggu keputusan</span>')}
    {card("Keputusan ketua koperasi", tabs, subtitle="Tiga jalur keputusan: setujui, revisi, atau tolak")}
    {card("Riwayat keputusan saya", riwayat, subtitle="Jejak keputusan untuk audit pengawas")}
  </div>
  <div class="col-xl-4">
    {card("Hasil verifikasi bendahara", verifikasi,
          subtitle="Dasar penilaian sebelum mengambil keputusan")}
    {card("Dokumen lampiran", dokumen, subtitle="Semua berkas dapat dibuka tanpa mengunduh")}
    {card("Konsekuensi keputusan", "@KONSEKUENSI@")}
  </div>
</div>"""

    kons = """<ul class="ps-3 mb-0 small">
        <li><strong>Setujui</strong> &mdash; bendahara menyiapkan dana &amp; bukti pencairan (Langkah 9).</li>
        <li><strong>Revisi</strong> &mdash; bendahara menghitung ulang tabel angsuran, anggota menerima
          pemberitahuan.</li>
        <li><strong>Tolak</strong> &mdash; anggota melihat alasan penolakan di portalnya.</li>
        <li>Semua keputusan tercatat pada kartu pengajuan dan log audit koperasi.</li>
      </ul>"""
    body = body.replace("@KONSEKUENSI@", kons)

    return layout("ketua", "persetujuan.html", "Persetujuan Pinjaman", "Langkah 8",
                  body, desc="Setujui, revisi, atau tolak permohonan pinjaman anggota",
                  crumb=[("Daftar peminjam", "daftar-peminjam.html"), ("Persetujuan", None)])


# ==================================================================== KETUA: pencairan
def k_pencairan():
    tabel = tbl(["Tanggal cair", "No. bukti", "Anggota", "Pokok dicairkan", "Sumber dana", "Dicairkan oleh",
                 "Bukti transaksi", "Status"],
                [["09 Okt 2026", "PC-2026-0033", "Dewi Lestari", money(9_500_000), "Kas koperasi", "S. Rahmawati",
                  '<a href="#">slip_transfer.pdf</a>', st("Dicairkan")],
                 ["08 Okt 2026", "PC-2026-0032", "Ahmad Fauzi", money(4_800_000), "Kas koperasi", "S. Rahmawati",
                  '<a href="#">slip_transfer.pdf</a>', st("Dicairkan")],
                 ["02 Okt 2026", "PC-2026-0031", "Budi Santoso", money(25_000_000), "Simpanan anggota", "S. Rahmawati",
                  '<a href="#">slip_transfer.pdf</a>', st("Dicairkan")],
                 ["28 Sep 2026", "PC-2026-0030", "Marlina Sari", money(9_500_000), "Kas koperasi", "S. Rahmawati",
                  '<span class="text-body-secondary">belum diunggah</span>', badge("Menunggu bukti", "warning")]],
                footer="Ketua memantau tindak lanjut persetujuannya sampai dana benar-benar cair.")

    body = f"""
{stepper(STEP_KETUA, 4)}
{kpi_row([
    ("Pencairan bulan ini", "4 pinjaman", "nilai " + money(45_200_000), "primary", "bi-cash-coin"),
    ("Menunggu bukti transaksi", "1 pinjaman", "bendahara diminta melengkapi", "warning", "bi-file-earmark-x"),
    ("Dana bersih diterima anggota", money(44_975_000), "setelah biaya administrasi", "success", "bi-wallet2"),
    ("Kas koperasi sekarang", money(KAS["saldo_kas"]), "setelah seluruh pencairan", "info", "bi-safe2"),
])}
<div class="row g-4">
  <div class="col-12">
    {card("Peminjaman yang sudah dicairkan", tabel,
          tools='<button class="btn btn-sm btn-outline-secondary" type="button">Ekspor XLSX</button>')}
  </div>
  <div class="col-xl-6">
    {card("Ringkasan pencairan per sumber dana",
          bars([("Kas koperasi", money(39_800_000), 88), ("Simpanan anggota", money(25_000_000), 55),
                ("Dana bank", "Rp 0", 5)]))}
  </div>
  <div class="col-xl-6">
    {card("Catatan ketua", "@CATKETUA@")}
  </div>
</div>"""

    cat = """<ul class="list-group list-group-flush small">
        <li class="list-group-item px-0">02 Okt 2026 &mdash; pencairan Rp 25,0 jt kepada Budi Santoso sesuai
          persetujuan saya.</li>
        <li class="list-group-item px-0">05 Okt 2026 &mdash; minta bendahara melampirkan bukti transfer untuk
          PC-2026-0030.</li>
      </ul>"""
    body = body.replace("@CATKETUA@", cat)

    return layout("ketua", "pencairan.html", "Monitoring Pencairan", "Langkah 9",
                  body, desc="Pantau realisasi pencairan pinjaman yang telah disetujui",
                  crumb=[("Ruang Ketua", "dashboard.html"), ("Pencairan", None)])


# ==================================================================== PENGAWAS: dashboard
def p_dashboard():
    kpi = kpi_row([
        ("Pinjaman aktif diawasi", "214 pinjaman", "outstanding " + money(KAS["piutang_berjalan"]), "primary", "bi-journal-check"),
        ("Piutang macet", money(KAS["piutang_macet"]), "1,48% dari total piutang", "warning", "bi-graph-down"),
        ("Autodebet gagal", "7 anggota", "nominal " + money(9_240_000), "danger", "bi-x-circle"),
        ("Perubahan parameter (bulan ini)", "2 perubahan", "bunga efektif &amp; penalti lunas dini", "info", "bi-sliders"),
    ])

    temuan = tbl(["No.", "Temuan pengawasan", "Risiko", "Rekomendasi pengawas", "Status"],
                 [["1", "7 anggota autodebet gagal belum seluruhnya ditindaklanjuti (2 dari 7)",
                   badge("Sedang", "warning"), "Minta laporan tindak lanjut mingguan dari bendahara", st("Menunggu Konfirmasi")],
                  ["2", "2 NIK pada berkas autodebet bank tidak cocok dengan data anggota",
                   badge("Sedang", "warning"), "Perbaiki data anggota &amp; verifikasi ulang NIK", st("Menunggu Konfirmasi")],
                  ["3", "Bukti transaksi pencairan PC-2026-0030 belum diunggah",
                   badge("Tinggi", "danger"), "Lengkapi bukti sebelum tutup buku bulan ini", st("Menunggu Konfirmasi")],
                  ["4", "Bunga efektif naik dari 1,05% menjadi 1,10%/bulan pada 01 Okt 2026",
                   badge("Rendah", "secondary"), "Pastikan sosialisasi ke anggota &amp; simulasi baru memakai tarif terbaru", st("Diterima")],
                  ["5", "Kas koperasi setelah seluruh komitmen " + money(KAS["saldo_kas"] - 149_500_000),
                   badge("Rendah", "secondary"), "Pantau agar tetap di atas kas minimum " + money(150_000_000), st("Diterima")]])

    kolek = bars([("Lancar", "205 pinjaman", 96), ("Perhatian khusus", "7 pinjaman", 3),
                  ("Kurang lancar", "2 pinjaman", 1)], "primary")

    audit = tbl(["Waktu", "Objek audit", "Aktivitas", "Pelaku", "Hasil"],
                [["10 Okt 2026 09:15", "File autodebet Okt 2026", "Upload 214 baris berkas bank", "S. Rahmawati",
                  badge("214 baris, 7 gagal", "warning")],
                 ["09 Okt 2026 16:40", "Rekomendasi PGJ-2026-0088", "Pengiriman rekomendasi ke ketua", "S. Rahmawati",
                  badge("Sesuai prosedur", "success")],
                 ["09 Okt 2026 14:05", "Pencairan PJM-2026-0033", "Pencairan Rp 9,5 jt + bukti transfer", "S. Rahmawati",
                  badge("Bukti lengkap", "success")],
                 ["01 Okt 2026 09:10", "Parameter bunga &amp; penalti", "Perubahan parameter produk", "S. Rahmawati",
                  badge("Ada SK pengurus", "success")]])

    body = f"""
{kpi}
<div class="row g-4">
  <div class="col-xl-8">
    {card("Temuan &amp; rekomendasi pengawasan", temuan,
          subtitle="Hasil pengawasan atas proses simpan pinjam bulan ini",
          tools='<button class="btn btn-sm btn-outline-secondary" type="button">Cetak laporan pengawasan</button>')}
    {card("Jejak audit aktivitas koperasi", audit,
          subtitle="Pengawas memverifikasi setiap langkah: simulasi, verifikasi, persetujuan, pencairan, autodebet")}
  </div>
  <div class="col-xl-4">
    {card("Kualitas portofolio pinjaman", kolek, subtitle="Klasifikasi kolektibilitas 214 pinjaman aktif")}
    {card("Ringkasan keuangan yang diawasi", dl([
        ("Kas koperasi", money(KAS["saldo_kas"])),
        ("Kas terikat", money(KAS["kas_terikat"])),
        ("Piutang berjalan", money(KAS["piutang_berjalan"])),
        ("Piutang macet", money(KAS["piutang_macet"])),
        ("Simpanan anggota", money(KAS["simpanan_anggota"])),
        ("Dana disalurkan bulan ini", money(45_200_000)),
    ]))}
    {card("Akses pengawas", "@AKSES@")}
  </div>
</div>"""

    akses = """<p class="small mb-2">Pengawas bersifat <strong>read-only</strong> &mdash; tidak mengubah transaksi, "
      "tetapi dapat:</p>
      <ul class="ps-3 mb-0 small">
        <li>Melihat daftar peminjam &amp; informasi peminjaman yang dicairkan (Langkah 12).</li>
        <li>Membuka kartu piutang tiap anggota.</li>
        <li>Menelusuri perubahan parameter &amp; jejak audit.</li>
        <li>Mengunduh laporan untuk rapat pengurus.</li>
      </ul>"""
    body = body.replace("@AKSES@", akses)

    return layout("pengawas", "dashboard.html", "Dashboard Pengawasan", "Langkah 12",
                  body, desc="Pengawasan portofolio pinjaman, pencairan, dan kepatuhan prosedur",
                  crumb=[("Ruang Pengawas", "dashboard.html"), ("Dashboard", None)],
                  actions='<a class="btn btn-primary" href="daftar-peminjam.html">'
                          '<i class="bi bi-table"></i> Daftar peminjam</a>')


# ==================================================================== PENGAWAS: daftar peminjam
def p_daftar():
    rows = []
    for i, (no, nama, unit, simpanan, pinjaman, status) in enumerate(DAFTAR_ANGGOTA, 1):
        sisa = max(pinjaman - int(pinjaman * 0.33), 0) if pinjaman else 0
        rows.append([str(i), nama, '<span class="small text-body-secondary">' + no + "</span>", unit,
                     money(simpanan), money(pinjaman), money(sisa), "88%", badge("Lancar", "success"),
                     st("Aktif" if pinjaman else "Tidak ada pinjaman"),
                     '<button class="btn btn-sm btn-outline-secondary" type="button">Kartu piutang</button>'])
    tabel = tbl(["No.", "Nama peminjam", "No. anggota", "Unit kerja", "Simpanan", "Pokok pinjaman",
                 "Sisa pokok", "Progres cicilan", "Kolektibilitas", "Status pinjaman", "Aksi"], rows,
                footer="Data dummy 8 peminjam dari 214 pinjaman aktif. Pengawas dapat menyaring dan mengekspor "
                       "daftar ini untuk laporan rapat pengurus.")

    body = f"""
{kpi_row([
    ("Daftar peminjam", "214 anggota", "pinjaman aktif per 10 Okt 2026", "primary", "bi-people"),
    ("Outstanding", money(KAS["piutang_berjalan"]), "sisa pokok seluruh pinjaman", "info", "bi-cash-coin"),
    ("Rata-rata pinjaman", money(5_821_495), "per anggota peminjam", "secondary", "bi-calculator"),
    ("Pinjaman lunas tahun ini", "37 pinjaman", "nilai " + money(412_000_000), "success", "bi-check2-circle"),
])}
<div class="row g-4">
  <div class="col-12">
    {card("Tabel daftar peminjam (read-only)", tabel,
          tools='<div class="d-flex gap-2"><input type="search" class="form-control form-control-sm" '
                'placeholder="Cari nama / no. anggota"><select class="form-select form-select-sm">'
                '<option>Semua unit kerja</option><option>Fakultas Teknik</option><option>Bagian Umum</option></select>'
                '<button class="btn btn-sm btn-outline-secondary" type="button">Ekspor</button></div>')}
  </div>
  <div class="col-xl-6">
    {card("Sebaran pinjaman per unit kerja",
          bars([("Fakultas Teknik", "62 pinjaman", 62), ("Fakultas Ekonomi", "48 pinjaman", 48),
                ("Bagian Umum", "41 pinjaman", 41), ("Unit lain", "63 pinjaman", 63)]))}
  </div>
  <div class="col-xl-6">
    {card("Catatan pengawasan atas daftar ini", "@CAT@")}
  </div>
</div>"""

    cat = """<ul class="list-group list-group-flush small">
        <li class="list-group-item px-0">2 pinjaman berstatus kolektibilitas <em>kurang lancar</em> &mdash; dalam
          pemantauan bendahara.</li>
        <li class="list-group-item px-0">Anggota dengan simpanan besar tetapi pinjaman menunggak perlu perhatian
          khusus.</li>
        <li class="list-group-item px-0">Daftar ini menjadi lampiran laporan pengawasan bulanan ke pengurus.</li>
      </ul>"""
    body = body.replace("@CAT@", cat)

    return layout("pengawas", "daftar-peminjam.html", "Daftar Peminjam", "Langkah 12",
                  body, desc="Tabel peminjam dan posisi pinjaman untuk pengawasan",
                  crumb=[("Ruang Pengawas", "dashboard.html"), ("Daftar peminjam", None)],
                  actions='<a class="btn btn-outline-primary" href="pencairan.html">Monitoring pencairan</a>')


# ==================================================================== PENGAWAS: pencairan
def p_pencairan():
    tabel = tbl(["Tanggal", "No. bukti", "No. pinjaman", "Anggota", "Pokok dicairkan", "Biaya administrasi",
                 "Dana bersih", "Metode", "Kelengkapan", "Diperiksa pengawas"],
                [["09 Okt 2026", "PC-2026-0033", "PJM-2026-0033", "Dewi Lestari", money(9_500_000), money(47_500),
                  money(9_452_500), "Transfer BSI", badge("Lengkap", "success"), st("Diterima")],
                 ["08 Okt 2026", "PC-2026-0032", "PJM-2026-0032", "Ahmad Fauzi", money(4_800_000), money(25_000),
                  money(4_775_000), "Transfer BRI", badge("Lengkap", "success"), st("Diterima")],
                 ["02 Okt 2026", "PC-2026-0031", "PJM-2026-0031", "Budi Santoso", money(25_000_000), money(125_000),
                  money(24_875_000), "Transfer BSI", badge("Lengkap", "success"), st("Diterima")],
                 ["28 Sep 2026", "PC-2026-0030", "PJM-2026-0030", "Marlina Sari", money(9_500_000), money(47_500),
                  money(9_452_500), "Transfer BSI", badge("Bukti belum ada", "warning"), st("Menunggu Konfirmasi")]],
                footer="Total pencairan yang diawasi bulan ini: Rp 48.800.000 &middot; 1 berkas perlu dilengkapi.")

    body = f"""
{note("<strong>Langkah 12:</strong> pengawas koperasi dapat melihat tabel daftar peminjam dan juga informasi tentang "
      "peminjaman yang dicairkan, termasuk keberadaan bukti transaksi pencairan.")}
{kpi_row([
    ("Pencairan diawasi bulan ini", "4 pinjaman", "nilai " + money(48_800_000), "primary", "bi-cash-coin"),
    ("Bukti transaksi lengkap", "3 dari 4", "1 berkas perlu dilengkapi bendahara", "warning", "bi-file-earmark-check"),
    ("Biaya administrasi terkumpul", "Rp 245.000", "masuk kas koperasi", "success", "bi-percent"),
    ("Pencairan tertunda", "Rp 0", "semua persetujuan ketua sudah ditindaklanjuti", "info", "bi-hourglass"),
])}
<div class="row g-4">
  <div class="col-12">
    {card("Informasi peminjaman yang dicairkan", tabel,
          tools='<button class="btn btn-sm btn-outline-secondary" type="button">Ekspor PDF pengawasan</button>')}
  </div>
  <div class="col-xl-6">
    {card("Tren pencairan 6 bulan terakhir",
          bars([("Mei", "Rp 32,4 jt", 32), ("Jun", "Rp 41,0 jt", 41), ("Jul", "Rp 28,7 jt", 29),
                ("Agu", "Rp 35,2 jt", 35), ("Sep", "Rp 39,9 jt", 40), ("Okt", "Rp 45,2 jt", 45)]))}
  </div>
  <div class="col-xl-6">
    {card("Hal yang diperiksa pengawas pada setiap pencairan", "@CEK@")}
  </div>
</div>"""

    cek = """<ul class="ps-3 mb-0 small">
        <li>Persetujuan ketua ada dan sesuai nominal pencairan.</li>
        <li>Bukti transfer/slip pencairan terunggah dan terbaca.</li>
        <li>Biaya administrasi serta asuransi dihitung benar.</li>
        <li>Kartu piutang anggota terbentuk dengan tenor &amp; angsuran sesuai tabel simulasi.</li>
        <li>Metode pembayaran (autodebet / manual) tercatat sesuai pengajuan.</li>
      </ul>"""
    body = body.replace("@CEK@", cek)

    return layout("pengawas", "pencairan.html", "Monitoring Pencairan", "Langkah 12",
                  body, desc="Informasi peminjaman yang dicairkan dan pemeriksaannya",
                  crumb=[("Ruang Pengawas", "dashboard.html"), ("Monitoring pencairan", None)])


add("ketua/dashboard.html", "ketua", "Dashboard Ketua", k_dashboard())
add("ketua/daftar-peminjam.html", "ketua", "Daftar Peminjam", k_daftar())
add("ketua/persetujuan.html", "ketua", "Persetujuan Pinjaman", k_persetujuan())
add("ketua/pencairan.html", "ketua", "Monitoring Pencairan", k_pencairan())
add("pengawas/dashboard.html", "pengawas", "Dashboard Pengawasan", p_dashboard())
add("pengawas/daftar-peminjam.html", "pengawas", "Daftar Peminjam", p_daftar())
add("pengawas/pencairan.html", "pengawas", "Monitoring Pencairan", p_pencairan())
