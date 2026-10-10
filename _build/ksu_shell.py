"""Shell halaman (navbar, header, footer) + registry halaman."""
import os
from ksu_core import (BASE, CDN_CSS, CDN_ICONS, CDN_JS, KOPERASI, ROLE_META, NAV, note, badge)

PAGES = {}  # relpath -> dict(role, title, html)


def add(relpath, role, title, html):
    PAGES[relpath] = dict(role=role, title=title, html=html)


def navbar(role, active):
    m = ROLE_META[role]
    items = ""
    for href, label in NAV[role]:
        cls = "nav-link active fw-semibold" if href == active else "nav-link"
        items += f'<li class="nav-item"><a class="{cls}" href="{href}">{label}</a></li>'
    switcher = ""
    for r, meta in ROLE_META.items():
        cur = " active" if r == role else ""
        href = "dashboard.html" if r == role else f'../{r}/dashboard.html'
        switcher += f'<li><a class="dropdown-item{cur}" href="{href}">{meta["label"]}</a></li>'
    root = "../" if role else ""
    return f"""<nav class="navbar navbar-expand-lg bg-{m['theme']} border-bottom" data-bs-theme="dark">
  <div class="container-xxl">
    <a class="navbar-brand d-flex align-items-center gap-2" href="{root}index.html">
      <i class="bi bi-bank2"></i><span class="fw-semibold">{KOPERASI}</span>
    </a>
    <span class="badge text-bg-light d-none d-xl-inline">{m['portal']}</span>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navUtama"
            aria-controls="navUtama" aria-expanded="false" aria-label="Buka navigasi">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navUtama">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">{items}</ul>
      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle"></i> {m['user']}
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><h6 class="dropdown-header">{m['user_sub']}</h6></li>
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header">Pindah role (wireframe)</h6></li>
            {switcher}
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="{root}index.html"><i class="bi bi-diagram-3"></i> Peta alur sistem</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-box-arrow-right"></i> Keluar</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>"""


def layout(role, active, title, req, body, desc="", crumb=None, actions="", modals="", wide=False):
    """role: kunci ROLE_META. active: nama file nav. req: label keterlacakan kebutuhan."""
    m = ROLE_META[role]
    crumb_html = ""
    if crumb:
        inner = "".join(
            (f'<li class="breadcrumb-item"><a href="{h}">{t}</a></li>' if h else f'<li class="breadcrumb-item active">{t}</li>')
            for t, h in crumb
        )
        crumb_html = f'<nav aria-label="breadcrumb"><ol class="breadcrumb small mb-2">{inner}</ol></nav>'
    return f"""<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{title} · {m['label']} · {KOPERASI}</title>
  <link href="{CDN_CSS}" rel="stylesheet">
  <link href="{CDN_ICONS}" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
{navbar(role, active)}
<main class="container-xxl py-4">
{crumb_html}
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div>
      <h1 class="h4 fw-bold mb-1">{title}</h1>
      <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="badge text-bg-{m['theme']}">{m['label']}</span>
        <span class="badge text-info-emphasis bg-info-subtle border border-info-subtle">Wireframe · {req}</span>
        {f'<span class="small text-body-secondary">{desc}</span>' if desc else ''}
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2">{actions}</div>
  </div>
{body}
</main>
<footer class="border-top bg-body py-4 mt-5">
  <div class="container-xxl small text-body-secondary d-flex justify-content-between flex-wrap gap-2">
    <span><i class="bi bi-bank2"></i> {KOPERASI} — Modul Simpan Pinjam (wireframe statis)</span>
    <span>Bootstrap 5.3 · tanpa backend · data dummy untuk diskusi dengan programmer</span>
  </div>
</footer>
{modals}
<script src="{CDN_JS}"></script>
</body>
</html>
"""
