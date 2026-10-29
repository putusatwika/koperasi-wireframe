---
paths:
  - 'tests/**'
---

# Tests

## Jangan migrate:fresh pakai koneksi dev
Jangan pernah menjalankan `php artisan migrate:fresh` (atau seeder) tanpa konteks testing: artisan memakai koneksi `.env` (MySQL dev) sehingga menghapus data dev. Test PHPUnit memakai sqlite `:memory:` dari phpunit.xml. Untuk memeriksa perilaku runtime, pakai `php artisan tinker`/skrip yang tidak destruktif, bukan migrate:fresh.
