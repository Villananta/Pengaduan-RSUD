<?php

namespace App\Http\Middleware;

use App\Models\MasterUnit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak unit yang sudah dinonaktifkan dari master data.
 *
 * Penjaga ini membuat status aktif berlaku sungguhan: URL langsung ke
 * dashboard maupun halaman disposisi unit yang dimatikan ikut 404, bukan
 * sekadar hilang dari tampilan. Karena itu berpindah unit tidak bisa
 * dipakai untuk menghidupkan kembali akses yang sudah dicabut admin.
 */
class PastikanUnitAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $unit = $request->route('unit');

        // Binding implicit sudah menjalankan query sebelum middleware ini, jadi
        // yang diterima di sini adalah model MasterUnit, bukan sekadar kode.
        abort_unless($unit instanceof MasterUnit && $unit->aktif, 404);

        return $next($request);
    }
}
