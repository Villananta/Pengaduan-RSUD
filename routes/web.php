<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MonitorController as AdminMonitorController;
use App\Http\Controllers\Admin\PengaduanController as AdminPengaduanController;
use App\Http\Controllers\Admin\UnitController as AdminUnitController;
use App\Http\Controllers\PengaduanController;
use App\Http\Controllers\Unit\DashboardController as UnitDashboardController;
use App\Http\Controllers\Unit\DisposisiController as UnitDisposisiController;
use App\Http\Middleware\PastikanUnitAktif;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/buat-aduan');

Route::view('/maklumat-prosedur', 'maklumat.index')->name('maklumat.index');

// Batas percobaan agar kode tiket tidak bisa ditebak brute force dan
// endpoint publik tidak bisa dipakai untuk spam.
//
// Tiap rute memakai awalan pembatas sendiri. Bawaan Laravel menandai
// permintaan hanya dengan domain dan IP, jadi tanpa awalan semua rute
// berbagi satu kunci: ramai di satu tombol ikut mengunci tombol lain.
Route::get('/lacak-tiket', [PengaduanController::class, 'lacak'])->name('pengaduan.lacak');

Route::post('/lacak-tiket/verifikasi', [PengaduanController::class, 'verifikasi'])
    ->middleware('throttle:10,1,lacak-verifikasi')
    ->name('pengaduan.verifikasi');

Route::post('/lacak-tiket/{kode}/pesan', [PengaduanController::class, 'kirimPesan'])
    ->middleware('throttle:20,1,pesan-lacak')
    ->name('pengaduan.pesan');

Route::get('/buat-aduan', [PengaduanController::class, 'create'])->name('pengaduan.create');

Route::post('/buat-aduan', [PengaduanController::class, 'store'])
    ->middleware('throttle:5,10,buat-aduan')
    ->name('pengaduan.store');

Route::get('/buat-aduan/sukses', [PengaduanController::class, 'sukses'])->name('pengaduan.sukses');

// Konsol admin.
//
// Catatan keamanan: seluruh rute di bawah masih terbuka tanpa login dan
// baru akan ditutup setelah auth admin dipasang. Sambil menunggu, laju
// tiap endpoint tulisnya dibatasi throttle:60,1 supaya konsol yang terbuka
// tidak bisa dibanjiri permintaan. Begitu auth siap, grup ini cukup diberi
// middleware auth.admin tanpa mengubah nama rute yang dipakai template.
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/monitor', [AdminMonitorController::class, 'index'])->name('monitor.index');
    Route::get('/pengaduan', [AdminPengaduanController::class, 'index'])->name('pengaduan.index');

    // Formulir pencatatan aduan yang masuk lewat telepon, SMS, atau loket.
    //
    // Rute ini wajib didaftarkan sebelum /pengaduan/{kode} supaya kata
    // "buat" tidak tertangkap sebagai kode tiket yang tidak ada.
    Route::get('/pengaduan/buat', [AdminPengaduanController::class, 'create'])->name('pengaduan.create');
    Route::post('/pengaduan/buat', [AdminPengaduanController::class, 'store'])
        ->middleware('throttle:60,1,admin-buat-aduan')
        ->name('pengaduan.store');

    Route::get('/pengaduan/{kode}', [AdminPengaduanController::class, 'show'])->name('pengaduan.show');

    // Endpoint tulis paling rawan disalahgunakan karena konsol masih tanpa
    // login. Lajunya dibatasi sekarang supaya peredaman tetap ada walau
    // auth belum dipasang.
    //
    // Awalan pada tiap throttle memisahkan jatah antar tombol. Kalau
    // dibiarkan kosong, permintaan ke rute mana pun menambah hitungan yang
    // sama (domain|ip), sehingga satu tombol yang dipakai berulang bisa
    // menutup tombol lain yang belum tersentuh.
    Route::post('/pengaduan/{kode}/balas', [AdminPengaduanController::class, 'balas'])
        ->middleware('throttle:60,1,admin-balas')
        ->name('pengaduan.balas');
    Route::post('/pengaduan/{kode}/koordinasi', [AdminPengaduanController::class, 'koordinasi'])
        ->middleware('throttle:60,1,admin-koordinasi')
        ->name('pengaduan.koordinasi');
    Route::post('/pengaduan/{kode}/tahap', [AdminPengaduanController::class, 'pindahTahap'])
        ->middleware('throttle:60,1,admin-tahap')
        ->name('pengaduan.tahap');
    Route::post('/pengaduan/{kode}/disposisi', [AdminPengaduanController::class, 'disposisi'])
        ->middleware('throttle:60,1,admin-disposisi')
        ->name('pengaduan.disposisi');
    Route::post('/pengaduan/{kode}/tangani-langsung', [AdminPengaduanController::class, 'tanganiLangsung'])
        ->middleware('throttle:60,1,admin-tangani-langsung')
        ->name('pengaduan.tangani-langsung');
    Route::post('/pengaduan/{kode}/kasus-berat', [AdminPengaduanController::class, 'kasusBerat'])
        ->middleware('throttle:60,1,admin-kasus-berat')
        ->name('pengaduan.kasus-berat');

    // Master data unit. Kode unit dipakai sebagai parameter rute karena
    // kode itulah yang dikenal petugas, bukan id angka yang tidak pernah
    // tampil di layar.
    Route::get('/unit', [AdminUnitController::class, 'index'])->name('unit.index');
    Route::get('/unit/tambah', [AdminUnitController::class, 'create'])->name('unit.create');
    Route::post('/unit', [AdminUnitController::class, 'store'])
        ->middleware('throttle:60,1,admin-unit-store')
        ->name('unit.store');
    Route::get('/unit/{unit}/ubah', [AdminUnitController::class, 'edit'])->name('unit.edit');
    Route::post('/unit/{unit}/perbarui', [AdminUnitController::class, 'update'])
        ->middleware('throttle:60,1,admin-unit-perbarui')
        ->name('unit.update');
    Route::post('/unit/{unit}/status', [AdminUnitController::class, 'status'])
        ->middleware('throttle:60,1,admin-unit-status')
        ->name('unit.status');
});

