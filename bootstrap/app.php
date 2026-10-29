<?php

use App\Http\Middleware\ProxyTepercaya;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Aplikasi berjalan di belakang proxy Railway yang memutus HTTPS di
        // edge. Tanpa kepercayaan ini, Laravel menganggap koneksi sebagai
        // HTTP biasa dan membangun tautan aset ber-schema http, sehingga
        // browser memblokir CSS/JS saat halaman dibuka lewat https.
        //
        // Daftarnya dibatasi ke jaringan proxy, bukan '*': Symfony membaca
        // X-Forwarded-For dari kanan dan berhenti di hop pertama yang tidak
        // tepercaya, jadi nilai kiriman klien di sebelah kiri tidak pernah
        // dipakai sebagai IP. Dengan '*' seluruh rantai dianggap tepercaya
        // dan IP kiriman klien itulah yang akhirnya dipakai throttle.
        //
        // Daftarnya baru dibaca saat request masuk lewat ProxyTepercaya
        // karena config() belum tersedia ketika closure ini dijalankan.
        $middleware->replace(TrustProxies::class, ProxyTepercaya::class);

        // Halaman masuk memakai nama rute sendiri, jadi tamu yang belum
        // autentikasi diarahkan ke sana, bukan ke rute 'login' bawaan.
        $middleware->redirectGuestsTo(fn () => route('masuk'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Rate limit pada halaman web tetap memakai status 429 yang benar,
        // namun diganti dengan halaman penjelasan yang mudah dipahami.
        $exceptions->render(function (TooManyRequestsHttpException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            $exception->setHeaders(array_merge($exception->getHeaders(), [
                'Retry-After' => $exception->getHeaders()['Retry-After'] ?? 60,
            ]));

            return response()->view('errors.429', [
                'pesan' => 'Terlalu banyak permintaan. Mohon tunggu beberapa saat sebelum mencoba lagi.',
            ], 429, $exception->getHeaders());
        });
    })->create();
