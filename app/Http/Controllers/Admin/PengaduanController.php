<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DaftarPengaduan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengaduanController extends Controller
{
    /**
     * Daftar pengaduan konsol admin dalam bentuk tabel yang bisa disaring.
     *
     * Penyusunan query, jumlah per tab, dan kalimat pada tabel diserahkan
     * ke App\Support\DaftarPengaduan supaya template tidak memakai query.
     */
    public function index(Request $request): View
    {
        return view('admin.pengaduan.index', [
            'daftar' => DaftarPengaduan::dariRequest($request),
        ]);
    }
}
