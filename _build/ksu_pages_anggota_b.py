"""Halaman role: ANGGOTA (bagian 2) — pengajuan, status, cicilan aktif, kartu piutang, riwayat.

Disiplin generator: TIDAK ada string literal triple-quote di dalam ekspresi f-string,
karena Python < 3.12 tidak mendukungnya. Semua potongan dibangun ke variabel lokal.
"""
from ksu_core import card, tbl, kpi_row, note, stepper, dl, money, pct, badge, st, bars
from ksu_data import ANGGOTA, SIM, SKENARIO, PINJAMAN_AKTIF
from ksu_shell import add, layout

STEP = ["Simulasi", "Pengajuan", "Verifikasi Bendahara", "Persetujuan Ketua", "Pencairan"]
BULAN = ["Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt"]
BULAN12 = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"]


# --------------------------------------------------------------------- 4. form pengajuan
def pengajuan_form():
    dokumen = [
        ("Scan KTP pemohon", "ktp_budi.pdf", "Wajib", True),
        ("Slip gaji / SK 3 bulan terakhir", "slip_gaji_sep2026.pdf", "Wajib", True),
        ("Form kesediaan autodebet (bermaterai)", "kesediaan_autodebet.pdf", "Wajib", True),
        ("Rekening koran 3 bulan", "rekening_koran.pdf", "Wajib jika autodebet", True),
        ("Bukti kolateral &mdash; BPKB kendaraan", "&mdash;", "Wajib untuk pinjaman kendaraan", False),
        ("Surat kuasa potong gaji", "kuasa_potong_gaji.pdf", "Wajib jika tanpa autodebet", False),
        ("Dokumen pendukung keperluan (surat keterangan)", "sk_pendidikan_anak.pdf", "Opsional", True),
    ]
    rows_dok = []
    for nama, file, wajib, ada in dokumen:
        if ada:
            status = badge("Terunggah", "success")
            aksi = ('<div class="btn-group btn-group-sm"><button class="btn btn-outline-secondary" type="button">'
                    'Lihat</button><button class="btn btn-outline-danger" type="button">Hapus</button></div>')
            isi = '<i class="bi bi-file-earmark-pdf text-danger"></i> ' + file
        else:
            status = badge("Belum ada", "secondary", outline=True)
            aksi = '<button class="btn btn-sm btn-outline-primary" type="button"><i class="bi bi-upload"></i> Unggah</button>'
            isi = '<span class="text-body-secondary">&mdash;</span>'
        rows_dok.append([nama, wajib, isi, status, aksi])

    form = """<form class="row g-3">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center">
      <h3 class="h6 fw-bold mb-0">A. Referensi simulasi</h3>
      <span class="badge text-bg-info">SIM-2026-0142</span>
    </div>
    <hr class="mt-2">
  </div>
  <div class="col-md-4">
    <label class="form-label" for="sim">Nomor simulasi</label>
    <select class="form-select" id="sim">
      <option selected>SIM-2026-0142 &mdash; Rp 25.000.000 / 24 bln / menurun 1,10%</option>
      <option>SIM-2026-0141 &mdash; Rp 20.000.000 / 18 bln / menurun 1,10%</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">Pokok pinjaman</label>
    <input type="text" class="form-control" value="Rp 25.000.000" readonly>
  </div>
  <div class="col-md-4">
    <label class="form-label">Kisaran angsuran per bulan</label>
    <input type="text" class="form-control" value="Rp 1.466.667 s.d. Rp 1.218.750" readonly>
  </div>

  <div class="col-12 mt-4">
    <h3 class="h6 fw-bold mb-0">B. Data pemohon</h3>
    <hr class="mt-2">
  </div>
  <div class="col-md-6">
    <label class="form-label">Nama anggota</label>
    <input type="text" class="form-control" value="Budi Santoso, S.Kom., M.T." readonly>
  </div>
  <div class="col-md-3">
    <label class="form-label">No. anggota</label>
    <input type="text" class="form-control" value="AG-0142" readonly>
  </div>
  <div class="col-md-3">
    <label class="form-label">NIK</label>
    <input type="text" class="form-control" value="7371 0201 0288 0004" readonly>
  </div>
  <div class="col-md-6">
    <label class="form-label">Unit kerja</label>
    <input type="text" class="form-control" value="Fakultas Teknik &mdash; Prodi Informatika" readonly>
  </div>
  <div class="col-md-3">
    <label class="form-label" for="penghasilan">Penghasilan per bulan</label>
    <div class="input-group">
      <span class="input-group-text">Rp</span>
      <input type="number" class="form-control" id="penghasilan" value="8500000" step="500000">
    </div>
  </div>
  <div class="col-md-3">
    <label class="form-label" for="tanggungan">Jumlah tanggungan</label>
    <input type="number" class="form-control" id="tanggungan" value="3" min="0">
  </div>

  <div class="col-12 mt-4">
    <h3 class="h6 fw-bold mb-0">C. Keperluan dan urgensi pinjaman</h3>
    <hr class="mt-2">
  </div>
  <div class="col-md-6">
    <label class="form-label" for="keperluan">Keperluan pinjaman</label>
    <select class="form-select" id="keperluan">
      <option selected>Biaya pendidikan anak</option>
      <option>Renovasi / perbaikan rumah</option>
      <option>Pembelian kendaraan</option>
      <option>Biaya kesehatan / pengobatan</option>
      <option>Modal usaha keluarga</option>
      <option>Lainnya</option>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="urgensi">Tingkat urgensi</label>
    <select class="form-select" id="urgensi">
      <option selected>Tinggi &mdash; kebutuhan mendesak, ada tenggat</option>
      <option>Sedang &mdash; direncanakan 1&ndash;3 bulan ke depan</option>
      <option>Rendah &mdash; kebutuhan tidak mendesak</option>
    </select>
    <div class="form-text">Urgensi dipakai ketua koperasi sebagai dasar urutan prioritas persetujuan.</div>
  </div>
  <div class="col-12">
    <label class="form-label" for="uraian">Uraian keperluan</label>
    <textarea class="form-control" id="uraian" rows="3">Melanjutkan studi S2 anak pertama; biaya masuk dan semester
pertama jatuh pada Desember 2026. Sudah ada tabungan Rp 8.000.000, sisanya diajukan lewat koperasi.</textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="tglbutuh">Tanggal dana dibutuhkan</label>
    <input type="date" class="form-control" id="tglbutuh" value="2026-11-15">
  </div>
  <div class="col-md-6">
    <label class="form-label" for="sumber">Rencana sumber pembayaran cicilan</label>
    <select class="form-select" id="sumber">
      <option selected>Potongan gaji / tunjangan kinerja melalui autodebet bank</option>
      <option>Pendapatan usaha sampingan</option>
      <option>Penghasilan pasangan</option>
    </select>
  </div>

  <div class="col-12 mt-4">
    <h3 class="h6 fw-bold mb-0">D. Sifat pembayaran pinjaman</h3>
    <hr class="mt-2">
  </div>
  <div class="col-12">
    <div class="row g-3">
      <div class="col-lg-6">
        <div class="card h-100 border-primary">
          <div class="card-body">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="sifat" id="sa" value="autodebet" checked>
              <label class="form-check-label fw-semibold" for="sa">
                <i class="bi bi-credit-card-2-front"></i> Autodebet (potong rekening tiap bulan)</label>
            </div>
            <dl class="row g-0 mt-3 mb-0 small">
              <dt class="col-sm-5 fw-normal text-body-secondary">Rekening autodebet</dt>
              <dd class="col-sm-7 mb-0">BSI 7100 2299 441</dd>
              <dt class="col-sm-5 fw-normal text-body-secondary">Atas nama</dt>
              <dd class="col-sm-7 mb-0">BUDI SANTOSO</dd>
              <dt class="col-sm-5 fw-normal text-body-secondary">Tanggal debet</dt>
              <dd class="col-sm-7 mb-0">Tanggal 10 setiap bulan</dd>
            </dl>
            <div class="alert alert-warning small py-2 mt-3 mb-0">
              <i class="bi bi-exclamation-triangle"></i> Wajib melampirkan <strong>form kesediaan autodebet</strong>
              yang ditandatangani dan bermaterai.
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-body">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="sifat" id="sb" value="manual">
              <label class="form-check-label fw-semibold" for="sb">
                <i class="bi bi-cash-stack"></i> Bayar per bulan (transfer / setor tunai ke kasir koperasi)</label>
            </div>
            <ul class="small text-body-secondary mt-3 mb-0 ps-3">
              <li>Anggota menerima tagihan bulanan di portal.</li>
              <li>Bukti transfer wajib diunggah sebagai bukti bayar.</li>
              <li>Keterlambatan dihitung denda sesuai parameter produk.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 mt-4">
    <h3 class="h6 fw-bold mb-0">E. Dokumen persyaratan</h3>
    <hr class="mt-2">
  </div>
  <div class="col-12">
    <input type="file" class="form-control" id="unggah" multiple>
    <div class="form-text">Format PDF/JPG, maksimal 5 MB per berkas. Bendahara memeriksa kelengkapan (Langkah 5)
      dan dapat meminta berkas yang tidak terbaca untuk diunggah ulang.</div>
  </div>

  <div class="col-12 mt-4">
    <h3 class="h6 fw-bold mb-0">F. Pernyataan anggota</h3>
    <hr class="mt-2">
  </div>
  <div class="col-12">
    <div class="form-check mb-2">
      <input class="form-check-input" type="checkbox" id="p1" checked>
      <label class="form-check-label" for="p1">Saya menyetujui pengenaan <strong>denda keterlambatan</strong> sebesar
        0,50% per bulan dari nilai tunggakan sesuai jenis pinjaman.</label>
    </div>
    <div class="form-check mb-2">
      <input class="form-check-input" type="checkbox" id="p2" checked>
      <label class="form-check-label" for="p2">Saya memahami <strong>mekanisme pelunasan dipercepat</strong>:
        penalti 1% dari sisa pokok untuk pinjaman reguler (lihat tabel parameter).</label>
    </div>
    <div class="form-check mb-2">
      <input class="form-check-input" type="checkbox" id="p3" checked>
      <label class="form-check-label" for="p3">Data dan dokumen yang saya berikan benar; koperasi berhak meminta
        kelengkapan tambahan.</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="p4">
      <label class="form-check-label" for="p4">Saya melampirkan kolateral (wajib untuk pinjaman kendaraan/multiguna).</label>
    </div>
  </div>

  <div class="col-12 d-flex flex-wrap gap-2 mt-3">
    <a class="btn btn-primary" href="pengajuan-status.html"><i class="bi bi-send"></i> Kirim pengajuan</a>
    <button type="button" class="btn btn-outline-secondary">Simpan sebagai draft</button>
    <a class="btn btn-link" href="simulasi-hasil.html">Kembali ke hasil simulasi</a>
  </div>
</form>"""

    dok_tbl = tbl(["Dokumen", "Sifat", "Berkas", "Status", "Aksi"], rows_dok,
                  footer="Kelengkapan saat ini: 5 dari 6 dokumen wajib terunggah. Kolateral BPKB hanya wajib bila "
                         "keperluan pinjaman adalah pembelian kendaraan.")

    kolateral = """<p class="small">Blok ini <strong>hanya muncul</strong> bila keperluan pinjaman adalah pembelian
      kendaraan (produk PJ-MG) atau anggota mencentang kolateral tambahan pada bagian F.</p>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="jns">Jenis kolateral</label>
          <select class="form-select" id="jns">
            <option selected>BPKB mobil</option>
            <option>BPKB motor</option>
            <option>Sertifikat tanah/bangunan</option>
            <option>Deposito / simpanan yang diblokir</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="nilaik">Nilai taksiran kolateral</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="number" class="form-control" id="nilaik" value="150000000" step="1000000">
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="nobpkb">Nomor BPKB / bukti kepemilikan</label>
          <input type="text" class="form-control" id="nobpkb" placeholder="M-1234-AB">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="pemilik">Nama pemilik sesuai dokumen</label>
          <input type="text" class="form-control" id="pemilik" value="Budi Santoso">
        </div>
      </div>"""

    ringkasan = dl([
        ("No. simulasi", "SIM-2026-0142"),
        ("Pokok", money(SKENARIO["P"])),
        ("Tenor", "24 bulan"),
        ("Sifat bayar", "Autodebet"),
        ("Keperluan", "Biaya pendidikan anak"),
        ("Urgensi", badge("Tinggi", "danger")),
    ])

    aturan = """<ul class="list-group list-group-flush small">
        <li class="list-group-item d-flex justify-content-between px-0">Denda keterlambatan
          <span class="fw-semibold">0,50% / bulan</span></li>
        <li class="list-group-item d-flex justify-content-between px-0">Penalti pelunasan dipercepat
          <span class="fw-semibold">1% dari sisa pokok</span></li>
        <li class="list-group-item d-flex justify-content-between px-0">Biaya administrasi
          <span class="fw-semibold">0,5% dari pokok</span></li>
        <li class="list-group-item d-flex justify-content-between px-0">Rasio cicilan maksimal
          <span class="fw-semibold">44% penghasilan</span></li>
      </ul>"""

    langkah = """<ol class="ps-3 mb-0 small">
        <li>Status berubah menjadi <em>Diajukan</em>.</li>
        <li>Bendahara memeriksa ketersediaan kas dan kelengkapan dokumen (Langkah 5).</li>
        <li>Bila lengkap, bendahara merekomendasikan ke ketua (Langkah 6).</li>
        <li>Ketua menyetujui / memberi revisi / menolak (Langkah 8).</li>
      </ol>"""

    tools_status = '<a class="btn btn-sm btn-outline-secondary" href="pengajuan-status.html">Lihat contoh status</a>'

    body = f"""
{stepper(STEP, 2)}
{note("<strong>Langkah 4:</strong> anggota mengajukan peminjaman berdasarkan tabel simulasi yang diberikan. "
      "Isian kunci: keperluan (untuk menilai urgensi) dan sifat pinjaman (autodebet atau bayar per bulan). "
      "Bendahara yang menyiapkan denda dan mekanisme pelunasan dini pada halaman Parameter Pinjaman.")}
<div class="row g-4">
  <div class="col-xl-8">
    {card("Form pengajuan pinjaman", form, subtitle="Langkah 4 &mdash; isian utama anggota")}
    {card("Dokumen persyaratan &amp; unggahan", dok_tbl,
          subtitle="Langkah 5 &mdash; dipakai bendahara untuk memeriksa kelengkapan")}
    {card("Kolateral (bila ada)", kolateral,
          subtitle="Berkas kunci: form kesediaan autodebet; kolateral bila pinjaman untuk kendaraan")}
  </div>
  <div class="col-xl-4">
    {card("Ringkasan pengajuan", ringkasan, footer="Periksa kembali sebelum menekan Kirim pengajuan.")}
    {card("Aturan biaya &amp; penalti produk PJ-RG", aturan, subtitle="Disiapkan bendahara / pengurus koperasi")}
    {card("Setelah dikirim", langkah, tools=tools_status)}
  </div>
</div>"""
    return layout("anggota", "pengajuan-form.html", "Pengajuan Pinjaman", "Langkah 4",
                  body, desc="Form pengajuan berdasarkan simulasi yang diberikan bendahara",
                  crumb=[("Hasil simulasi", "simulasi-hasil.html"), ("Pengajuan pinjaman", None)])


