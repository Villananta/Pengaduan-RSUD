<?php

use App\Http\Controllers\PengaduanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/buat-aduan', [PengaduanController::class, 'create'])->name('pengaduan.create');
Route::post('/buat-aduan', [PengaduanController::class, 'store'])->name('pengaduan.store');
Route::get('/buat-aduan/sukses/{kode}', [PengaduanController::class, 'sukses'])->name('pengaduan.sukses');