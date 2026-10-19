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
 * humas membuka konsolnya sendiri, PIC membuka konsol unit. Pemisahan antar
 * PIC (unit A tidak boleh membuka unit B) bukan tugas penjaga ini karena
 * tidak tahu unit mana yang sedang dibuka — tugas
 * App\Http\Middleware\PastikanUnitSendiri yang memeriksa kepemilikan unit
 * di URL terhadap pivot akun pemakai.
 */
class PastikanPic
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->peran === PeranPengguna::Unit, 403);

        return $next($request);
    }
}
