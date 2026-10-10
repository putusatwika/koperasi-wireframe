"""Halaman role: ANGGOTA (bagian 1) — dashboard, form simulasi, hasil simulasi.

Catatan implementasi generator: seluruh potongan HTML dibangun ke variabel
lokal lebih dulu, lalu digabung. Ini menghindari f-string bersarang yang tidak
didukung Python < 3.12.
"""
from ksu_core import card, tbl, kpi_row, note, stepper, dl, money, pct, pctf, badge, st, bars
from ksu_data import (ANGGOTA, PRODUK, SIM, LABEL_METODE, SKENARIO, PENGAJUAN,
                      PINJAMAN_AKTIF, tabel_jadwal, tabel_compare, tbl_pinjaman)
from ksu_shell import add, layout

STEP = ["Simulasi", "Pengajuan", "Verifikasi Bendahara", "Persetujuan Ketua", "Pencairan"]


def dashboard():
    p9 = PINJAMAN_AKTIF[0]
    a = ANGGOTA
    kpi = kpi_row([
        ("Pinjaman aktif", "2 pinjaman", "Reguler &amp; Pendidikan", "primary", "bi-cash-coin"),
        ("Total sisa hutang", money(p9["sisa_pokok"] + PINJAMAN_AKTIF[1]["sisa_pokok"]),
         "pokok saja, belum termasuk bunga berjalan", "danger", "bi-wallet2"),
        ("Angsuran bulan ini", money(p9["angsuran"]),
         "jatuh tempo " + p9["jatuh"] + " &middot; autodebet BSI", "warning", "bi-calendar-check"),
        ("Status pengajuan baru", "Menunggu ketua", "PGJ-2026-0088 &middot; Rp 25,0 jt &middot; 24 bln", "info", "bi-hourglass-split"),
    ])

    _p = PENGAJUAN[0]
    baris_pengajuan = [[
        '<span class="fw-semibold">' + _p["id"] + "</span>", _p["produk"], money(_p["pokok"]),
        str(_p["tenor"]) + " bulan", _p["keperluan"], badge(_p["urgensi"], "danger"), st(_p["status"]),
        '<a href="pengajuan-status.html" class="btn btn-sm btn-primary">Pantau status</a>',
    ]]
    pengajuan_tbl = tbl(["No. Pengajuan", "Produk", "Pokok", "Tenor", "Keperluan", "Urgensi", "Status", ""],
                        baris_pengajuan)

    simpanan = dl([
        ("Simpanan pokok", money(a["simpanan_pokok"])),
        ("Simpanan wajib", money(a["simpanan_wajib"])),
        ("Simpanan sukarela", money(a["simpanan_sukarela"])),
        ("Total simpanan", "<span class='fw-semibold'>" + money(a["simpanan_pokok"] + a["simpanan_wajib"] + a["simpanan_sukarela"]) + "</span>"),
    ])

    kolektibilitas = bars([
        ("Kelancaran bayar", "8/9 tepat waktu", 89),
        ("Rasio cicilan / penghasilan", "28%", 28),
        ("Pemakaian plafon produk", "50% dari Rp 50,0 jt", 50),
    ])

    perlu = """<ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-start gap-2 px-0">
          <div><i class="bi bi-credit-card-2-front text-warning"></i> Autodebet cicilan ke-9
            <div class="small text-body-secondary">Dijadwalkan didebet 10 Okt 2026 dari BSI 7100-2299-441</div></div>
          <span class="badge text-bg-warning">Menunggu Konfirmasi</span></li>
        <li class="list-group-item d-flex justify-content-between align-items-start gap-2 px-0">
          <div><i class="bi bi-file-earmark-check text-success"></i> Form kesediaan autodebet
            <div class="small text-body-secondary">Terunggah 06 Okt 2026</div></div>
          <span class="badge text-bg-success">Diterima</span></li>
        <li class="list-group-item d-flex justify-content-between align-items-start gap-2 px-0">
          <div><i class="bi bi-pencil-square text-primary"></i> Ringkasan pengajuan
            <div class="small text-body-secondary">Sudah direkomendasikan bendahara</div></div>
          <a class="btn btn-sm btn-outline-primary" href="pengajuan-status.html">Buka</a></li>
      </ul>"""

    riwayat_kpi = kpi_row([
        ("Total pinjaman (historis)", "4 pinjaman", "2019 &ndash; 2026", "secondary", "bi-clock-history"),
        ("Pinjaman sudah ditutup", "2 pinjaman", "termasuk PJM-2023-0117 &middot; lunas Agu 2024", "success", "bi-check2-circle"),
        ("Total bunga dibayar", "Rp 7.480.000", "akumulasi seluruh pinjaman", "warning", "bi-percent"),
        ("Rasio denda", "0 kali", "tidak ada keterlambatan", "success", "bi-shield-check"),
    ])

    body = f"""
{kpi}
<div class="row g-4">
  <div class="col-xl-8">
    {card("Cicilan &amp; pinjaman aktif", tbl_pinjaman(),
          tools='<a href="pinjaman-aktif.html" class="btn btn-sm btn-outline-primary">Lihat jadwal cicilan</a>'
                '<a href="riwayat.html" class="btn btn-sm btn-outline-secondary">Riwayat &amp; penutupan</a>',
          subtitle="Data dummy: 2 pinjaman berjalan, 1 pinjaman sudah lunas")}
    {card("Pengajuan terakhir", pengajuan_tbl,
          subtitle="Bendahara sudah merekomendasikan &middot; menunggu keputusan ketua koperasi")}
    <h2 class="h6 fw-bold mb-3">Aktivitas hutang (historis)</h2>
    {riwayat_kpi}
  </div>
  <div class="col-xl-4">
    {card("Simpanan saya", simpanan, tools='<a class="btn btn-sm btn-outline-secondary" href="#">Mutasi simpanan</a>')}
    {card("Indikator penilaian kredit saya", kolektibilitas,
          subtitle="Bahan pertimbangan bendahara &amp; ketua saat menilai pengajuan")}
    {card("Perlu tindakan", perlu)}
  </div>
</div>"""
    return layout("anggota", "dashboard.html", "Dashboard Anggota", "Langkah 1 &amp; 11",
                  body, desc="Ringkasan pinjaman, cicilan aktif, dan status pengajuan",
                  crumb=[("Portal Anggota", "dashboard.html"), ("Dashboard", None)],
                  actions='<a class="btn btn-primary" href="simulasi-form.html">'
                          '<i class="bi bi-calculator"></i> Buat simulasi pinjaman</a>')


