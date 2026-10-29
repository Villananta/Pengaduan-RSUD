<?php

$rentang = trim((string) env('TRUSTED_PROXIES', ''));

return [
    /*
     * Alamat dan rentang IP proxy yang boleh memasok header X-Forwarded-*.
     *
     * Edge Railway terhubung ke container lewat jaringan privatnya, jadi
     * rentang 100.64.0.0/10 sudah cukup untuk produksi saat ini. Daftar ini
     * sengaja tidak memakai '*' seperti sebelumnya: dengan '*' seluruh
     * X-Forwarded-For kiriman klien ikut dipercaya, sehingga kunci throttle
     * berbasis IP bisa diganti-ganti hanya dengan mengubah header.
     *
     * Platform lain dengan rentang edge berbeda (misalnya Render memakai
     * 74.220.48.0/24 dan 74.220.56.0/24) cukup mengisi TRUSTED_PROXIES
     * dipisah koma tanpa mengubah kode.
     */
    'tepercaya' => $rentang === ''
        ? ['100.64.0.0/10', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '127.0.0.1', '::1', 'fc00::/7']
        : array_values(array_filter(array_map('trim', explode(',', $rentang)))),
];
