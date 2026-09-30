<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DaftarPengaduan;
use App\Support\DetailPengaduan;
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

    /**
     * Workspace & Detail: satu tiket pengaduan yang dibuka dari daftar.
     *
     * Tiket yang tidak ada dibiarkan 404 supaya admin yang salah ketik kode
     * tidak diarahkan ke halaman kosong tanpa penjelasan.
     */
    public function show(string $kode): View
    {
        return view('admin.pengaduan.show', [
            'detail' => DetailPengaduan::dariKode($kode),
        ]);
    }
}
