"""Kerangka (framework) wireframe high-fidelity modul simpan pinjam KSU.

ATURAN YANG DIPATUHI:
- HANYA komponen Bootstrap 5.3 + Bootstrap Icons (CDN).
- TIDAK ADA CSS kustom (tanpa <style>, tanpa atribut style="").
- TIDAK ADA JS kustom (hanya bundle Bootstrap untuk komponen bawaan: modal, dropdown, tab, collapse).
"""
import os

BASE = os.path.expanduser("~/Documents/PROJECT/Koperasi-Project/UI_design/modul_peminjaman")

CDN_CSS = "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
CDN_ICONS = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
CDN_JS = "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"

KOPERASI = "KSU Mitra Sejahtera"

ROLE_META = {
    "anggota": dict(
        label="Anggota", theme="primary", portal="Portal Anggota",
        user="Budi Santoso", user_sub="Anggota AG-0142",
    ),
    "admin": dict(
        label="Admin Koperasi / Bendahara", theme="dark", portal="Back Office Bendahara",
        user="Siti Rahmawati", user_sub="Bendahara Koperasi",
    ),
    "ketua": dict(
        label="Ketua Koperasi", theme="success", portal="Ruang Ketua",
        user="Dr. Hendra Wijaya", user_sub="Ketua Koperasi",
    ),
    "pengawas": dict(
        label="Pengawas Koperasi", theme="secondary", portal="Ruang Pengawas",
        user="Prof. Anwar Makmur", user_sub="Pengawas Koperasi",
    ),
}

NAV = {
    "anggota": [
        ("dashboard.html", "Dashboard"),
        ("simulasi-form.html", "Simulasi Pinjaman"),
        ("pengajuan-form.html", "Ajukan Pinjaman"),
        ("pengajuan-status.html", "Status Pengajuan"),
        ("pinjaman-aktif.html", "Cicilan Aktif"),
        ("kartu-piutang.html", "Kartu Piutang"),
        ("riwayat.html", "Riwayat & Penutupan"),
    ],
    "admin": [
        ("dashboard.html", "Dashboard"),
        ("simulasi-masuk.html", "Simulasi Masuk"),
        ("pengajuan.html", "Verifikasi Pengajuan"),
        ("rekomendasi.html", "Rekomendasi ke Ketua"),
        ("pencairan.html", "Pencairan Dana"),
        ("autodebet-upload.html", "Upload Autodebet"),
        ("autodebet-gagal.html", "Autodebet Gagal"),
        ("parameter.html", "Parameter Pinjaman"),
        ("anggota.html", "Data Anggota"),
    ],
    "ketua": [
        ("dashboard.html", "Dashboard"),
        ("daftar-peminjam.html", "Daftar Peminjam & Prioritas"),
        ("persetujuan.html", "Persetujuan"),
        ("pencairan.html", "Pencairan"),
    ],
    "pengawas": [
        ("dashboard.html", "Dashboard Pengawasan"),
        ("daftar-peminjam.html", "Daftar Peminjam"),
        ("pencairan.html", "Monitoring Pencairan"),
    ],
}


# ----------------------------------------------------------------------------- util
def money(n):
    return "Rp " + f"{round(n):,}".replace(",", ".")


def jt(n):
    """Format singkat jutaan: 25 -> Rp 25,0 jt"""
    return "Rp " + f"{n/1_000_000:.1f}".replace(".", ",") + " jt"


def pct(x, d=2):
    """Format angka yang SUDAH dalam satuan persen (mis. 1.1 -> '1,10%')."""
    return f"{x:.{d}f}".replace(".", ",") + "%"


def pctf(x, d=2):
    """Format fraksi menjadi persen (mis. 0.011 -> '1,10%')."""
    return f"{x * 100:.{d}f}".replace(".", ",") + "%"


def badge(text, color="secondary", pill=True, outline=False):
    cls = "badge " + ("rounded-pill " if pill else "") + (f"text-{color}-emphasis bg-{color}-subtle border border-{color}-subtle" if outline else f"text-bg-{color}")
    return f'<span class="{cls}">{text}</span>'


STATUS_COLOR = {
    "Draft": "secondary", "Menunggu Simulasi": "warning", "Simulasi Selesai": "info",
    "Diajukan": "primary", "Verifikasi Dokumen": "warning", "Direkomendasikan": "info",
    "Disetujui": "success", "Disetujui dengan Revisi": "success", "Ditolak": "danger",
    "Dicairkan": "dark", "Aktif": "primary", "Lunas": "success", "Menunggu Persetujuan": "warning",
    "Diterima": "success", "Gagal": "danger", "Menunggu Konfirmasi": "warning", "Revisi": "warning",
}


def st(text):
    return badge(text, STATUS_COLOR.get(text, "secondary"))


