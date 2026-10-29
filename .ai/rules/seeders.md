---
paths:
  - 'Modules/*/database/seeders/*COASeeder.php'
---

# Seeders

## Seeder COA modul hanya mengisi yang kosong
Seeder COA per modul bersifat heal-only: buat akun bila belum ada (by `code`) dan hanya isi `coa_config.coa_account_id` bila nilainya NULL. Jangan pakai `updateOrCreate` untuk menimpa — pengaturan COA manual bendahara tidak boleh ditimpa saat module:update. Helper: App\Support\Accounting\CoaAccountEnsurer (ensure + fillConfig).