# --------------------------------------------------------------------- 5. status pengajuan
def pengajuan_status():
    kejadian = [
        ("06 Okt 2026 &middot; 09:12", "Pengajuan diterima sistem",
         "Oleh: Budi Santoso (anggota) &middot; dokumen 5 dari 6", "Diajukan"),
        ("06 Okt 2026 &middot; 14:30", "Pemeriksaan kelengkapan dokumen",
         "Bendahara: Siti Rahmawati &middot; kolateral BPKB tidak diperlukan untuk PJ-RG", "Verifikasi Dokumen"),
        ("07 Okt 2026 &middot; 10:05", "Pemeriksaan ketersediaan kas",
         "Kas tersedia Rp 412,8 jt &middot; kebutuhan pencairan Rp 25,0 jt &middot; status cukup", "Verifikasi Dokumen"),
        ("07 Okt 2026 &middot; 15:40", "Rekomendasi bendahara ke ketua",
         "Catatan: riwayat pembayaran baik, bunga efektif 14,03% p.a., tenor sesuai simulasi", "Direkomendasikan"),
        ("08 Okt 2026 &middot; 11:20", "Menunggu keputusan ketua koperasi",
         "Sedang ditinjau bersama urutan prioritas keperluan minggu ini", "Menunggu Persetujuan"),
    ]
    rows_tl = []
    for tgl, judul, ket, status in kejadian:
        isi = "<span class='fw-semibold'>" + judul + "</span><div class='small text-body-secondary'>" + ket + "</div>"
        rows_tl.append([tgl, isi, st(status)])
    timeline = tbl(["Waktu", "Kejadian", "Status"], rows_tl)

    isian = dl([
        ("No. pengajuan", "PGJ-2026-0088"),
        ("Simulasi acuan", "SIM-2026-0142"),
        ("Pokok / tenor", money(SKENARIO["P"]) + " / 24 bulan"),
        ("Produk &amp; metode bunga", "PJ-RG &middot; menurun (efektif) 1,10%/bln"),
        ("Sifat pembayaran", "Autodebet BSI 7100 2299 441"),
        ("Keperluan", "Biaya pendidikan anak (urgensi tinggi)"),
        ("Dokumen", "5 dari 6 wajib &middot; form kesediaan autodebet terverifikasi"),
    ])

    revisi = """<div class="alert alert-warning mb-0">
        <h3 class="h6 fw-bold"><i class="bi bi-arrow-repeat"></i> Revisi diminta ketua &mdash; 08 Okt 2026 15:02</h3>
        <p class="mb-2 small">"Tenor saya minta <strong>36 bulan</strong> agar angsuran bulanan di bawah 30%
        penghasilan, karena ada cicilan kendaraan lain di rumah tangga. Silakan hitung ulang tabel angsuran."</p>
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-sm btn-warning" type="button">Ajukan simulasi ulang (36 bulan)</button>
          <button class="btn btn-sm btn-outline-secondary" type="button">Lihat tabel angsuran 36 bulan</button>
        </div>
      </div>"""

    tolak = """<div class="alert alert-danger mb-0">
        <h3 class="h6 fw-bold"><i class="bi bi-x-octagon"></i> Pengajuan ditolak &mdash; contoh tampilan</h3>
        <p class="mb-2 small"><strong>Alasan ketua:</strong> "Rasio cicilan melebihi 44% penghasilan dan masih ada
        pinjaman aktif lain di koperasi. Silakan ajukan kembali setelah PJM-2025-0114 lunas."</p>
        <button class="btn btn-sm btn-outline-danger" type="button">Ajukan banding / konsultasi bendahara</button>
      </div>"""

    tab_keputusan = """<ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab"
          data-bs-target="#tabRevisi" type="button" role="tab">Revisi dari ketua</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab"
          data-bs-target="#tabTolak" type="button" role="tab">Penolakan</button></li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="tabRevisi" role="tabpanel">""" + revisi + """</div>
        <div class="tab-pane fade" id="tabTolak" role="tabpanel">""" + tolak + """</div>
      </div>"""

    lanjut = """<ol class="ps-3 mb-0 small">
        <li>Ketua menyetujui &rarr; status <em>Disetujui</em>.</li>
        <li>Bendahara menyiapkan dana dan bukti transaksi pencairan (Langkah 9).</li>
        <li>Anggota menerima pencairan dan mulai mencicil (Langkah 10).</li>
      </ol>"""

    kontak = """<ul class="list-group list-group-flush small">
        <li class="list-group-item d-flex justify-content-between px-0">Bendahara
          <span>Siti Rahmawati &middot; 0811-2233-4455</span></li>
        <li class="list-group-item d-flex justify-content-between px-0">Ketua
          <span>Dr. Hendra Wijaya &middot; 0813-9988-7766</span></li>
      </ul>"""

    status_judul = badge("Menunggu Persetujuan", "warning") + \
        ' <span class="ms-2 small text-body-secondary">posisi saat ini: di meja ketua koperasi (Langkah 8)</span>'

    body = f"""
{stepper(STEP, 4)}
<div class="row g-4">
  <div class="col-xl-8">
    {card("Status pengajuan PGJ-2026-0088", status_judul, subtitle="Riwayat pemrosesan",
          tools='<a class="btn btn-sm btn-outline-secondary" href="pengajuan-form.html">Edit pengajuan</a>')}
    {card("Riwayat pemrosesan pengajuan", timeline,
          subtitle="Transparansi alur: anggota dapat melihat siapa memproses dan kapan")}
    {card("Contoh tampilan bila ada keputusan ketua", tab_keputusan,
          subtitle="Revisi ketua juga terlihat oleh bendahara agar tabel angsuran dapat dihitung ulang")}
  </div>
  <div class="col-xl-4">
    {card("Ringkasan isian pengajuan", isian)}
    {card("Langkah selanjutnya", lanjut)}
    {card("Kontak cepat", kontak)}
  </div>
</div>"""
    return layout("anggota", "pengajuan-status.html", "Status Pengajuan", "Langkah 4&ndash;8",
                  body, desc="Pantau proses pengajuan dari diajukan sampai pencairan",
                  crumb=[("Pengajuan pinjaman", "pengajuan-form.html"), ("Status pengajuan", None)])


