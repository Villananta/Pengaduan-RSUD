<?php

namespace App\Http\Middleware;

use App\Enums\PeranPengguna;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menutup konsol unit layanan hanya untuk akun berperan PIC unit.
 *
 * Penjaga tingkat peran ini memisahkan dua konsol satu sama lain: admin
 * humas membuka konsolnya sendiri, PIC membuka konsol unit. Penguncian
 * per-unit (hanya PIC unit terkait yang boleh masuk ke {unit} tertentu)
 * adalah lapisan berikutnya dan belum dipasang di tahap ini.
 */
class PastikanPic
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->peran === PeranPengguna::Unit, 403);

        return $next($request);
    }
}