def simulasi_form():
    opts = []
    for p in PRODUK:
        sel = " selected" if p["kode"] == SKENARIO["kode"] else ""
        opts.append('<option value="' + p["kode"] + '"' + sel + ">" + p["kode"] + " &mdash; " + p["nama"] +
                    " (" + pct(p["bunga"]) + "/bln, " + p["metode"] + ")</option>")
    opsi_produk = "".join(opts)

    tenor_opts = "".join('<option' + (" selected" if n == SKENARIO["n"] else "") + ">" + str(n) + " bulan</option>"
                         for n in (12, 18, 24, 36))

    form = """<form class="row g-3">
  <div class="col-md-6">
    <label class="form-label" for="produk">Jenis pinjaman</label>
    <select class="form-select" id="produk">""" + opsi_produk + """</select>
    <div class="form-text">Menentukan plafon, metode bunga, dan aturan kolateral.</div>
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
    <label class="form-label" for="pokok">Jumlah pinjaman (pokok)</label>
    <div class="input-group">
      <span class="input-group-text">Rp</span>
      <input type="number" class="form-control" id="pokok" value="25000000" step="1000000" min="1000000" max="50000000">
    </div>
    <div class="form-text">Plafon produk terpilih: Rp 50.000.000 &middot; sisa plafon Anda: Rp 25.000.000</div>
  </div>
  <div class="col-md-3">
    <label class="form-label" for="tenor">Lama tenor</label>
    <select class="form-select" id="tenor">""" + tenor_opts + """</select>
  </div>
  <div class="col-md-3">
    <label class="form-label" for="mulai">Rencana mulai cicilan</label>
    <input type="month" class="form-control" id="mulai" value="2026-11">
  </div>
  <div class="col-12">
    <fieldset>
      <legend class="form-label float-none fs-6 mb-2">Metode perhitungan bunga yang ingin dilihat</legend>
      <div class="d-flex flex-wrap gap-3">
        <div class="form-check">
          <input class="form-check-input" type="radio" name="metode" id="m1" value="tetap">
          <label class="form-check-label" for="m1">Bunga tetap (flat)</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="metode" id="m2" value="menurun" checked>
          <label class="form-check-label" for="m2">Bunga menurun (efektif / sliding)</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="metode" id="m3" value="anuitas">
          <label class="form-check-label" for="m3">Bunga anuitas (angsuran tetap)</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="banding" id="m4" value="1" checked>
          <label class="form-check-label" for="m4">Tampilkan perbandingan ketiga metode</label>
        </div>
      </div>
    </fieldset>
  </div>
  <div class="col-12">
    <label class="form-label" for="catatan">Catatan tambahan untuk bendahara
      <span class="text-body-secondary">(opsional)</span></label>
    <textarea class="form-control" id="catatan" rows="3"
      placeholder="Contoh: cicilan saya inginkan mulai Januari 2027 agar tidak berbarengan dengan biaya semester."></textarea>
  </div>
  <div class="col-12 d-flex flex-wrap gap-2">
    <a class="btn btn-primary" href="simulasi-hasil.html"><i class="bi bi-calculator"></i> Hitung &amp; kirim ke bendahara</a>
    <button type="reset" class="btn btn-outline-secondary">Ulangi isian</button>
    <a class="btn btn-link" href="dashboard.html">Batal</a>
  </div>
</form>"""

    tabel_produk = tbl(
        ["Produk", "Bunga / bulan", "Metode bunga", "Plafon maks.", "Tenor"],
        [[p["kode"] + " &middot; " + p["nama"], pct(p["bunga"]) + "/bln", p["metode"],
          money(p["plafon"]), p["tenor"]] for p in PRODUK],
        footer="Bunga efektif acuan koperasi per 01 Okt 2026: 12,00% p.a. (SK Pengurus No. 07/SK/X/2026). "
               "Nilai denda &amp; penalti pelunasan dini di-set pada halaman Parameter Pinjaman.")

    ringkasan = dl([
        ("Pemohon", ANGGOTA["nama"] + '<div class="small text-body-secondary">' + ANGGOTA["no_anggota"] +
         " &middot; " + ANGGOTA["unit"] + "</div>"),
        ("Produk", "PJ-RG &middot; Pinjaman Reguler"),
        ("Pokok", money(SKENARIO["P"])),
        ("Tenor", str(SKENARIO["n"]) + " bulan"),
        ("Metode bunga", "Menurun (efektif)"),
        ("Keperluan", SKENARIO["keperluan"]),
    ])

    langkah = """<ol class="ps-3 mb-0 small">
        <li>Permintaan masuk ke <strong>antrian simulasi</strong> bendahara (status <em>Menunggu Simulasi</em>).</li>
        <li>Bendahara menyusun tabel angsuran sesuai jenis bunga, tenor, pembayaran bulanan, dan bunga efektif yang berlaku.</li>
        <li>Tabel hasil dikirim kembali ke portal anggota (status <em>Simulasi Selesai</em>).</li>
        <li>Anggota memutuskan mengajukan pinjaman berdasarkan tabel tersebut.</li>
      </ol>"""

    body = f"""
{stepper(STEP, 1)}
{note("<strong>Alur koperasi:</strong> anggota meminta simulasi &rarr; admin koperasi memproses tabel simulasi "
      "berdasarkan jenis bunga (tetap/menurun/anuitas), lama tenor, pembayaran bulanan, dan bunga efektif saat ini "
      "&rarr; tabel diserahkan kembali ke anggota. Form ini adalah titik masuk <em>Langkah 1</em>.")}
<div class="row g-4">
  <div class="col-lg-8">
    {card("Form permintaan simulasi pinjaman", form,
          subtitle="Semua angka bersifat dummy untuk keperluan diskusi desain")}
    {card("Parameter bunga yang berlaku saat ini", tabel_produk,
          subtitle="Sumber: parameter yang di-set bendahara pada halaman Parameter Pinjaman")}
  </div>
  <div class="col-lg-4">
    {card("Ringkasan permintaan", ringkasan, subtitle="Belum final &mdash; hasilnya diproses bendahara")}
    {card("Yang terjadi setelah dikirim", langkah,
          tools='<a class="btn btn-sm btn-outline-secondary" href="../admin/simulasi-masuk.html">'
                'Pratinjau layar bendahara</a>')}
  </div>
</div>"""
    return layout("anggota", "simulasi-form.html", "Permintaan Simulasi Pinjaman", "Langkah 1",
                  body, desc="Anggota mengajukan simulasi dengan parameter pilihannya",
                  crumb=[("Portal Anggota", "dashboard.html"), ("Simulasi Pinjaman", None)])


