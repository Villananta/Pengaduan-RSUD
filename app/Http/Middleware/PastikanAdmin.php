<?php

namespace App\Http\Middleware;

use App\Enums\PeranPengguna;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menutup konsol admin hanya untuk akun berperan admin.
 *
 * Grup rute admin sudah dibungkus `auth`, jadi penjaga ini hanya memastikan
 * yang masuk benar-benar pemegang konsol humas. PIC unit yang mengetik URL
 * konsol admin secara langsung harus berhenti di sini dengan 403.
 */
class PastikanAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->peran === PeranPengguna::Admin, 403);

        return $next($request);
    }
}
