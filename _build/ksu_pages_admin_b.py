"""Halaman role: ADMIN KOPERASI / BENDAHARA (bagian 2) — pencairan, autodebet, parameter, data anggota."""
from ksu_core import card, tbl, kpi_row, note, stepper, dl, money, pct, badge, st, bars
from ksu_data import (KAS, PRODUK, AUTODEBET, DAFTAR_ANGGOTA, tbl_autodebet, tbl_anggota,
                      PENGAJUAN, produk)
from ksu_shell import add, layout

STEP = ["Permintaan simulasi", "Verifikasi & rekomendasi", "Persetujuan ketua", "Pencairan", "Cicilan & autodebet"]


# --------------------------------------------------------------------- 5. pencairan
def pencairan():
    rows = [
        ["PJM-2026-0034", "Ahmad Fauzi", "AG-0117", money(5_000_000), "05 Okt 2026", "Bayar bulanan",
         "Transfer &mdash; BRI 0021 3344 55", "Bukti belum diunggah", badge("Siap dicairkan", "warning"),
         '<a class="btn btn-sm btn-primary" href="#formcair">Siapkan dana</a>'],
        ["PJM-2026-0035", "Ratna Kusumawati", "AG-0231", money(120_000_000), "05 Okt 2026", "Autodebet",
         "Transfer &mdash; BSI 7100 3344 2", "Kolateral BPKB diterima", badge("Menunggu pencairan bank", "info"),
         '<a class="btn btn-sm btn-outline-primary" href="#formcair">Lihat</a>'],
        ["PJM-2026-0036", "Dewi Lestari", "AG-0264", money(15_000_000), "04 Okt 2026", "Autodebet",
         "Transfer &mdash; BSI 7100 5566 1", "Bukti terunggah", badge("Siap dicairkan", "warning"),
         '<a class="btn btn-sm btn-primary" href="#formcair">Siapkan dana</a>'],
        ["PJM-2026-0033", "Dewi Lestari", "AG-0264", money(9_500_000), "01 Okt 2026", "Autodebet",
         "Transfer &mdash; BSI 7100 5566 1", "PC-2026-0033 &middot; sudah dicairkan", st("Dicairkan"),
         '<a class="btn btn-sm btn-outline-secondary" href="#">Bukti</a>'],
    ]
    tabel_cair = tbl(["No. pinjaman", "Anggota", "No. anggota", "Pokok", "Tanggal disetujui", "Sifat bayar",
                      "Rekening pencairan", "Kelengkapan", "Status", "Aksi"], rows,
                     footer="Pencairan hanya boleh dilakukan setelah ketua menyetujui dan bukti transfer "
                            "pencairan diunggah sebagai bukti transaksi.")

    form = """<form class="row g-3">
  <div class="col-md-6">
    <label class="form-label" for="nopjm">Pinjaman yang akan dicairkan</label>
    <select class="form-select" id="nopjm">
      <option selected>PJM-2026-0034 &mdash; Ahmad Fauzi (AG-0117) &mdash; Rp 5.000.000</option>
      <option>PJM-2026-0036 &mdash; Dewi Lestari (AG-0264) &mdash; Rp 15.000.000</option>
      <option>PJM-2026-0035 &mdash; Ratna Kusumawati (AG-0231) &mdash; Rp 120.000.000</option>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label" for="tglcair">Tanggal pencairan</label>
    <input type="date" class="form-control" id="tglcair" value="2026-10-10">
  </div>
  <div class="col-md-3">
    <label class="form-label" for="nobukti">Nomor bukti transaksi</label>
    <input type="text" class="form-control" id="nobukti" value="PC-2026-0034" readonly>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="sumber">Sumber dana</label>
    <select class="form-select" id="sumber">
      <option selected>Kas koperasi</option>
      <option>Simpanan anggota (revolving)</option>
      <option>Dana pinjaman bank</option>
      <option>Kas unit usaha</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="metode">Metode pencairan</label>
    <select class="form-select" id="metode">
      <option selected>Transfer bank ke rekening anggota</option>
      <option>Tunai di kasir koperasi</option>
      <option>Transfer ke rekening pihak ketiga (vendor/penyedia)</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="rek">Rekening penerima</label>
    <input type="text" class="form-control" id="rek" value="BRI 0021 3344 55 a.n. Ahmad Fauzi">
  </div>
  <div class="col-md-4">
    <label class="form-label" for="adm">Biaya administrasi (0,5% dari pokok)</label>
    <div class="input-group">
      <span class="input-group-text">Rp</span>
      <input type="number" class="form-control" id="adm" value="25000" readonly>
    </div>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="asur">Asuransi / dana risiko</label>
    <div class="input-group">
      <span class="input-group-text">Rp</span>
      <input type="number" class="form-control" id="asur" value="0">
    </div>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="netto">Dana bersih diterima anggota</label>
    <div class="input-group">
      <span class="input-group-text">Rp</span>
      <input type="number" class="form-control" id="netto" value="4975000" readonly>
    </div>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="bukti">Bukti transaksi pencairan (transfer/slip)</label>
    <input type="file" class="form-control" id="bukti">
    <div class="form-text">Wajib diunggah agar kartu piutang dan jurnal kas dapat diverifikasi pengawas.</div>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="perjanjian">Perjanjian pinjaman &amp; form autodebet</label>
    <input type="file" class="form-control" id="perjanjian" multiple>
  </div>
  <div class="col-12">
    <label class="form-label" for="catcair">Catatan pencairan</label>
    <textarea class="form-control" id="catcair" rows="2">Dana ditransfer sesuai permintaan anggota. Angsuran pertama
jatuh pada 10 November 2026 melalui autodebet.</textarea>
  </div>
  <div class="col-12">
    <div class="form-check mb-2">
      <input class="form-check-input" type="checkbox" id="c1" checked>
      <label class="form-check-label" for="c1">Perjanjian pinjaman sudah ditandatangani anggota dan ketua.</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="c2" checked>
      <label class="form-check-label" for="c2">Form kesediaan autodebet sudah diterima bank.</label>
    </div>
  </div>
  <div class="col-12 d-flex flex-wrap gap-2">
    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalCair">
      <i class="bi bi-cash-coin"></i> Cairkan &amp; catat jurnal</button>
    <button class="btn btn-outline-secondary" type="button">Simpan sebagai draft pencairan</button>
    <a class="btn btn-link" href="pengajuan.html">Kembali ke verifikasi</a>
  </div>
</form>"""

    riwayat = tbl(["Tanggal", "No. bukti", "Anggota", "Pokok", "Sumber dana", "Bukti transaksi", "Status"],
                  [["09 Okt 2026", "PC-2026-0033", "Dewi Lestari", money(9_500_000), "Kas koperasi",
                    '<a href="#">slip_transfer.pdf</a>', st("Dicairkan")],
                   ["08 Okt 2026", "PC-2026-0032", "Ahmad Fauzi", money(4_800_000), "Kas koperasi",
                    '<a href="#">slip_transfer.pdf</a>', st("Dicairkan")],
                   ["02 Okt 2026", "PC-2026-0031", "Budi Santoso", money(25_000_000), "Simpanan anggota",
                    '<a href="#">slip_transfer.pdf</a>', st("Dicairkan")]],
                  footer="Total pencairan bulan ini: Rp 45.200.000 (4 pinjaman).")

    modal = """<div class="modal fade" id="modalCair" tabindex="-1" aria-labelledby="modalCairLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCairLabel">Konfirmasi pencairan dana</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p class="small mb-2">Anda akan mencairkan <strong>Rp 5.000.000</strong> kepada Ahmad Fauzi (AG-0117)
          melalui transfer BRI.</p>
        <ul class="small">
          <li>Nomor bukti transaksi: <code>PC-2026-0034</code></li>
          <li>Kartu piutang anggota otomatis dibuat dengan 10 cicilan &times; Rp 545.000.</li>
          <li>Status pengajuan berubah menjadi <em>Dicairkan</em> dan terlihat oleh pengawas.</li>
        </ul>
        <div class="alert alert-warning small py-2 mb-0">
          <i class="bi bi-exclamation-triangle"></i> Pastikan bukti transfer sudah diunggah sebelum menekan Cairkan.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <a class="btn btn-primary" href="pencairan.html">Cairkan sekarang</a>
      </div>
    </div>
  </div>
</div>"""

    body = f"""
{stepper(STEP, 3)}
{note("<strong>Langkah 9:</strong> bendahara melihat informasi pada dashboard, menyiapkan dana untuk pencairan dan "
      "mengunggah bukti transaksi pencairan. Setelah dicairkan, kartu piutang anggota otomatis terbentuk.")}
{kpi_row([
    ("Siap dicairkan", "4 pinjaman", "nilai " + money(149_500_000), "warning", "bi-hourglass-split"),
    ("Kas tersedia", money(KAS["saldo_kas"]), "setelah komitmen lain " + money(KAS["saldo_kas"] - 149_500_000), "success", "bi-safe2"),
    ("Dicairkan bulan ini", "Rp 45.200.000", "4 pinjaman &middot; Oktober 2026", "primary", "bi-cash-coin"),
    ("Bukti transaksi belum lengkap", "1 pinjaman", "wajib dilengkapi sebelum tutup buku", "danger", "bi-file-earmark-x"),
])}
<div class="row g-4">
  <div class="col-12">
    {card("Pinjaman disetujui &mdash; antrian pencairan", tabel_cair,
          subtitle="Hanya pinjaman berstatus Disetujui yang muncul di antrian ini")}
  </div>
  <div class="col-xl-8">
    <div id="formcair"></div>
    {card("Form penyiapan dana &amp; pencairan", form, subtitle="Langkah 9 &mdash; siapkan dana dan bukti transaksi")}
  </div>
  <div class="col-xl-4">
    {card("Ringkasan pencairan terpilih", dl([
        ("Anggota", "Ahmad Fauzi (AG-0117)"),
        ("Pokok disetujui", money(5_000_000)),
        ("Biaya administrasi", money(25_000)),
        ("Dana bersih", money(4_975_000)),
        ("Cicilan pertama", "10 Nov 2026 &middot; " + money(545_000)),
        ("Tenor", "10 bulan &middot; bunga tetap 0,90%/bln"),
    ]))}
    {card("Ceklis sebelum mencairkan", "@CEKLIS@")}
  </div>
  <div class="col-12">
    {card("Riwayat pencairan terakhir", riwayat)}
  </div>
</div>"""

    ceklis = """<ul class="list-group list-group-flush small">
        <li class="list-group-item d-flex justify-content-between px-0">Persetujuan ketua
          <span class="badge text-bg-success">Ada</span></li>
        <li class="list-group-item d-flex justify-content-between px-0">Kelengkapan dokumen
          <span class="badge text-bg-success">6/6</span></li>
        <li class="list-group-item d-flex justify-content-between px-0">Ketersediaan kas
          <span class="badge text-bg-success">Cukup</span></li>
        <li class="list-group-item d-flex justify-content-between px-0">Perjanjian ditandatangani
          <span class="badge text-bg-warning">Menunggu unggah</span></li>
        <li class="list-group-item d-flex justify-content-between px-0">Bukti transfer
          <span class="badge text-bg-warning">Menunggu unggah</span></li>
      </ul>"""
    body = body.replace("@CEKLIS@", ceklis)

    return layout("admin", "pencairan.html", "Pencairan Dana", "Langkah 9",
                  body, desc="Siapkan dana dan bukti transaksi pencairan pinjaman yang disetujui",
                  crumb=[("Back Office Bendahara", "dashboard.html"), ("Pencairan dana", None)],
                  modals=modal)


