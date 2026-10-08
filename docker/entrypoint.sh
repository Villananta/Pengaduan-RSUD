#!/bin/sh
set -e

# Paket layanan dibangkitkan saat container hidup, bukan saat image dibangun,
# supaya daftar paket selalu konsisten dengan vendor yang terpasang.
php artisan package:discover --ansi

# Migrasi idempoten sehingga aman diulang setiap container naik. Kalau
# database belum siap pada start pertama, container keluar dan Railway
# mencoba lagi sesuai restartPolicy di railway.json.
php artisan migrate --force

# Simpulkan public/storage ke volume lampiran (storage/app/public). Tampa
# volume, symlink tetap dibuat dan lampiran hanya bertahan sampai redeploy.
php artisan storage:link --force

# Cache konfigurasi, rute, dan view dibangun di sini, bukan saat image
# dibuat, karena konfigurasi bergantung pada env Railway yang baru tersedia
# ketika container dijalankan.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# PORT diisi Railway; fallback 8080 dipakai saat menjalankan di lokal.
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"