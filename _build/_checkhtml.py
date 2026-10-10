"""Pemeriksaan kasar keseimbangan tag HTML + daftar berkas hasil build."""
import pathlib
import re
from html.parser import HTMLParser

BASE = pathlib.Path("/Users/wika/Documents/PROJECT/Koperasi-Project/UI_design/modul_peminjaman")
VOID = {"area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "param",
        "source", "track", "wbr"}


class Cek(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.stack = []
        self.err = []

    def handle_starttag(self, tag, attrs):
        if tag not in VOID:
            self.stack.append(tag)

    def handle_endtag(self, tag):
        if tag in VOID:
            return
        if not self.stack:
            self.err.append("penutup berlebih: </%s>" % tag)
            return
        if self.stack[-1] == tag:
            self.stack.pop()
        else:
            if tag in self.stack:
                while self.stack and self.stack[-1] != tag:
                    self.err.append("tidak ditutup: <%s>" % self.stack.pop())
                self.stack.pop()
            else:
                self.err.append("penutup tanpa pembuka: </%s>" % tag)


total = 0
masalah = 0
for f in sorted(BASE.rglob("*.html")):
    c = Cek()
    c.feed(f.read_text())
    sisa = [t for t in c.stack if t not in ("html", "body")]
    total += 1
    if c.err or sisa:
        masalah += 1
        print("!!", f.relative_to(BASE), "| error:", c.err[:5], "| belum ditutup:", sisa[:5])
print("berkas diperiksa:", total, "| bermasalah:", masalah)

# cek keberadaan komponen penting di setiap halaman
for f in sorted(BASE.rglob("*.html")):
    s = f.read_text()
    for wajib in ("<title>", "navbar", "cdn.jsdelivr.net/npm/bootstrap@5.3.3", "bootstrap-icons"):
        if wajib not in s:
            print("kurang:", f.relative_to(BASE), "->", wajib)
print("selesai")