# --------------------------------------------------------------------- 6. upload autodebet
def autodebet_upload():
    form = """<form class="row g-3">
  <div class="col-md-4">
    <label class="form-label" for="bank">Bank pengirim file</label>
    <select class="form-select" id="bank">
      <option selected>BSI &mdash; Bank Syariah Indonesia</option>
      <option>BRI</option>
      <option>Bank Mandiri</option>
      <option>BNI</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="periode">Periode autodebet</label>
    <input type="month" class="form-control" id="periode" value="2026-10">
  </div>
  <div class="col-md-4">
    <label class="form-label" for="tglfile">Tanggal file diterima dari bank</label>
    <input type="date" class="form-control" id="tglfile" value="2026-10-10">
  </div>
  <div class="col-12">
    <label class="form-label" for="filead">Berkas XLSX hasil autodebet</label>
    <input type="file" class="form-control" id="filead" accept=".xlsx,.xls,.csv">
    <div class="form-text">Kolom wajib: Nama Nasabah, NIK, Status Autodebet. Kolom opsional: No. Pinjaman,
      Nominal, Tanggal Debet, Keterangan.</div>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="tindak">Tindakan bila status gagal</label>
    <select class="form-select" id="tindak">
      <option selected>Masukkan ke daftar tindak lanjut &amp; tandai kartu piutang</option>
      <option>Kenakan denda langsung sesuai parameter</option>
      <option>Ubah metode menjadi bayar per bulan</option>
    </select>
  </div>
  <div class="col-md-6 d-flex align-items-end">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="anotif" checked>
      <label class="form-check-label" for="anotif">Kirim notifikasi ke anggota yang autodebetnya gagal</label>
    </div>
  </div>
  <div class="col-12 d-flex flex-wrap gap-2">
    <button class="btn btn-primary" type="button"><i class="bi bi-upload"></i> Unggah &amp; baca berkas</button>
    <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#modalProses">
      <i class="bi bi-check2-square"></i> Proses &amp; perbarui kartu piutang</button>
    <button class="btn btn-outline-secondary" type="button">Unduh template XLSX</button>
  </div>
</form>"""

    pratinjau = tbl(["Nama nasabah", "NIK", "No. pinjaman", "Nominal", "Status autodebet", "Keterangan"],
                    [[a[0] + '<div class="small text-body-secondary">baris ' + str(i + 2) + "</div>", "<code>" + a[1] + "</code>",
                      a[2], money(a[3]), st(a[4]), a[5]] for i, a in enumerate(AUTODEBET)],
                    footer="Menampilkan 10 baris contoh dari 214 baris file (data dummy).")

    modal = """<div class="modal fade" id="modalProses" tabindex="-1" aria-labelledby="modalProsesLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalProsesLabel">Proses file autodebet dan perbarui kartu piutang</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p class="small">Berkas <code>autodebet_BSI_2026-10.xlsx</code> berisi 214 baris. Hasil pembacaan:</p>
        <div class="row g-3 mb-3">
          <div class="col-sm-4"><div class="border rounded p-2 text-center">
            <div class="fs-4 fw-bold text-success">207</div><div class="small text-body-secondary">berhasil</div></div></div>
          <div class="col-sm-4"><div class="border rounded p-2 text-center">
            <div class="fs-4 fw-bold text-danger">7</div><div class="small text-body-secondary">gagal</div></div></div>
          <div class="col-sm-4"><div class="border rounded p-2 text-center">
            <div class="fs-4 fw-bold">2 dari 214</div><div class="small text-body-secondary">NIK tidak cocok</div></div></div>
        </div>
        <ul class="small mb-0">
          <li>207 kartu piutang anggota akan diperbarui (cicilan Oktober ditandai lunas).</li>
          <li>7 anggota masuk daftar <em>autodebet gagal</em> dengan tindak lanjut otomatis.</li>
          <li>2 NIK tidak ditemukan &mdash; perlu perbaikan data anggota sebelum diproses ulang.</li>
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <a class="btn btn-success" href="autodebet-gagal.html">Proses sekarang</a>
      </div>
    </div>
  </div>
</div>"""

    body = f"""
{stepper(STEP, 4)}
{note("Karena sistem belum terhubung dengan API perbankan, bendahara meminta informasi autodebet ke bank setiap "
      "tanggal 5. Bank mengirim berkas <strong>XLSX</strong> berisi nama nasabah, NIK, dan status autodebet. "
      "Bendahara mengunggah berkas tersebut, lalu kartu piutang anggota <strong>otomatis terupdate</strong>.")}
{kpi_row([
    ("Baris file diterima", "214 baris", "periode Okt 2026 &middot; BSI", "primary", "bi-table"),
    ("Autodebet berhasil", "207 transaksi", "kartu piutang otomatis lunas", "success", "bi-check2-circle"),
    ("Autodebet gagal", "7 transaksi", "masuk daftar tindak lanjut", "danger", "bi-x-circle"),
    ("Total nominal terdebit", money(276_450_000), "seluruh file autodebet periode ini", "info", "bi-cash"),
])}
<div class="row g-4">
  <div class="col-xl-8">
    {card("Unggah berkas autodebet dari bank", form, subtitle="Langkah: tarik file dari bank &rarr; unggah &rarr; proses")}
    {card("Pratinjau hasil pembacaan berkas", pratinjau,
          subtitle="Wajib diperiksa bendahara sebelum menekan Proses")}
  </div>
  <div class="col-xl-4">
    {card("Contoh berkas dari bank", "@CONTOHFILE@")}
    {card("Dampak ke sistem", dl([
        ("Kartu piutang", "207 anggota diperbarui otomatis"),
        ("Anggota gagal", "7 anggota + notifikasi otomatis"),
        ("NIK tidak cocok", "2 baris perlu perbaikan data"),
        ("Piutang macet", "ditandai bila 3 bulan berturut gagal"),
        ("Riwayat autodebet", "disimpan per periode untuk audit pengawas"),
    ]))}
    {card("Kebijakan autodebet gagal", dl([
        ("Percobaan ulang otomatis", "tanggal 15 &amp; 20"),
        ("Notifikasi", "e-mail + WhatsApp ke anggota"),
        ("Denda", "0,50% / bulan setelah 30 hari menunggak"),
        ("Eskalasi", "kunjungan bendahara bila 2 bulan berturut"),
    ]))}
  </div>
</div>"""

    contoh = """<dl class="row g-0 small mb-3">
        <dt class="col-6 fw-normal text-body-secondary py-1 border-bottom">Nama berkas</dt>
        <dd class="col-6 py-1 mb-0 border-bottom text-end">autodebet_BSI_2026-10.xlsx</dd>
        <dt class="col-6 fw-normal text-body-secondary py-1 border-bottom">Diterima</dt>
        <dd class="col-6 py-1 mb-0 border-bottom text-end">10 Okt 2026 08:30</dd>
        <dt class="col-6 fw-normal text-body-secondary py-1 border-bottom">Ukuran</dt>
        <dd class="col-6 py-1 mb-0 border-bottom text-end">18 KB</dd>
      </dl>
      <div class="table-responsive"><table class="table table-sm table-bordered mb-0 small">
        <thead class="table-light"><tr><th>Nama Nasabah</th><th>NIK</th><th>Status Autodebet</th></tr></thead>
        <tbody>
          <tr><td>Budi Santoso</td><td>7371…0004</td><td>Berhasil</td></tr>
          <tr><td>Ahmad Fauzi</td><td>7371…0098</td><td>Gagal</td></tr>
          <tr><td>&hellip;</td><td>&hellip;</td><td>&hellip;</td></tr>
        </tbody>
      </table></div>"""
    body = body.replace("@CONTOHFILE@", contoh)

    return layout("admin", "autodebet-upload.html", "Upload Hasil Autodebet Bank", "Mekanisme autodebet",
                  body, desc="Unggah XLSX dari bank, kartu piutang otomatis terupdate",
                  crumb=[("Back Office Bendahara", "dashboard.html"), ("Upload autodebet", None)],
                  actions='<a class="btn btn-outline-danger" href="autodebet-gagal.html">'
                          'Lihat 7 autodebet gagal</a>', modals=modal)