# --------------------------------------------------------------------- 6. cicilan aktif
def pinjaman_aktif():
    r = SIM["flat"]["rows"]
    rows = []
    for x in r:
        k = x["k"]
        tm = 2026 * 12 + 1 + (k - 1)          # Feb 2026 = cicilan ke-1
        tgl = "10 " + BULAN12[tm % 12] + " " + str(tm // 12)
        if k <= 8:
            status, bukti = badge("Lunas", "success"), "AD-2026-" + str(1000 + k)
            aksi = '<a class="btn btn-sm btn-outline-secondary" href="kartu-piutang.html">Detail</a>'
        elif k == 9:
            status = badge("Jatuh tempo hari ini", "warning")
            bukti = '<span class="text-body-secondary">menunggu file bank</span>'
            aksi = '<button class="btn btn-sm btn-primary" type="button">Bayar manual</button>'
        else:
            status = badge("Belum jatuh tempo", "secondary", outline=True)
            bukti = '<span class="text-body-secondary">&mdash;</span>'
            aksi = '<a class="btn btn-sm btn-outline-secondary" href="#">Rincian</a>'
        rows.append(["<span class='fw-semibold'>ke-" + str(k) + "</span>", tgl, money(x["angs"]),
                     money(x["pokok"]), money(x["bunga"]), "Autodebet", bukti, status, aksi])

    jadwal = tbl(["Cicilan", "Jatuh tempo", "Angsuran", "Pokok", "Bunga", "Metode", "No. bukti", "Status", "Aksi"],
                 rows, footer="Total 24 cicilan &middot; 8 sudah dibayar &middot; sisa 16 cicilan. "
                              "Angka konsisten dengan halaman Kartu Piutang.")

    lunas_dini = """<p class="small">Simulasi pelunasan dipercepat hari ini (10 Okt 2026) atas pinjaman
      PJM-2026-0031:</p>
      <dl class="row g-0 small mb-3">
        <dt class="col-7 fw-normal text-body-secondary py-1 border-bottom">Sisa pokok (15 cicilan)</dt>
        <dd class="col-5 py-1 mb-0 border-bottom text-end">""" + money(16_666_667) + """</dd>
        <dt class="col-7 fw-normal text-body-secondary py-1 border-bottom">Penalti pelunasan dipercepat (1%)</dt>
        <dd class="col-5 py-1 mb-0 border-bottom text-end">""" + money(166_667) + """</dd>
        <dt class="col-7 fw-normal text-body-secondary py-1 border-bottom">Bunga berjalan (1 bulan)</dt>
        <dd class="col-5 py-1 mb-0 border-bottom text-end">""" + money(183_333) + """</dd>
        <dt class="col-7 fw-semibold py-1 border-bottom">Total yang harus dibayar</dt>
        <dd class="col-5 fw-semibold py-1 mb-0 border-bottom text-end">""" + money(17_016_667) + """</dd>
      </dl>
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalLunas">
          <i class="bi bi-check2-circle"></i> Ajukan pelunasan dipercepat</button>
        <button class="btn btn-sm btn-outline-secondary" type="button">Cetak surat permohonan</button>
      </div>"""

    denda = """<ul class="list-group list-group-flush small">
        <li class="list-group-item d-flex justify-content-between px-0">Denda keterlambatan bulan ini
          <span class="fw-semibold text-success">Rp 0</span></li>
        <li class="list-group-item d-flex justify-content-between px-0">Akumulasi denda pinjaman ini
          <span class="fw-semibold">Rp 0</span></li>
        <li class="list-group-item d-flex justify-content-between px-0">Riwayat keterlambatan
          <span>0 dari 8 cicilan</span></li>
        <li class="list-group-item d-flex justify-content-between px-0">Kolektibilitas
          <span class="badge text-bg-success">Lancar</span></li>
      </ul>"""

    cara_bayar = """<ul class="nav nav-pills nav-fill mb-3" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill"
          data-bs-target="#cbA" type="button">Autodebet</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill"
          data-bs-target="#cbB" type="button">Bayar per bulan</button></li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="cbA" role="tabpanel">
          <p class="small mb-2">Koperasi mengirim permintaan autodebet ke bank setiap tanggal 5. File hasil dari bank
          berisi nama nasabah, NIK, dan status autodebet, lalu diunggah bendahara ke sistem untuk memperbarui kartu
          piutang anggota.</p>
          <dl class="row g-0 small mb-0">
            <dt class="col-6 fw-normal text-body-secondary py-1 border-bottom">Rekening</dt>
            <dd class="col-6 py-1 mb-0 border-bottom text-end">BSI 7100 2299 441</dd>
            <dt class="col-6 fw-normal text-body-secondary py-1 border-bottom">Tanggal debet</dt>
            <dd class="col-6 py-1 mb-0 border-bottom text-end">Tanggal 10 setiap bulan</dd>
            <dt class="col-6 fw-normal text-body-secondary py-1 border-bottom">Sumber data kartu piutang</dt>
            <dd class="col-6 py-1 mb-0 border-bottom text-end">File XLSX dari bank</dd>
          </dl>
        </div>
        <div class="tab-pane fade" id="cbB" role="tabpanel">
          <p class="small mb-2">Bayar transfer ke rekening koperasi, lalu unggah bukti transfer untuk diperiksa
          bendahara.</p>
          <input type="file" class="form-control form-control-sm mb-2">
          <div class="form-text mb-2">Untuk cicilan ke-9 yang jatuh tempo hari ini.</div>
          <button class="btn btn-sm btn-primary" type="button">Kirim bukti transfer</button>
        </div>
      </div>"""

    berkas = """<ul class="list-group list-group-flush small">
        <li class="list-group-item d-flex justify-content-between px-0">Perjanjian pinjaman
          <a href="#">Lihat PDF</a></li>
        <li class="list-group-item d-flex justify-content-between px-0">Bukti pencairan
          <a href="#">Lihat PDF</a></li>
        <li class="list-group-item d-flex justify-content-between px-0">Form kesediaan autodebet
          <a href="#">Lihat PDF</a></li>
      </ul>"""

    kpi = kpi_row([
        ("Sisa pokok", money(16_666_667), "15 cicilan tersisa", "danger", "bi-wallet2"),
        ("Angsuran per bulan", money(1_316_667), "pokok + bunga tetap", "primary", "bi-calendar3"),
        ("Progres cicilan", "8 dari 24", "33% selesai &middot; mulai Feb 2026", "info", "bi-activity"),
        ("Status autodebet", "Menunggu konfirmasi", "file bank Okt 2026 sudah diunggah bendahara", "warning", "bi-credit-card-2-front"),
    ])

    modal = """<div class="modal fade" id="modalLunas" tabindex="-1" aria-labelledby="modalLunasLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLunasLabel">Ajukan pelunasan dipercepat</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p class="small">Pinjaman PJM-2026-0031 akan ditutup dengan total pembayaran
          <strong>""" + money(17_016_667) + """</strong> (sisa pokok + penalti 1% + bunga berjalan).</p>
        <div class="mb-3">
          <label class="form-label" for="alasanl">Alasan pelunasan dipercepat</label>
          <textarea class="form-control" id="alasanl" rows="2"
            placeholder="Contoh: menerima dana tunjangan tambahan."></textarea>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="setujul">
          <label class="form-check-label small" for="setujul">Saya memahami penalti 1% dari sisa pokok sesuai
            parameter produk.</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary">Kirim permohonan ke bendahara</button>
      </div>
    </div>
  </div>
</div>"""

    body = f"""
{kpi}
<div class="row g-4">
  <div class="col-xl-8">
    {card("Jadwal cicilan PJM-2026-0031 &mdash; PJ-RG Rp 25.000.000 / 24 bulan / bunga tetap 1,10%", jadwal,
          tools='<button class="btn btn-sm btn-outline-secondary" type="button"><i class="bi bi-printer"></i> Cetak jadwal</button>'
                '<a class="btn btn-sm btn-outline-primary" href="kartu-piutang.html">Kartu piutang</a>')}
    {card("Cara membayar cicilan", cara_bayar,
          subtitle="Dua sifat pinjaman: autodebet atau bayar per bulan (Langkah 10)")}
  </div>
  <div class="col-xl-4">
    {card("Pelunasan dipercepat", lunas_dini, subtitle="Mekanisme pelunasan dini &amp; penalti di-set bendahara")}
    {card("Denda &amp; kolektibilitas", denda)}
    {card("Berkas pinjaman", berkas)}
  </div>
</div>"""
    return layout("anggota", "pinjaman-aktif.html", "Cicilan Aktif", "Langkah 10&ndash;11",
                  body, desc="Status cicilan aktif, jadwal bayar, denda, dan pelunasan dipercepat",
                  crumb=[("Portal Anggota", "dashboard.html"), ("Cicilan aktif", None)],
                  modals=modal)


# --------------------------------------------------------------------- 7. kartu piutang
def kartu_piutang():
    r = SIM["flat"]["rows"]
    rows = [["<span class='fw-semibold'>10 Feb 2026</span>", "PC-2026-0031",
             "Pencairan pinjaman (kas bertambah / piutang muncul)", "&mdash;",
             money(25_000_000), "&mdash;", "&mdash;", money(25_000_000), badge("Dicairkan", "dark")]]
    for k in range(1, 10):
        x = r[k - 1]
        tgl = "10 " + BULAN[k - 1] + " 2026"
        if k <= 8:
            status, bukti = badge("Lunas", "success"), "AD-2026-" + str(1000 + k)
        else:
            status, bukti = badge("Menunggu autodebet", "warning"), "&mdash;"
        rows.append([tgl, bukti, "Angsuran ke-" + str(k) + " (pokok + bunga)", money(x["angs"]),
                     money(x["pokok"]), money(x["bunga"]), "Rp 0", money(x["sk"]), status])
    mutasi = tbl(["Tanggal", "No. bukti", "Uraian", "Angsuran", "Pokok", "Bunga", "Denda", "Sisa pokok", "Status"],
                 rows, footer="Total sudah dibayar: " + money(8 * 1_316_667) + " &middot; sisa pokok "
                              + money(16_666_667) + " &middot; tidak ada denda.")

    ident = dl([
        ("Nama / no. anggota", ANGGOTA["nama"] + " (" + ANGGOTA["no_anggota"] + ")"),
        ("No. pinjaman", "PJM-2026-0031"),
        ("Produk &amp; metode bunga", "PJ-RG &middot; bunga tetap (flat) 1,10%/bulan"),
        ("Pokok pinjaman", money(25_000_000)),
        ("Tenor", "24 bulan &middot; mulai 10 Feb 2026"),
        ("Sifat pembayaran", "Autodebet BSI 7100 2299 441"),
        ("Keperluan", "Biaya pendidikan anak"),
        ("Status", badge("Aktif", "primary")),
    ])
    ident2 = dl([
        ("Angsuran per bulan", money(1_316_667)),
        ("Sudah dibayar", "8 cicilan &middot; " + money(10_533_336)),
        ("Tunggakan", "1 cicilan (jatuh tempo hari ini)"),
        ("Sisa pokok", money(16_666_667)),
        ("Sisa bunga sampai lunas", money(4_400_000)),
        ("Estimasi lunas", "10 Jan 2028"),
    ])

    catatan = """<ul class="list-group list-group-flush small">
        <li class="list-group-item px-0">10 Feb 2026 &mdash; pencairan via transfer BSI, bukti PC-2026-0031.</li>
        <li class="list-group-item px-0">10 Sep 2026 &mdash; autodebet berhasil, tanpa denda.</li>
        <li class="list-group-item px-0">06 Okt 2026 &mdash; file autodebet bank diunggah, cicilan ke-9 menunggu.</li>
      </ul>"""

    sumber = """<ul class="ps-3 mb-0 small">
        <li>File XLSX autodebet dari bank (nama nasabah, NIK, status autodebet).</li>
        <li>Bukti transfer manual yang diverifikasi bendahara.</li>
        <li>Jurnal denda / penalti bila ada tunggakan atau pelunasan dipercepat.</li>
      </ul>"""

    progres = bars([("Pokok terbayar", "33%", 33), ("Bunga terbayar", "58%", 58),
                    ("Sisa pokok dari pokok awal", "67%", 67)])

    header = """<div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h2 class="h6 fw-bold mb-0">Kartu Piutang &mdash; PJM-2026-0031</h2>
      <div class="small text-body-secondary">KSU Mitra Sejahtera &middot; dicetak 10 Okt 2026</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-sm btn-outline-secondary" type="button"><i class="bi bi-printer"></i> Cetak</button>
      <button class="btn btn-sm btn-outline-secondary" type="button"><i class="bi bi-file-earmark-pdf"></i> Unduh PDF</button>
      <a class="btn btn-sm btn-outline-secondary" href="pinjaman-aktif.html">Kembali</a>
    </div>
  </div>"""

    body = f"""
{note("Kartu piutang adalah <strong>satu halaman per pinjaman</strong>. Sumber update: file autodebet XLSX dari bank "
      "yang diunggah bendahara, ditambah pembayaran manual yang diverifikasi kasir. Anggota tidak mengubah data ini.",
      "secondary", "bi-journal-text")}
<div class="card mb-4">{header}
  <div class="card-body">
    <div class="row g-4">
      <div class="col-lg-6">{ident}</div>
      <div class="col-lg-6">{ident2}</div>
    </div>
  </div>
</div>
<div class="row g-4">
  <div class="col-xl-8">
    {card("Mutasi kartu piutang", mutasi, subtitle="Urutan kronologis: pencairan lalu setiap cicilan")}
  </div>
  <div class="col-xl-4">
    {card("Ringkasan saldo", progres, subtitle="Visual progres pelunasan pinjaman")}
    {card("Catatan bendahara pada pinjaman ini", catatan)}
    {card("Sumber pembaruan data", sumber)}
  </div>
</div>"""
    return layout("anggota", "kartu-piutang.html", "Kartu Piutang", "Langkah 11",
                  body, desc="Detail mutasi piutang per pinjaman",
                  crumb=[("Cicilan aktif", "pinjaman-aktif.html"), ("Kartu piutang", None)])


# --------------------------------------------------------------------- 8. riwayat & penutupan
def riwayat():
    aktif = tbl(["No. pinjaman", "Produk &amp; sifat", "Pokok", "Cicilan ke", "Angsuran", "Sisa pokok",
                 "Jatuh tempo", "Status", ""],
                [[PINJAMAN_AKTIF[0]["id"], "PJ-RG &middot; Autodebet", money(25_000_000), "9/24",
                  money(1_316_667), money(16_666_667), "10 Okt 2026", st("Aktif"),
                  '<a href="kartu-piutang.html" class="btn btn-sm btn-outline-primary">Kartu piutang</a>'],
                 [PINJAMAN_AKTIF[1]["id"], "PJ-PD &middot; Bayar bulanan", money(12_000_000), "15/18",
                  money(786_667), money(2_666_662), "10 Nov 2026", st("Aktif"),
                  '<a href="kartu-piutang.html" class="btn btn-sm btn-outline-primary">Kartu piutang</a>']])
    tertutup = tbl(["No. pinjaman", "Produk &amp; sifat", "Pokok", "Tenor", "Total dibayar", "Tanggal lunas",
                    "Sebab penutupan", "Status", ""],
                   [["PJM-2023-0117", "PJ-RG &middot; Autodebet", money(10_000_000), "12 bulan",
                     money(11_200_000), "10 Agu 2024", "Lunas normal (cicilan selesai)", st("Lunas"),
                     '<a href="kartu-piutang.html" class="btn btn-sm btn-outline-secondary">Kartu piutang</a>'],
                    ["PJM-2021-0064", "PJ-DR &middot; Bunga tetap", money(3_000_000), "6 bulan",
                     money(3_170_000), "05 Jan 2022", "Lunas dipercepat (penalti Rp 30.000)", st("Lunas"),
                     '<a href="kartu-piutang.html" class="btn btn-sm btn-outline-secondary">Kartu piutang</a>']],
                   footer="Pinjaman yang sudah ditutup tetap dapat dibuka kartu piutangnya sebagai arsip audit.")

    kpi = kpi_row([
        ("Pinjaman aktif", "2", "total sisa pokok " + money(19_333_329), "primary", "bi-cash-coin"),
        ("Pinjaman ditutup", "2", "1 lunas normal, 1 lunas dipercepat", "success", "bi-check2-circle"),
        ("Akumulasi bunga dibayar", "Rp 7.480.000", "seluruh pinjaman historis", "warning", "bi-percent"),
        ("Denda historis", "Rp 0", "tidak pernah menunggak", "success", "bi-shield-check"),
    ])

    legalitas = """<ul class="list-group list-group-flush small">
        <li class="list-group-item d-flex justify-content-between px-0">Perjanjian pinjaman 2026
          <a href="#">PDF</a></li>
        <li class="list-group-item d-flex justify-content-between px-0">Berita acara penutupan 2024
          <a href="#">PDF</a></li>
        <li class="list-group-item d-flex justify-content-between px-0">Surat keterangan lunas
          <a href="#">PDF</a></li>
      </ul>"""

    unduh = """<div class="d-grid gap-2">
        <button class="btn btn-outline-secondary" type="button">Unduh rekap pinjaman (PDF)</button>
        <button class="btn btn-outline-secondary" type="button">Unduh riwayat cicilan (XLSX)</button>
        <button class="btn btn-outline-secondary" type="button">Cetak surat keterangan lunas</button>
      </div>"""

    tren = bars([("2024", "Rp 11,2 jt", 45), ("2025", "Rp 9,4 jt", 38), ("2026", "Rp 25,0 jt", 100)])

    pesan = ("<strong>Langkah 11:</strong> halaman ini memisahkan hutang yang masih aktif dan hutang yang sudah "
             "ditutup, dengan akses ke kartu piutang setiap pinjaman.")

    body = f"""
{kpi}
{note(pesan)}
<div class="row g-4">
  <div class="col-xl-8">
    {card("Hutang aktif (2 pinjaman)", aktif, subtitle="Masih berjalan &mdash; angsuran berikutnya tanggal 10")}
    {card("Hutang sudah ditutup (2 pinjaman)", tertutup,
          subtitle="Arsip: kartu piutang tetap tersedia untuk audit")}
  </div>
  <div class="col-xl-4">
    {card("Nilai pencairan per tahun", tren, subtitle="Riwayat 3 tahun terakhir (dummy)")}
    {card("Legalitas &amp; persetujuan", legalitas)}
    {card("Unduh laporan", unduh)}
  </div>
</div>"""
    return layout("anggota", "riwayat.html", "Riwayat &amp; Penutupan Pinjaman", "Langkah 11",
                  body, desc="Pemisahan hutang aktif dan hutang yang sudah ditutup",
                  crumb=[("Portal Anggota", "dashboard.html"), ("Riwayat &amp; penutupan", None)])


add("anggota/pengajuan-form.html", "anggota", "Pengajuan Pinjaman", pengajuan_form())
add("anggota/pengajuan-status.html", "anggota", "Status Pengajuan", pengajuan_status())
add("anggota/pinjaman-aktif.html", "anggota", "Cicilan Aktif", pinjaman_aktif())
add("anggota/kartu-piutang.html", "anggota", "Kartu Piutang", kartu_piutang())
add("anggota/riwayat.html", "anggota", "Riwayat Pinjaman", riwayat())
