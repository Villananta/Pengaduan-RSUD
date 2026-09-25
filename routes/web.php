<?php

use App\Http\Controllers\Admin\PengaduanController as AdminPengaduanController;
use App\Http\Controllers\PengaduanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/buat-aduan', [PengaduanController::class, 'create'])->name('pengaduan.create');
Route::post('/buat-aduan', [PengaduanController::class, 'store'])->name('pengaduan.store');
Route::get('/buat-aduan/sukses/{kode}', [PengaduanController::class, 'sukses'])->name('pengaduan.sukses');
Route::get('/lacak-tiket', [PengaduanController::class, 'lacak'])->name('pengaduan.lacak');
Route::post('/lacak-tiket/{kode}/pesan', [PengaduanController::class, 'kirimPesan'])->name('pengaduan.pesan');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/pengaduan', [AdminPengaduanController::class, 'index'])->name('pengaduan.index');
    Route::get('/pengaduan/{kode}', [AdminPengaduanController::class, 'show'])->name('pengaduan.show');
    Route::post('/pengaduan/{kode}/status', [AdminPengaduanController::class, 'ubahStatus'])->name('pengaduan.status');
    Route::post('/pengaduan/{kode}/balas', [AdminPengaduanController::class, 'balas'])->name('pengaduan.balas');
});
