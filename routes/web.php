<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MonitorController as AdminMonitorController;
use App\Http\Controllers\Admin\PengaduanController as AdminPengaduanController;
use App\Http\Controllers\Admin\UnitController as AdminUnitController;
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
    Route::get('/monitor', [AdminMonitorController::class, 'index'])->name('monitor.index');
    Route::get('/pengaduan', [AdminPengaduanController::class, 'index'])->name('pengaduan.index');

    // Formulir pencatatan aduan yang masuk lewat telepon, SMS, atau loket.
    //
    // Rute ini wajib didaftarkan sebelum /pengaduan/{kode} supaya kata
    // "buat" tidak tertangkap sebagai kode tiket yang tidak ada.
    Route::get('/pengaduan/buat', [AdminPengaduanController::class, 'create'])->name('pengaduan.create');
    Route::post('/pengaduan/buat', [AdminPengaduanController::class, 'store'])->name('pengaduan.store');

    Route::get('/pengaduan/{kode}', [AdminPengaduanController::class, 'show'])->name('pengaduan.show');

    Route::post('/pengaduan/{kode}/balas', [AdminPengaduanController::class, 'balas'])->name('pengaduan.balas');
    Route::post('/pengaduan/{kode}/tahap', [AdminPengaduanController::class, 'pindahTahap'])->name('pengaduan.tahap');
    Route::post('/pengaduan/{kode}/draf', [AdminPengaduanController::class, 'simpanDraf'])->name('pengaduan.draf');
    Route::post('/pengaduan/{kode}/jawaban', [AdminPengaduanController::class, 'kirimJawaban'])->name('pengaduan.jawaban');
    Route::post('/pengaduan/{kode}/kembalikan', [AdminPengaduanController::class, 'kembalikan'])->name('pengaduan.kembalikan');
    Route::post('/pengaduan/{kode}/kasus-berat', [AdminPengaduanController::class, 'kasusBerat'])->name('pengaduan.kasus-berat');

    // Master data unit. Kode unit dipakai sebagai parameter rute karena
    // kode itulah yang dikenal petugas, bukan id angka yang tidak pernah
    // tampil di layar.
    Route::get('/unit', [AdminUnitController::class, 'index'])->name('unit.index');
    Route::get('/unit/tambah', [AdminUnitController::class, 'create'])->name('unit.create');
    Route::post('/unit', [AdminUnitController::class, 'store'])->name('unit.store');
    Route::get('/unit/{unit}/ubah', [AdminUnitController::class, 'edit'])->name('unit.edit');
    Route::post('/unit/{unit}/perbarui', [AdminUnitController::class, 'update'])->name('unit.update');
    Route::post('/unit/{unit}/status', [AdminUnitController::class, 'status'])->name('unit.status');
});
