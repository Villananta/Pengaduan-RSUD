<?php

namespace App\Http\Middleware;

use App\Models\MasterUnit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengunci konsol unit ke unit milik akun yang sedang masuk.
 *
 * PastikanPic hanya memisahkan PIC dari admin, bukan memisahkan PIC unit A
 * dari PIC unit B — kode unit di URL masih bisa ditukar siapa saja. Penjaga
 * ini menutup celah itu dengan mencocokkan unit di URL terhadap catatan
 * pivot akun pemakai, sehingga satu akun tidak bisa membaca atau menjawab
 * pengaduan unit lain walaupun kodenya diketik langsung.
 *
 * Unit yang tidak dimiliki dibalas 403, bukan 404: kode unit memang sudah
 * dikenal petugas dari alur tugas mereka, jadi menyembunyikan keberadaannya
 * tidak menambah nilai apa pun. Unit non-aktif tetap urusan
 * App\Http\Middleware\PastikanUnitAktif yang berjalan setelah penjaga ini.
 */
class PastikanUnitSendiri
{
    public function handle(Request $request, Closure $next): Response
    {
        $unit = $request->route('unit');

        // Binding implicit sudah menyerahkan model MasterUnit sebelum
        // middleware berjalan, jadi di sini tinggal memeriksa kepemilikannya.
        abort_unless(
            $unit instanceof MasterUnit && $request->user()?->terdaftarDiUnit($unit),
            403,
        );

        return $next($request);
    }
}