// Dashboard unit layanan (pintu masuk /unit).
//
// Rute masih terbuka tanpa login seperti konsol humas: satu halaman /unit
// menampilkan daftar unit, lalu kode unit di URL menentukan dashboard mana
// yang dibuka. Selama auth belum dipasang, rute ber-{unit} sudah dijaga
// middleware PastikanUnitAktif supaya unit non-aktif tidak bisa dibuka lewat
// URL langsung, dan endpoint tulis jawaban dibatasi throttle:20,1. Begitu
// auth unit siap, grup ini cukup diberi middleware auth tanpa mengubah nama
// rute yang dipakai template.
Route::prefix('unit')->name('unit.')->group(function () {
    Route::get('/', [UnitDashboardController::class, 'pilih'])->name('pilih');

    // Unit non-aktif hanya hilang dari daftar pemilih, jadi rute ber-{unit}
    // dijaga terpisah supaya URL langsungnya ikut ditolak.
    Route::middleware(PastikanUnitAktif::class)->group(function () {
        Route::get('/{unit}/dashboard', [UnitDashboardController::class, 'index'])->name('dashboard');

        // Halaman disposisi sisi unit. Sengaja terpisah dari rute admin.pengaduan
        // karena pemiliknya berbeda: di sini kode unit mengunci isi halaman, jadi
        // satu unit tidak bisa membuka daftar atau detail tiket milik unit lain.
        Route::get('/{unit}/disposisi', [UnitDisposisiController::class, 'index'])->name('disposisi.index');
        Route::get('/{unit}/arsip', [UnitDisposisiController::class, 'arsip'])->name('disposisi.arsip');
        Route::get('/{unit}/pengaduan/{kode}', [UnitDisposisiController::class, 'show'])->name('disposisi.show');

        // Jawaban unit membalik sinyal disposisi yang dibaca konsol humas, jadi
        // lajunya dibatasi meski konsol masih tanpa login.
        Route::post('/{unit}/pengaduan/{kode}/jawaban', [UnitDisposisiController::class, 'kirim'])
            ->middleware('throttle:20,1,unit-jawaban')
            ->name('disposisi.kirim');
    });
});