# --------------------------------------------------------------------- 7. autodebet gagal
def autodebet_gagal():
    GAGAL = [(a[0], a[1], a[2], a[3], a[5]) for a in AUTODEBET if a[4] == "Gagal"] + [
        ("Rina Marlina", "7371 0201 0665 0031", "PJM-2026-0019", 1_320_000, "Rekening ditutup"),
        ("Surya Darma", "7371 0201 0778 0042", "PJM-2025-0355", 2_150_000, "Batas transaksi harian"),
        ("Lilis Setiawati", "7371 0201 0899 0013", "PJM-2026-0009", 980_000, "Bank menolak, perlu verifikasi ulang"),
    ]
    rows = []
    for nama, nik, pjm, nominal, ket in GAGAL:
        rows.append([nama + '<div class="small text-body-secondary">anggota koperasi aktif</div>',
                     "<code>" + nik + "</code>", pjm, money(nominal), ket,
                     badge("Belum ditindak", "warning"),
                     '<button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" '
                     'data-bs-target="#modalAnggota">Detail anggota</button>'])
    tabel_gagal = tbl(["Nama anggota", "NIK", "No. pinjaman", "Nominal gagal", "Alasan dari bank",
                       "Status tindak lanjut", "Aksi"], rows,
                      footer="Total nominal gagal bulan ini: Rp 9.240.000 dari 7 anggota (data dummy).")

    modal = """<div class="modal fade" id="modalAnggota" tabindex="-1" aria-labelledby="modalAnggotaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAnggotaLabel">Detail anggota &mdash; autodebet gagal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="row g-4">
          <div class="col-lg-6">
            <h6 class="fw-bold">Identitas anggota</h6>
            <dl class="row g-0 small mb-0">
              <dt class="col-5 fw-normal text-body-secondary py-1 border-bottom">Nama</dt>
              <dd class="col-7 py-1 mb-0 border-bottom">Ahmad Fauzi</dd>
              <dt class="col-5 fw-normal text-body-secondary py-1 border-bottom">No. anggota</dt>
              <dd class="col-7 py-1 mb-0 border-bottom">AG-0117</dd>
              <dt class="col-5 fw-normal text-body-secondary py-1 border-bottom">NIK</dt>
              <dd class="col-7 py-1 mb-0 border-bottom">7371 0201 0330 0098</dd>
              <dt class="col-5 fw-normal text-body-secondary py-1 border-bottom">Unit kerja</dt>
              <dd class="col-7 py-1 mb-0 border-bottom">Bagian Umum</dd>
              <dt class="col-5 fw-normal text-body-secondary py-1 border-bottom">Telepon</dt>
              <dd class="col-7 py-1 mb-0 border-bottom">0812-9988-1122</dd>
              <dt class="col-5 fw-normal text-body-secondary py-1 border-bottom">Rekening autodebet</dt>
              <dd class="col-7 py-1 mb-0 border-bottom">BRI 0021 3344 55</dd>
            </dl>
          </div>
          <div class="col-lg-6">
            <h6 class="fw-bold">Pinjaman terkait</h6>
            <dl class="row g-0 small mb-0">
              <dt class="col-5 fw-normal text-body-secondary py-1 border-bottom">No. pinjaman</dt>
              <dd class="col-7 py-1 mb-0 border-bottom">PJM-2026-0030</dd>
              <dt class="col-5 fw-normal text-body-secondary py-1 border-bottom">Pokok / tenor</dt>
              <dd class="col-7 py-1 mb-0 border-bottom">Rp 5.000.000 / 10 bulan</dd>
              <dt class="col-5 fw-normal text-body-secondary py-1 border-bottom">Cicilan ke</dt>
              <dd class="col-7 py-1 mb-0 border-bottom">7 dari 10</dd>
              <dt class="col-5 fw-normal text-body-secondary py-1 border-bottom">Tunggakan</dt>
              <dd class="col-7 py-1 mb-0 border-bottom">Rp 1.120.000 (1 cicilan)</dd>
              <dt class="col-5 fw-normal text-body-secondary py-1 border-bottom">Denda berjalan</dt>
              <dd class="col-7 py-1 mb-0 border-bottom">Rp 0 (belum 30 hari)</dd>
              <dt class="col-5 fw-normal text-body-secondary py-1 border-bottom">Kolektibilitas</dt>
              <dd class="col-7 py-1 mb-0 border-bottom"><span class="badge text-bg-warning">Dalam perhatian
                khusus</span></dd>
            </dl>
          </div>
          <div class="col-12">
            <h6 class="fw-bold">Riwayat autodebet 3 periode terakhir</h6>
            <div class="table-responsive"><table class="table table-sm mb-0">
              <thead class="table-light"><tr><th>Periode</th><th>Status</th><th>Keterangan bank</th><th>Tanggal tindak lanjut</th></tr></thead>
              <tbody>
                <tr><td>Okt 2026</td><td><span class="badge text-bg-danger">Gagal</span></td><td>Saldo tidak cukup</td><td>Belum</td></tr>
                <tr><td>Sep 2026</td><td><span class="badge text-bg-success">Berhasil</span></td><td>&mdash;</td><td>&mdash;</td></tr>
                <tr><td>Agu 2026</td><td><span class="badge text-bg-success">Berhasil</span></td><td>&mdash;</td><td>&mdash;</td></tr>
              </tbody>
            </table></div>
          </div>
          <div class="col-12">
            <h6 class="fw-bold">Tindak lanjut bendahara</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="tl">Aksi</label>
                <select class="form-select" id="tl">
                  <option selected>Hubungi anggota &amp; minta bayar manual</option>
                  <option>Jadwalkan ulang autodebet tanggal 20</option>
                  <option>Ubah metode menjadi bayar per bulan</option>
                  <option>Kenakan denda sesuai parameter</option>
                  <option>Kunjungan lapangan (eskalasi)</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="tglt">Tanggal tindak lanjut</label>
                <input type="date" class="form-control" id="tglt" value="2026-10-11">
              </div>
              <div class="col-12">
                <label class="form-label" for="catt">Catatan hasil kontak</label>
                <textarea class="form-control" id="catt" rows="2"></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary">Simpan tindak lanjut</button>
      </div>
    </div>
  </div>
</div>"""

    body = f"""
{stepper(STEP, 4)}
{note("Bendahara dapat melihat daftar nama anggota koperasi yang autodebetnya <strong>gagal</strong> dan melihat "
      "informasi detail dari anggota tersebut, lengkap dengan pinjaman terkait, tunggakan, dan riwayat autodebet "
      "3 periode terakhir.")}
{kpi_row([
    ("Anggota autodebet gagal", "7 anggota", "periode Oktober 2026", "danger", "bi-person-x"),
    ("Nominal gagal tertagih", money(9_240_000), "belum masuk kas koperasi", "warning", "bi-cash-stack"),
    ("Sudah ditindaklanjuti", "2 anggota", "bayar manual terverifikasi", "success", "bi-check2-circle"),
    ("Perlu eskalasi", "1 anggota", "gagal 2 bulan berturut-turut", "danger", "bi-exclamation-triangle"),
])}
<div class="row g-4">
  <div class="col-xl-8">
    {card("Daftar autodebet gagal", tabel_gagal,
          tools='<div class="d-flex gap-2"><select class="form-select form-select-sm"><option>Semua periode</option>'
                '<option>Okt 2026</option><option>Sep 2026</option></select>'
                '<button class="btn btn-sm btn-outline-secondary" type="button">Ekspor XLSX</button></div>',
          subtitle="Klik Detail anggota untuk melihat informasi lengkap dan mencatat tindak lanjut")}
  </div>
  <div class="col-xl-4">
    {card("Alasan gagal dari bank", bars([("Saldo tidak cukup", "3 anggota", 43), ("Rekening beku/ditutup", "2 anggota", 29),
                                          ("Kartu kedaluwarsa", "1 anggota", 14), ("Lain-lain", "1 anggota", 14)]))}
    {card("Tindak lanjut yang tercatat", "@TINDAK@")}
  </div>
</div>"""

    tindak = """<ul class="list-group list-group-flush small">
        <li class="list-group-item px-0">10 Okt 2026 &mdash; Ahmad Fauzi: dihubungi, akan bayar manual tanggal 12.</li>
        <li class="list-group-item px-0">10 Okt 2026 &mdash; Joko Prasetyo: rekening beku, mengurus ke bank.</li>
        <li class="list-group-item px-0">09 Okt 2026 &mdash; Rina Marlina: metode diubah ke bayar per bulan.</li>
      </ul>"""
    body = body.replace("@TINDAK@", tindak)

    return layout("admin", "autodebet-gagal.html", "Autodebet Gagal", "Mekanisme autodebet",
                  body, desc="Daftar anggota dengan autodebet gagal dan tindak lanjutnya",
                  crumb=[("Back Office Bendahara", "dashboard.html"), ("Autodebet gagal", None)],
                  actions='<a class="btn btn-outline-primary" href="autodebet-upload.html">Upload berkas bank</a>',
                  modals=modal)


