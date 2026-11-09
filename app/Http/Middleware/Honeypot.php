<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Honeypot Protection untuk Form Publik.
 *
 * Prinsip: tambah input tersembunyi (name="website") di form.
 * Manusia tidak akan mengisi (tersembunyi via CSS), bot akan mengisi.
 * Jika field terisi → anggap bot → return 200 OK tapi tidak proses data.
 */
class Honeypot
{
    /** Nama field honeypot (bisa diubah via config). */
    protected string $field = 'website';

    /**
     * Handle incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya cek pada request POST dengan field honeypot
        if ($request->isMethod('POST') && $request->has($this->field)) {
            $value = (string) $request->input($this->field);

            // Jika terisi (bot mengisi) → log & return response "sukses" tapi no-op
            if ($value !== '') {
                // Log untuk monitoring (opsional)
                logger()->warning('Honeypot triggered', [
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'user_agent' => $request->userAgent(),
                    'field' => $this->field,
                ]);

                // Return response yang terlihat normal buat bot
                // Tapi tidak menjalankan logic sesungguhnya
                return response()->view('honeypot.blocked', [], 200);
            }
        }

        return $next($request);
    }
}
