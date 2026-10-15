<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\RiwayatAdmin;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiwayatController extends Controller
{
    /**
     * Halaman riwayat global aksi admin humas.
     *
     * Menampilkan seluruh jejak audit perubahan tahap, penandaan kasus berat,
     * dan aksi lain yang tertulis di riwayat_status_pengaduan.
     * Mendukung filter status, rentang tanggal, pencarian, dan paginasi.
     */
    public function index(Request $request): View
    {
        $riwayat = RiwayatAdmin::dariRequest($request);

        return view('admin.riwayat.index', [
            'riwayat' => $riwayat,
            'pilihanStatus' => RiwayatAdmin::pilihanStatus(),
            'statistik' => RiwayatAdmin::statistikGlobal(),
        ]);
    }
}
