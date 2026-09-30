<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PengaduanController as AdminPengaduanController;
use App\Http\Controllers\PengaduanController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/buat-aduan');

Route::view('/maklumat-prosedur', 'maklumat.index')->name('maklumat.index');

// Batas percobaan agar kode tiket tidak bisa ditebak brute force dan
// endpoint publik tidak bisa dipakai untuk spam.
Route::get('/lacak-tiket', [PengaduanController::class, 'lacak'])->name('pengaduan.lacak');

Route::post('/lacak-tiket/verifikasi', [PengaduanController::class, 'verifikasi'])
    ->middleware('throttle:10,1')
    ->name('pengaduan.verifikasi');

Route::post('/lacak-tiket/{kode}/pesan', [PengaduanController::class, 'kirimPesan'])
    ->middleware('throttle:20,1')
    ->name('pengaduan.pesan');

Route::get('/buat-aduan', [PengaduanController::class, 'create'])->name('pengaduan.create');

Route::post('/buat-aduan', [PengaduanController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('pengaduan.store');

Route::get('/buat-aduan/sukses/{kode}', [PengaduanController::class, 'sukses'])->name('pengaduan.sukses');

// Konsol admin.
//
// Catatan keamanan: seluruh rute di bawah masih terbuka tanpa login,
// mengikuti halaman beranda dan daftar yang lebih dulu dibuat. Begitu
// auth admin dipasang, grup ini cukup diberi middleware auth.admin
// tanpa mengubah nama rute yang sudah dipakai template.
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/pengaduan', [AdminPengaduanController::class, 'index'])->name('pengaduan.index');
    Route::get('/pengaduan/{kode}', [AdminPengaduanController::class, 'show'])->name('pengaduan.show');

    Route::post('/pengaduan/{kode}/draf', [AdminPengaduanController::class, 'simpanDraf'])->name('pengaduan.draf');
    Route::post('/pengaduan/{kode}/jawaban', [AdminPengaduanController::class, 'kirimJawaban'])->name('pengaduan.jawaban');
    Route::post('/pengaduan/{kode}/kembalikan', [AdminPengaduanController::class, 'kembalikan'])->name('pengaduan.kembalikan');
    Route::post('/pengaduan/{kode}/kasus-berat', [AdminPengaduanController::class, 'kasusBerat'])->name('pengaduan.kasus-berat');
});
