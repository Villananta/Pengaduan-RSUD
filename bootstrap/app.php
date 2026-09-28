<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
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
