<?php

namespace App\Http\Middleware;

use App\Models\MasterUnit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak unit yang sudah dinonaktifkan dari master data.
 *
 * Unit non-aktif selama ini hanya disembunyikan dari halaman pemilih, jadi
 * URL langsung ke dashboard-nya tetap terbuka. Penjaga ini membuat status
 * aktif berlaku sungguhan tanpa bergantung pada tautan yang tampil di layar.
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