def simulasi_hasil():
    s = SIM["menurun"]
    flat = SIM["flat"]
    r0 = s["rows"][0]
    jadwal = tbl(["Bulan", "Saldo awal", "Angsuran pokok", "Bunga", "Total angsuran", "Saldo akhir"],
                 tabel_jadwal("menurun"),
                 footer='<tr class="fw-semibold"><td colspan="4">Total 24 bulan</td><td>' + money(s["tot_bayar"]) +
                        '</td><td class="text-body-secondary">termasuk bunga ' + money(s["tot_bunga"]) + "</td></tr>")

    tools_jadwal = ('<button class="btn btn-sm btn-outline-secondary" type="button"><i class="bi bi-printer"></i> Cetak</button>'
                    '<button class="btn btn-sm btn-outline-secondary" type="button"><i class="bi bi-file-earmark-pdf"></i> Unduh PDF</button>')

    ringkasan = dl([
        ("No. Simulasi", "SIM-2026-0142"),
        ("Pemohon", ANGGOTA["nama"] + " (" + ANGGOTA["no_anggota"] + ")"),
        ("Produk", "PJ-RG &middot; Pinjaman Reguler"),
        ("Pokok", money(SKENARIO["P"])),
        ("Tenor", "24 bulan"),
        ("Metode bunga", LABEL_METODE["menurun"]),
        ("Angsuran pertama", money(r0["angs"]) + ' <span class="small text-body-secondary">(pokok ' +
         money(r0["pokok"]) + " + bunga " + money(r0["bunga"]) + ")</span>"),
        ("Maks. rasio cicilan", "44% dari penghasilan"),
    ])

    beda = """<p class="small mb-2">Dengan pokok dan tenor yang sama:</p>
      <ul class="list-group list-group-flush small">
        <li class="list-group-item d-flex justify-content-between px-0">Total bunga tetap (flat)
          <span class="fw-semibold">""" + money(flat["tot_bunga"]) + """</span></li>
        <li class="list-group-item d-flex justify-content-between px-0">Total bunga menurun
          <span class="fw-semibold">""" + money(s["tot_bunga"]) + """</span></li>
        <li class="list-group-item d-flex justify-content-between px-0">Selisih (lebih hemat)
          <span class="fw-semibold text-success">""" + money(flat["tot_bunga"] - s["tot_bunga"]) + """</span></li>
      </ul>
      <p class="small text-body-secondary mt-2 mb-0">Catatan: bunga flat dihitung dari pokok awal sehingga bunga
      efektifnya lebih tinggi meski tarif per bulan sama.</p>"""

    lanjut = """<p class="small">Ajukan pinjaman memakai tabel simulasi ini. Dokumen dan data keperluan akan diminta
      pada langkah berikutnya.</p>
      <div class="d-grid gap-2">
        <a class="btn btn-primary" href="pengajuan-form.html"><i class="bi bi-send"></i> Ajukan pinjaman dengan simulasi ini</a>
        <a class="btn btn-outline-secondary" href="simulasi-form.html">Ubah parameter &amp; simulasi ulang</a>
        <button class="btn btn-link" type="button">Tidak, simpan saja tabel ini</button>
      </div>"""

    body = f"""
{stepper(STEP, 1)}
{note('<strong>Tabel simulasi diterbitkan oleh bendahara</strong> (Langkah 2&ndash;3 pada alur). Nomor simulasi '
      '<code>SIM-2026-0142</code> &middot; diterbitkan 07 Okt 2026 &middot; berlaku sampai 07 Nov 2026. '
      'Skema terpilih: <strong>' + LABEL_METODE["menurun"] + "</strong>.", "success", "bi-clipboard2-check")}
{kpi_row([
    ("Pokok pinjaman", money(SKENARIO["P"]), "PJ-RG &middot; Pinjaman Reguler", "primary", "bi-cash-stack"),
    ("Angsuran per bulan", money(s["angs_min"]), "menurun tiap bulan &middot; tertinggi " + money(s["angs_max"]), "info", "bi-calendar3"),
    ("Total bunga", money(s["tot_bunga"]), "bunga efektif " + pctf(s["r_eff_tahun"]) + " p.a.", "warning", "bi-percent"),
    ("Total yang dibayar", money(s["tot_bayar"]), "pokok + bunga (belum termasuk provisi)", "success", "bi-receipt"),
])}
<div class="row g-4">
  <div class="col-xl-8">
    {card("Perbandingan metode bunga", tbl(
        ["Metode", "Angsuran / bulan", "Total bunga", "Total bayar", "Bunga efektif bulanan", "Bunga efektif per tahun"],
        tabel_compare()),
        subtitle="Skenario sama: Rp 25,0 jt / 24 bulan / tarif 1,10% per bulan",
        tools='<span class="badge text-bg-primary">Menurun = rekomendasi bendahara</span>')}
    {card("Jadwal angsuran 24 bulan &mdash; Bunga menurun (efektif)", jadwal,
          footer="Kolom yang sama dipakai pada Kartu Piutang anggota dan mutasi pembayaran bendahara.",
          tools=tools_jadwal)}
  </div>
  <div class="col-xl-4">
    {card("Ringkasan simulasi", ringkasan, footer="Dasar perhitungan: SK Pengurus No. 07/SK/X/2026")}
    {card("Perbandingan vs bunga tetap", beda, subtitle="Poin yang biasanya ditanyakan anggota")}
    {card("Lanjut ke pengajuan", lanjut)}
  </div>
</div>"""
    return layout("anggota", "simulasi-form.html", "Hasil Simulasi Pinjaman", "Langkah 2&ndash;3",
                  body, desc="Tabel simulasi dari bendahara yang diserahkan ke anggota",
                  crumb=[("Simulasi Pinjaman", "simulasi-form.html"), ("Hasil simulasi", None)],
                  actions='<a class="btn btn-outline-primary" href="pengajuan-form.html">Ajukan pinjaman</a>')


add("anggota/dashboard.html", "anggota", "Dashboard Anggota", dashboard())
add("anggota/simulasi-form.html", "anggota", "Permintaan Simulasi", simulasi_form())
add("anggota/simulasi-hasil.html", "anggota", "Hasil Simulasi", simulasi_hasil())
