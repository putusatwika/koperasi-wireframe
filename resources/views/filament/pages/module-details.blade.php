<div class="space-y-5">
    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800/50">
        <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
            {{ filled($module['description']) ? $module['description'] : 'Belum ada deskripsi untuk modul ini.' }}
        </p>
    </div>

    <dl class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <dt class="text-sm font-medium">Status instalasi</dt>
            <dd class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $module['installed'] ? 'Sudah terpasang' : 'Belum terpasang' }}</dd>
        </div>
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <dt class="text-sm font-medium">Status modul</dt>
            <dd class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $module['enabled'] ? 'Aktif' : 'Nonaktif' }}</dd>
        </div>
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <dt class="text-sm font-medium">Versi</dt>
            <dd class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $module['installedVersion'] ?? 'Belum tercatat' }} → {{ $module['currentVersion'] }}</dd>
        </div>
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <dt class="text-sm font-medium">Migrasi baru</dt>
            <dd class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ count($module['pendingMigrations']) }} tersedia</dd>
        </div>
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <dt class="text-sm font-medium">Status pembaruan</dt>
            <dd class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $module['needsUpdate'] ? 'Perlu diperbarui' : 'Sudah terbaru' }}</dd>
        </div>
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <dt class="text-sm font-medium">Izin terdaftar</dt>
            <dd class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ count($module['missingPermissions']) }} belum tersedia dari {{ $module['configuredPermissions'] }}</dd>
        </div>
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <dt class="text-sm font-medium">Konfigurasi</dt>
            <dd class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $module['configAvailable'] ? 'Tersedia' : 'Tidak tersedia' }}</dd>
        </div>
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <dt class="text-sm font-medium">Seeder</dt>
            <dd class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $module['seederAvailable'] ? 'Akan dijalankan' : 'Tidak tersedia' }}</dd>
        </div>
    </dl>

    <p class="text-sm text-gray-600 dark:text-gray-300">
        Proses akan menjalankan migrasi yang belum diterapkan, seeder yang idempoten, dan sinkronisasi izin.
    </p>
</div>
