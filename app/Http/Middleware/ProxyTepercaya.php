<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;

/**
 * Daftar proxy tepercaya yang dibaca saat permintaan masuk.
 *
 * trustProxies() di bootstrap/app.php menerima daftar langsung, padahal
 * closure-nya dijalankan sebelum konfigurasi dimuat pada perintah artisan
 * sehingga config() belum bisa dipakai di sana. Class ini menggantikan
 * middleware bawaan dan membaca config/proxy.php pada saat handle, ketika
 * seluruh konfigurasi sudah tersedia.
 */
final class ProxyTepercaya extends TrustProxies
{
    /**
     * @return array<int, string>
     */
    protected function proxies()
    {
        return config('proxy.tepercaya');
    }
}
