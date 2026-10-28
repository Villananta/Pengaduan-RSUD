<?php

namespace App\Providers;

use App\Support\NotifikasiAdmin;
use Illuminate\Support\Facades\View as ViewFactory;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Lonceng admin dipasang dari sini karena layout dipakai delapan
        // halaman; menyalin dua query notifikasi ke tiap controller akan
        // membuat titik yang harus diingat setiap kali ada halaman baru.
        ViewFactory::composer('admin.layout', function (View $view): void {
            $pengguna = auth()->user();

            $view->with(
                'notifikasiAdmin',
                $pengguna === null ? null : new NotifikasiAdmin($pengguna),
            );
        });
    }
}