# ----------------------------------------------------------------------------- komponen
def kpi(label, value, sub="", color="primary", icon="bi-graph-up-arrow"):
    return f"""<div class="col-sm-6 col-xl-3">
  <div class="card h-100">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start gap-2">
        <div>
          <div class="text-body-secondary text-uppercase fw-semibold small">{label}</div>
          <div class="fs-4 fw-bold mt-1">{value}</div>
          <div class="small text-body-secondary mt-1">{sub}</div>
        </div>
        <i class="bi {icon} fs-3 text-{color}"></i>
      </div>
    </div>
  </div>
</div>"""


def kpi_row(items):
    return '<div class="row g-3 mb-4">' + "".join(kpi(*i) for i in items) + "</div>"


def card(title, body, tools="", footer="", cls="mb-4", subtitle="", body_cls=""):
    head = ""
    if title:
        sub = f'<div class="small text-body-secondary">{subtitle}</div>' if subtitle else ""
        head = (f'<div class="card-header bg-body-tertiary d-flex justify-content-between '
                f'align-items-center flex-wrap gap-2"><div><h2 class="h6 fw-bold mb-0">{title}</h2>{sub}</div>'
                f'<div class="d-flex flex-wrap gap-2">{tools}</div></div>')
    foot = f'<div class="card-footer bg-body-tertiary small text-body-secondary">{footer}</div>' if footer else ""
    return f'<div class="card {cls}">{head}<div class="card-body {body_cls}">{body}</div>{foot}</div>'


def tbl(headers, rows, cls="table table-sm table-hover align-middle mb-0", thead="table-light", footer="", empty_text=""):
    head = "".join(f"<th scope='col'>{h}</th>" for h in headers)
    if rows:
        body = "".join("<tr>" + "".join(f"<td>{c}</td>" for c in r) + "</tr>" for r in rows)
    else:
        body = f'<tr><td colspan="{len(headers)}" class="text-center text-body-secondary py-4">{empty_text or "Belum ada data"}</td></tr>'
    foot = f'<tfoot class="table-group-divider table-light">{footer}</tfoot>' if footer else ""
    return (f'<div class="table-responsive"><table class="{cls}"><thead class="{thead}"><tr>{head}</tr></thead>'
            f'<tbody>{body}</tbody>{foot}</table></div>')


def note(text, color="info", icon="bi-info-circle"):
    return f'<div class="alert alert-{color} d-flex gap-2 py-2 mb-4"><i class="bi {icon}"></i><div class="small">{text}</div></div>'


def stepper(items, current):
    out = '<div class="d-flex flex-wrap align-items-center gap-2 mb-4">'
    for i, lab in enumerate(items, 1):
        if i == current:
            cls = "text-bg-primary border border-primary"
        elif i < current:
            cls = "text-primary-emphasis bg-primary-subtle border border-primary-subtle"
        else:
            cls = "text-body-secondary bg-body-secondary border"
        out += f'<span class="badge rounded-pill border px-3 py-2 fw-normal {cls}">{i}. {lab}</span>'
        if i < len(items):
            out += '<i class="bi bi-chevron-right small text-body-secondary"></i>'
    return out + "</div>"


def dl(items, cls="row g-0"):
    """items: list of (label, value)."""
    out = f'<dl class="{cls} mb-0">'
    for k, v in items:
        out += (f'<dt class="col-sm-4 col-lg-3 fw-normal text-body-secondary py-2 border-bottom">{k}</dt>'
                f'<dd class="col-sm-8 col-lg-9 py-2 mb-0 border-bottom">{v}</dd>')
    return out + "</dl>"


def ph(text="Placeholder", icon="bi-image", ratio="ratio-16x9"):
    """Placeholder area memakai utility .ratio Bootstrap (tanpa CSS kustom)."""
    return (f'<div class="{ratio} bg-body-secondary border rounded"><div class="d-flex flex-column '
            f'align-items-center justify-content-center text-body-secondary"><i class="bi {icon} fs-2"></i>'
            f'<span class="small">{text}</span></div></div>')


def bars(items, color="primary"):
    """Grafik batang horizontal memakai komponen .progress Bootstrap (lebar via utility w-*).

    items: list of (label, value_text, percent 0-100)
    """
    out = ""
    for label, val, p in items:
        w = max(5, min(100, int(round(p / 5.0)) * 5))  # dibulatkan ke kelipatan 5 -> utility w-*
        out += (f'<div class="d-flex align-items-center gap-2 mb-2"><div class="small text-body-secondary text-nowrap">'
                f'{label}</div><div class="progress flex-fill" role="progressbar" aria-label="{label}" '
                f'aria-valuenow="{p}" aria-valuemin="0" aria-valuemax="100" style="height:1.25rem">'
                f'<div class="progress-bar w-{w} bg-{color}">{val}</div></div></div>')
    return out.replace('style="height:1.25rem"', '').replace(' style="height:1.25rem"', '')