# --------------------------------------------------------------------- 8. parameter pinjaman
def parameter():
    rows_produk = []
    for p in PRODUK:
        rows_produk.append([p["kode"], p["nama"], pct(p["bunga"]) + "/bln", p["metode"],
                            p["tenor"], money(p["plafon"]), pct(p["denda"]), pct(p["penalti"], 1),
                            p["kolateral"], badge("Aktif", "success"),
                            '<button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" '
                            'data-bs-target="#modalProduk">Ubah</button>'])
    tabel_produk = tbl(["Kode", "Jenis pinjaman", "Bunga", "Metode", "Tenor", "Plafon maks.", "Denda / bulan",
                        "Penalti lunas dini", "Kolateral", "Status", "Aksi"], rows_produk,
                       footer="Perubahan parameter berlaku untuk pinjaman baru; pinjaman berjalan tetap memakai "
                              "ketentuan saat akad.")

    form_global = """<form class="row g-3">
  <div class="col-md-4">
    <label class="form-label" for="grate">Bunga efektif acuan (% / tahun)</label>
    <input type="number" class="form-control" id="grate" value="12.00" step="0.25">
    <div class="form-text">Setara 1,10% per bulan (metode menurun).</div>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="gadm">Biaya administrasi (% dari pokok)</label>
    <input type="number" class="form-control" id="gadm" value="0.50" step="0.05">
  </div>
  <div class="col-md-4">
    <label class="form-label" for="grasio">Rasio cicilan maksimal (% penghasilan)</label>
    <input type="number" class="form-control" id="grasio" value="44" step="1">
  </div>
  <div class="col-md-4">
    <label class="form-label" for="gtgl">Tanggal jatuh tempo cicilan</label>
    <input type="number" class="form-control" id="gtgl" value="10" min="1" max="28">
  </div>
  <div class="col-md-4">
    <label class="form-label" for="gkas">Kas minimum koperasi</label>
    <div class="input-group">
      <span class="input-group-text">Rp</span>
      <input type="number" class="form-control" id="gkas" value="150000000" step="1000000">
    </div>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="gplafon">Sisa plafon per anggota</label>
    <div class="input-group">
      <span class="input-group-text">Rp</span>
      <input type="number" class="form-control" id="gplafon" value="200000000" step="5000000">
    </div>
  </div>

  <div class="col-12 mt-3"><h3 class="h6 fw-bold mb-0">Denda keterlambatan</h3><hr class="mt-2"></div>
  <div class="col-md-4">
    <label class="form-label" for="gdenda">Dasar perhitungan denda</label>
    <select class="form-select" id="gdenda">
      <option selected>Persentase dari nilai tunggakan per bulan</option>
      <option>Nominal tetap per keterlambatan</option>
      <option>Persentase harian dari tunggakan</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="gdendan">Besaran denda</label>
    <div class="input-group">
      <span class="input-group-text">%</span>
      <input type="number" class="form-control" id="gdendan" value="0.50" step="0.05">
    </div>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="ggrace">Masa tenggang sebelum denda (hari)</label>
    <input type="number" class="form-control" id="ggrace" value="30" step="1">
  </div>

  <div class="col-12 mt-3"><h3 class="h6 fw-bold mb-0">Mekanisme pelunasan dipercepat (penalti per jenis pinjaman)</h3>
    <hr class="mt-2"></div>
  <div class="col-12">
    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
      <thead class="table-light"><tr><th>Jenis pinjaman</th><th>Dasar penalti</th><th>Besaran</th>
        <th>Bunga berjalan yang tetap ditagih</th></tr></thead>
      <tbody>
        <tr><td>PJ-RG &middot; Reguler</td><td>Sisa pokok</td><td>1,0%</td><td>1 bulan</td></tr>
        <tr><td>PJ-MG &middot; Multiguna</td><td>Sisa pokok</td><td>2,0%</td><td>1 bulan + biaya penutupan BPKB</td></tr>
        <tr><td>PJ-PD &middot; Pendidikan</td><td>Sisa pokok</td><td>0,5%</td><td>1 bulan</td></tr>
        <tr><td>PJ-DR &middot; Darurat</td><td>Tidak ada penalti</td><td>0%</td><td>1 bulan</td></tr>
      </tbody>
    </table></div>
  </div>

  <div class="col-12 mt-3"><h3 class="h6 fw-bold mb-0">Kebijakan autodebet</h3><hr class="mt-2"></div>
  <div class="col-md-4">
    <label class="form-label" for="aretry">Percobaan ulang otomatis</label>
    <select class="form-select" id="aretry">
      <option selected>2 kali (tanggal 15 dan 20)</option>
      <option>1 kali (tanggal 15)</option>
      <option>Tidak ada, langsung tindak lanjut manual</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="anotif">Kanal notifikasi autodebet gagal</label>
    <select class="form-select" id="anotif">
      <option selected>E-mail + WhatsApp</option>
      <option>E-mail saja</option>
      <option>WhatsApp saja</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="aeskalasi">Eskalasi tindak lanjut</label>
    <select class="form-select" id="aeskalasi">
      <option selected>Kunjungan bendahara bila gagal 2 bulan berturut</option>
      <option>Pemberitahuan ketua koperasi</option>
      <option>Penghentian fasilitas pinjaman baru</option>
    </select>
  </div>

  <div class="col-12 d-flex flex-wrap gap-2 mt-3">
    <button class="btn btn-primary" type="button"><i class="bi bi-save"></i> Simpan parameter</button>
    <button class="btn btn-outline-secondary" type="button">Kembalikan ke pengaturan terakhir</button>
    <a class="btn btn-link" href="pengajuan.html">Kembali ke verifikasi pengajuan</a>
  </div>
</form>"""

    modal = """<div class="modal fade" id="modalProduk" tabindex="-1" aria-labelledby="modalProdukLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalProdukLabel">Ubah parameter produk &mdash; PJ-RG Reguler</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label" for="mkode">Kode produk</label>
            <input type="text" class="form-control" id="mkode" value="PJ-RG" readonly>
          </div>
          <div class="col-md-8">
            <label class="form-label" for="mnama">Nama produk</label>
            <input type="text" class="form-control" id="mnama" value="Pinjaman Reguler">
          </div>
          <div class="col-md-4">
            <label class="form-label" for="mbunga">Bunga per bulan (%)</label>
            <input type="number" class="form-control" id="mbunga" value="1.10" step="0.05">
          </div>
          <div class="col-md-4">
            <label class="form-label" for="mmetode">Metode bunga</label>
            <select class="form-select" id="mmetode">
              <option>Bunga tetap (flat)</option>
              <option selected>Saldo menurun (efektif)</option>
              <option>Anuitas</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="mplafon">Plafon maksimum</label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="number" class="form-control" id="mplafon" value="50000000" step="5000000">
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="mtenormin">Tenor minimum (bulan)</label>
            <input type="number" class="form-control" id="mtenormin" value="12">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="mtenormax">Tenor maksimum (bulan)</label>
            <input type="number" class="form-control" id="mtenormax" value="36">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="mdenda">Denda (% / bulan)</label>
            <input type="number" class="form-control" id="mdenda" value="0.50" step="0.05">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="mpenalti">Penalti lunas dini (% sisa pokok)</label>
            <input type="number" class="form-control" id="mpenalti" value="1.00" step="0.25">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="mkol">Ketentuan kolateral</label>
            <select class="form-select" id="mkol">
              <option selected>Tanpa kolateral (autodebet / potong gaji)</option>
              <option>BPKB kendaraan</option>
              <option>Sertifikat tanah/bangunan</option>
              <option>Deposito diblokir</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="msifat">Sifat pembayaran yang diizinkan</label>
            <select class="form-select" id="msifat">
              <option selected>Autodebet atau bayar per bulan</option>
              <option>Autodebet saja</option>
              <option>Bayar per bulan saja</option>
            </select>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="maktif" checked>
              <label class="form-check-label" for="maktif">Produk aktif dan dapat dipilih anggota</label>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary">Simpan perubahan produk</button>
      </div>
    </div>
  </div>
</div>"""

    audit = tbl(["Waktu", "Parameter", "Nilai lama", "Nilai baru", "Diubah oleh", "Dasar"],
                [["01 Okt 2026 09:10", "Bunga efektif PJ-RG", "1,05%/bln", "1,10%/bln", "S. Rahmawati",
                  "SK 07/SK/X/2026"],
                 ["01 Okt 2026 09:12", "Penalti lunas dini PJ-MG", "1,5% sisa pokok", "2,0% sisa pokok", "S. Rahmawati",
                  "Rapat pengurus 28 Sep 2026"],
                 ["15 Sep 2026 14:20", "Denda keterlambatan (semua produk)", "0,40%/bln", "0,50%/bln", "S. Rahmawati",
                  "Rapat pengurus 12 Sep 2026"],
                 ["01 Agu 2026 10:05", "Batas rasio cicilan", "50%", "44%", "H. Wijaya", "Kebijakan ketua"]])

    body = f"""
{note("<strong>Langkah 4 (bagian bendahara):</strong> bendahara menyiapkan denda dan mekanisme pelunasan dini "
      "(penalti berdasarkan jenis pinjaman), serta parameter bunga efektif yang dipakai saat memproses simulasi. "
      "Semua perubahan tersimpan dalam jejak audit yang dapat dilihat pengawas.")}
<div class="row g-4">
  <div class="col-xl-12">
    {card("Parameter produk pinjaman", tabel_produk,
          tools='<button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalProduk">'
                '<i class="bi bi-plus-lg"></i> Tambah produk</button>',
          subtitle="Termasuk denda keterlambatan dan penalti pelunasan dipercepat per jenis pinjaman")}
  </div>
  <div class="col-xl-8">
    {card("Parameter umum &amp; kebijakan pinjaman", form_global, subtitle="Berlaku untuk seluruh produk")}
  </div>
  <div class="col-xl-4">
    {card("Ringkasan parameter aktif", dl([
        ("Bunga efektif acuan", "12,00% p.a. (1,10%/bln)"),
        ("Jatuh tempo cicilan", "tanggal 10"),
        ("Denda standar", "0,50% / bulan"),
        ("Masa tenggang denda", "30 hari"),
        ("Penalti lunas dini", "0,5% &ndash; 2,0% sesuai jenis"),
        ("Rasio cicilan maksimal", "44% penghasilan"),
        ("Kas minimum", money(150_000_000)),
    ]))}
    {card("Contoh perhitungan penalti pelunasan dini", dl([
        ("PJ-RG &middot; sisa pokok " + money(16_666_667), money(166_667) + " (1%)"),
        ("PJ-MG &middot; sisa pokok " + money(100_000_000), money(2_000_000) + " (2%)"),
        ("PJ-PD &middot; sisa pokok " + money(5_000_000), money(25_000) + " (0,5%)"),
        ("PJ-DR &middot; sisa pokok " + money(2_000_000), "tanpa penalti"),
    ]), footer="Dipakai sistem saat anggota mengajukan pelunasan dipercepat.")}
  </div>
  <div class="col-xl-12">
    {card("Riwayat perubahan parameter (audit pengawas)", audit,
          subtitle="Pengawas koperasi dapat menelusuri siapa mengubah parameter dan atas dasar apa")}
  </div>
</div>"""
    return layout("admin", "parameter.html", "Parameter Pinjaman", "Langkah 4 &amp; kebijakan",
                  body, desc="Setup jenis pinjaman, denda, penalti pelunasan dini, dan autodebet",
                  crumb=[("Back Office Bendahara", "dashboard.html"), ("Parameter pinjaman", None)],
                  modals=modal)


