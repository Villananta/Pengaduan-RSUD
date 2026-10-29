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

# PORT diisi Railway/Render; fallback 8080 dipakai saat menjalankan di lokal.
# nginx tidak bisa membaca environment variable di konfigurasinya, jadi
# templatenya dirender menjadi berkas konfigurasi biasa lebih dulu.
sed "s/__PORT__/${PORT:-8080}/g" /app/docker/nginx.conf.template > /etc/nginx/http.d/default.conf

# Berkas hasil artisan di atas dibuat sebagai root, sedangkan worker PHP-FPM
# berjalan sebagai www-data. Tanpa chown, log dan view terkompilasi tidak
# bisa ditulis dan setiap halaman berakhir dengan error 500.
chown -R www-data:www-data /app/storage /app/bootstrap/cache

# PHP-FPM melayani PHP di 127.0.0.1:9000 dan nginx memegang PID 1 supaya
# sinyal berhenti dari platform diterima apa adanya. Server bawaan
# `php artisan serve` hanya satu proses, sehingga permintaan antre begitu
# ada lebih dari satu pembaca.
php-fpm --nodaemonize &
exec nginx -g 'daemon off;'
