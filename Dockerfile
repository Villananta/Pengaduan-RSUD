# Tahap 1 -- membangun aset frontend (Vite + Tailwind 4).
#
# Dipisah ke image node supaya runtime PHP tidak membawa npm. `app.js`
# dan `app.css` dipakai semua layout melalui helper @vite, jadi hasil
# build wajib menempel di image final.
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# Tahap 2 -- memasang dependensi composer tanpa perangkat pengembangan.
#
# Composer dijalankan di image terpisah supaya ekstensi PHP tidak perlu
# dipasang dua kali. Skrip composer dimatikan karena package:discover
# dijalankan ulang saat container hidup (bootstrap/cache harus segar).
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-scripts

# Tahap 3 -- image runtime final.
FROM php:8.4-cli-alpine

WORKDIR /app

# Ekstensi yang dibutuhkan Laravel untuk MySQL, blading, dan dukungan zip
# composer/upload. libxml, ctype, tokenizer, openssl, dan fileinfo sudah
# menyatu dengan image php resmi sehingga tidak perlu diinstall manual.
RUN apk add --no-cache libzip-dev oniguruma-dev && \
    docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring bcmath zip

# Validasi controller mengizinkan 4 MB per berkas, jadi batas default PHP
# 2M harus dilonggarkan lebih dulu supaya unggahan tidak diam-diam gagal.
RUN echo "upload_max_filesize=8M" > $PHP_INI_DIR/conf.d/upload.ini && \
    echo "post_max_size=8M" >> $PHP_INI_DIR/conf.d/upload.ini

COPY --from=vendor /app/vendor /app/vendor
COPY --from=frontend /app/public/build /app/public/build
COPY . /app

RUN chmod +x /app/docker/entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/app/docker/entrypoint.sh"]