# --------------------------------------------------------------------- 9. data anggota
def anggota():
    tabel = tbl(["No. anggota", "Nama", "Unit kerja", "Simpanan", "Pinjaman aktif", "Status", "Aksi"],
                tbl_anggota(),
                footer="Data dummy 8 anggota dari 386 anggota koperasi. Kolom pinjaman dipakai untuk menilai "
                       "kemampuan bayar saat verifikasi pengajuan.")

    detail = dl([
        ("Nama", "Budi Santoso, S.Kom., M.T."),
        ("No. anggota / NIK", "AG-0142 &middot; 7371 0201 0288 0004"),
        ("Unit kerja", "Fakultas Teknik &mdash; Prodi Informatika"),
        ("Total simpanan", money(15_000_000)),
        ("Pinjaman aktif", "2 pinjaman &middot; sisa " + money(19_333_329)),
        ("Rekening autodebet", "BSI 7100 2299 441"),
        ("Status autodebet Okt 2026", badge("Berhasil", "success")),
        ("Kolektibilitas", badge("Lancar", "success")),
    ])

    pinjaman_anggota = tbl(["No. pinjaman", "Produk", "Pokok", "Cicilan", "Sisa pokok", "Status"],
                           [["PJM-2026-0031", "PJ-RG", money(25_000_000), "9/24", money(16_666_667), st("Aktif")],
                            ["PJM-2025-0114", "PJ-PD", money(12_000_000), "15/18", money(2_666_662), st("Aktif")],
                            ["PJM-2023-0117", "PJ-RG", money(10_000_000), "12/12", money(0), st("Lunas")]])

    body = f"""
{kpi_row([
    ("Jumlah anggota", "386 orang", "aktif sebagai anggota koperasi", "primary", "bi-people"),
    ("Anggota dengan pinjaman", "214 orang", "piutang berjalan " + money(KAS["piutang_berjalan"]), "info", "bi-cash-coin"),
    ("Autodebet siap", "207 anggota", "rekening terdaftar di bank", "success", "bi-credit-card-2-front"),
    ("Anggota bermasalah", "7 anggota", "autodebet gagal bulan ini", "danger", "bi-person-x"),
])}
<div class="row g-4">
  <div class="col-xl-8">
    {card("Daftar anggota koperasi", tabel,
          tools='<div class="input-group input-group-sm"><input type="search" class="form-control" '
                'placeholder="Cari nama / NIK / no. anggota"><button class="btn btn-outline-secondary" '
                'type="button">Cari</button></div>')}
    {card("Riwayat pinjaman anggota terpilih", pinjaman_anggota,
          subtitle="Tampil setelah bendahara memilih satu anggota pada daftar di atas")}
  </div>
  <div class="col-xl-4">
    {card("Profil anggota terpilih", detail,
          tools='<a class="btn btn-sm btn-outline-secondary" href="#">Cetak profil</a>')}
    {card("Kegunaan data ini", "@GUNA@")}
  </div>
</div>"""

    guna = """<ul class="ps-3 mb-0 small">
        <li>Menilai kemampuan bayar (rasio cicilan) saat verifikasi pengajuan.</li>
        <li>Mencocokkan NIK dengan berkas autodebet dari bank.</li>
        <li>Menelusuri anggota bermasalah dan riwayat pinjamannya.</li>
        <li>Menjadi lampiran lembar rekomendasi ke ketua koperasi.</li>
      </ul>"""
    body = body.replace("@GUNA@", guna)

    return layout("admin", "anggota.html", "Data Anggota Koperasi", "Referensi verifikasi",
                  body, desc="Data anggota, simpanan, pinjaman, dan status autodebet",
                  crumb=[("Back Office Bendahara", "dashboard.html"), ("Data anggota", None)])


add("admin/pencairan.html", "admin", "Pencairan Dana", pencairan())
add("admin/autodebet-upload.html", "admin", "Upload Autodebet", autodebet_upload())
add("admin/autodebet-gagal.html", "admin", "Autodebet Gagal", autodebet_gagal())
add("admin/parameter.html", "admin", "Parameter Pinjaman", parameter())
add("admin/anggota.html", "admin", "Data Anggota", anggota())
