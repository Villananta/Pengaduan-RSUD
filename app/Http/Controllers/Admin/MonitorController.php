<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\MonitorDisposisi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitorController extends Controller
{
    /**
     * Monitor Disposisi & SLA: tegur unit atas investigasi yang berjalan.
     *
     * Halaman ini sengaja dipisah dari Beranda Utama karena yang dipantau
     * di sini hanya tiket yang sudah ditugaskan ke instalasi, sedangkan
     * beranda masih menyoroti pengaduan yang ditangani humas langsung
     * dan tiket yang sudah selesai.
     *
     * Seluruh query dan penyusunan kalimat diserahkan ke
     * App\Support\MonitorDisposisi, jadi template ini tidak memakai query.
     */
    public function index(Request $request): View
    {
        return view('admin.monitor.index', [
            'monitor' => MonitorDisposisi::dariRequest($request),
        ]);
    }
}
