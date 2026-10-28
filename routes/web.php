<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DisposisiController as AdminDisposisiController;
use App\Http\Controllers\Admin\DraftController as AdminDraftController;
use App\Http\Controllers\Admin\ExportController as AdminExportController;
use App\Http\Controllers\Admin\KomunikasiController as AdminKomunikasiController;
use App\Http\Controllers\Admin\MonitorController as AdminMonitorController;
use App\Http\Controllers\Admin\NotifikasiController as AdminNotifikasiController;
use App\Http\Controllers\Admin\PengaduanController as AdminPengaduanController;
use App\Http\Controllers\Admin\RiwayatController as AdminRiwayatController;
use App\Http\Controllers\Admin\TahapController as AdminTahapController;
use App\Http\Controllers\Admin\UnitController as AdminUnitController;
use App\Http\Controllers\AktivasiUndanganController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\LampiranController;
use App\Http\Controllers\PengaduanController;
use App\Http\Controllers\SesiController;
use App\Http\Controllers\Unit\DashboardController as UnitDashboardController;
use App\Http\Controllers\Unit\DisposisiController as UnitDisposisiController;
use App\Http\Controllers\Unit\NotificationController as UnitNotificationController;
use App\Http\Controllers\Unit\RealTimeNotificationController as UnitRealTimeNotificationController;
use App\Http\Controllers\Unit\UnitExportController;
use App\Http\Middleware\PastikanAdmin;
use App\Http\Middleware\PastikanPic;
use App\Http\Middleware\PastikanUnitAktif;
use App\Http\Middleware\PastikanUnitSendiri;
use App\Http\Middleware\ThrottleKeys;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/buat-aduan');

Route::view('/maklumat-prosedur', 'maklumat.index')->name('maklumat.index');

// Lampiran terproteksi
Route::get('/lampiran/pengaduan/{kode}/{file}', [LampiranController::class, 'pengaduan'])
    ->name('lampiran.pengaduan');
Route::get('/lampiran/pesan/{pesan}/{file}', [LampiranController::class, 'pesan'])
    ->name('lampiran.pesan');

// Health check endpoints (untuk load balancer, Kubernetes, monitoring)
// Tidak memerlukan auth, rate limit longgar (hanya monitoring internal)
Route::get('/health', [HealthCheckController::class, 'index'])->name('health');
Route::get('/health/live', [HealthCheckController::class, 'live'])->name('health.live');
Route::get('/health/ready', [HealthCheckController::class, 'ready'])->name('health.ready');

// Batas percobaan agar kode tiket tidak bisa ditebak brute force dan
// endpoint publik tidak bisa dipakai untuk spam.
//
// Tiap rute memakai awalan pembatas sendiri. Bawaan Laravel menandai
// permintaan hanya dengan domain dan IP, jadi tanpa awalan semua rute
// berbagi satu kunci: ramai di satu tombol ikut mengunci tombol lain.
Route::get('/lacak-tiket', [PengaduanController::class, 'lacak'])->name('pengaduan.lacak');

Route::post('/lacak-tiket/verifikasi', [PengaduanController::class, 'verifikasi'])
    ->middleware('throttle:10,1,'.ThrottleKeys::LACAK_VERIFIKASI)
    ->name('pengaduan.verifikasi');

Route::post('/lacak-tiket/{kode}/pesan', [PengaduanController::class, 'kirimPesan'])
    ->middleware('throttle:20,1,'.ThrottleKeys::PESAN_LACAK)
    ->name('pengaduan.pesan');

Route::get('/buat-aduan', [PengaduanController::class, 'create'])->name('pengaduan.create');

Route::post('/buat-aduan', [PengaduanController::class, 'store'])
    ->middleware('throttle:5,10,'.ThrottleKeys::BUAT_ADUAN)
    ->name('pengaduan.store');

Route::get('/buat-aduan/sukses', [PengaduanController::class, 'sukses'])->name('pengaduan.sukses');

// Verifikasi email pelapor (OTP)
Route::get('/verifikasi-email/{kode}', [PengaduanController::class, 'verifikasiEmail'])
    ->name('pengaduan.verifikasi-email');
Route::post('/verifikasi-email/{kode}', [PengaduanController::class, 'verifikasiEmail'])
    ->middleware('throttle:10,1,'.ThrottleKeys::VERIFIKASI_EMAIL)
    ->name('pengaduan.verifikasi-email.post');
Route::post('/verifikasi-email/{kode}/kirim-ulang', [PengaduanController::class, 'kirimUlangOtp'])
    ->middleware('throttle:3,1,'.ThrottleKeys::KIRIM_ULANG_OTP)
    ->name('pengaduan.verifikasi-email.resend');

// Aktivasi akun PIC dari tautan undangan email. Token yang dibawa URL
// adalah bukti kepemilikan email PIC, jadi halaman ini tidak meminta
// login apa pun seperti halaman pelapor.
Route::get('/aktivasi-undangan/{token}', [AktivasiUndanganController::class, 'tampil'])
    ->name('aktivasi.undangan');
Route::post('/aktivasi-undangan/{token}', [AktivasiUndanganController::class, 'simpan'])
    ->middleware('throttle:10,1,'.ThrottleKeys::AKTIVASI_UNDANGAN)
    ->name('aktivasi.undangan.simpan');

// Sesi: masuk dan keluar.
//
// Satu halaman masuk dipakai bersama dua peran: admin humas diarahkan ke
// konsol admin, PIC unit ke konsol unitnya. Tidak ada pendaftaran mandiri —
// akun PIC lahir dari undangan aktivasi, akun admin dari pengelolaan basis
// data. Percobaan masuk dibatasi 10/menit agar sandi tidak bisa ditebak.
//
// Alamatnya dibaca dari config dan sengaja tidak ditautkan di situs
// pelapor, jadi orang awam tidak kebetulan menemukan halaman ini. trim()
// menetralkan garis miring tulisan di .env, dan alamat bawaan dipakai
// kembali kalau isinya kosong supaya rute tidak pernah jatuh ke root.
$ruteMasuk = '/'.(trim((string) config('pengaduan.rute_masuk'), '/') ?: 'petugas/masuk');

Route::get($ruteMasuk, [SesiController::class, 'tampil'])->name('masuk');
Route::post($ruteMasuk, [SesiController::class, 'coba'])
    ->middleware('throttle:10,1,'.ThrottleKeys::MASUK)
    ->name('masuk.coba');
Route::post('/keluar', [SesiController::class, 'keluar'])->name('keluar');

// Konsol admin.
//
// Seluruh rute di bawah kini tertutup oleh auth: tamu diarahkan ke halaman
// masuk, dan PIC unit yang menembus URL diblokir PastikanAdmin dengan 403.
// Pembatasan laju pada endpoint tulis tetap dipertahankan sebagai lapisan
// kedua bagi sesi admin yang mungkin disalahgunakan.
Route::prefix('admin')->name('admin.')
    ->middleware(['auth', PastikanAdmin::class])
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/riwayat', [AdminRiwayatController::class, 'index'])->name('riwayat.index');
        Route::get('/monitor', [AdminMonitorController::class, 'index'])->name('monitor.index');
        Route::get('/pengaduan', [AdminPengaduanController::class, 'index'])->name('pengaduan.index');

        // Notifikasi admin
        Route::get('/notifikasi/daftar', [AdminNotifikasiController::class, 'daftar'])
            ->middleware('throttle:120,1,'.ThrottleKeys::UNIT_NOTIFIKASI_REALTIME)
            ->name('notifikasi.daftar');
        Route::post('/notifikasi/baca', [AdminNotifikasiController::class, 'tandaiDibaca'])
            ->middleware('throttle:60,1,'.ThrottleKeys::UNIT_NOTIFIKASI_BACA)
            ->name('notifikasi.baca');
        Route::post('/notifikasi/baca-semua', [AdminNotifikasiController::class, 'tandaiSemuaDibaca'])
            ->middleware('throttle:30,1,'.ThrottleKeys::UNIT_NOTIFIKASI_BACA_SEMUA)
            ->name('notifikasi.baca-semua');

        // Formulir pencatatan aduan yang masuk lewat telepon, SMS, atau loket.
        //
        // Rute ini wajib didaftarkan sebelum /pengaduan/{kode} supaya kata
        // "buat" tidak tertangkap sebagai kode tiket yang tidak ada.
        Route::get('/pengaduan/buat', [AdminPengaduanController::class, 'create'])->name('pengaduan.create');
        Route::post('/pengaduan/buat', [AdminPengaduanController::class, 'store'])
            ->middleware('throttle:60,1,'.ThrottleKeys::ADMIN_BUAT_ADUAN)
            ->name('pengaduan.store');

        Route::get('/pengaduan/{kode}', [AdminPengaduanController::class, 'show'])->name('pengaduan.show');

        // Endpoint tulis tetap dibatasi lajunya sebagai lapisan kedua setelah
        // auth, supaya sesi admin yang disalahgunakan tidak bisa membanjiri
        // sistem dalam sekejap.
        //
        // Awalan pada tiap throttle memisahkan jatah antar tombol. Kalau
        // dibiarkan kosong, permintaan ke rute mana pun menambah hitungan yang
        // sama (domain|ip), sehingga satu tombol yang dipakai berulang bisa
        // menutup tombol lain yang belum tersentuh.
        Route::post('/pengaduan/{kode}/balas', [AdminKomunikasiController::class, 'balas'])
            ->middleware('throttle:60,1,'.ThrottleKeys::ADMIN_BALAS)
            ->name('pengaduan.balas');
        Route::post('/pengaduan/{kode}/koordinasi', [AdminKomunikasiController::class, 'koordinasi'])
            ->middleware('throttle:60,1,'.ThrottleKeys::ADMIN_KOORDINASI)
            ->name('pengaduan.koordinasi');
        Route::post('/pengaduan/{kode}/tahap', [AdminTahapController::class, 'update'])
            ->middleware('throttle:60,1,'.ThrottleKeys::ADMIN_TAHAP)
            ->name('pengaduan.tahap');
        Route::post('/pengaduan/{kode}/disposisi', [AdminDisposisiController::class, 'store'])
            ->middleware('throttle:60,1,'.ThrottleKeys::ADMIN_DISPOSISI)
            ->name('pengaduan.disposisi');
        Route::post('/pengaduan/{kode}/tangani-langsung', [AdminDisposisiController::class, 'tanganiLangsung'])
            ->middleware('throttle:60,1,'.ThrottleKeys::ADMIN_TANGANI_LANGSUNG)
            ->name('pengaduan.tangani-langsung');
        Route::post('/pengaduan/{kode}/kasus-berat', [AdminKomunikasiController::class, 'kasusBerat'])
            ->middleware('throttle:60,1,'.ThrottleKeys::ADMIN_KASUS_BERAT)
            ->name('pengaduan.kasus-berat');
        Route::post('/pengaduan/{kode}/keparahan', [AdminKomunikasiController::class, 'keparahan'])
            ->middleware('throttle:60,1,'.ThrottleKeys::ADMIN_KEPARAHAN)
            ->name('pengaduan.keparahan');
        Route::post('/pengaduan/{kode}/kategori', [AdminKomunikasiController::class, 'kategori'])
            ->middleware('throttle:60,1,'.ThrottleKeys::ADMIN_KATEGORI)
            ->name('pengaduan.kategori');

        // Nudge manual ke unit
        Route::post('/pengaduan/{kode}/nudge', [AdminKomunikasiController::class, 'nudge'])
            ->middleware('throttle:30,1,'.ThrottleKeys::ADMIN_NUDGE)
            ->name('pengaduan.nudge');

        // Aksi massal (bulk action) untuk daftar pengaduan
        Route::post('/pengaduan/bulk-action', [AdminPengaduanController::class, 'bulkAction'])
            ->middleware('throttle:20,1,'.ThrottleKeys::ADMIN_BULK_ACTION)
            ->name('pengaduan.bulk-action');

        // Manajemen draf jawaban admin
        Route::post('/pengaduan/{kode}/draf/simpan', [AdminDraftController::class, 'simpan'])
            ->middleware('throttle:60,1,'.ThrottleKeys::ADMIN_DRAF_SIMPAN)
            ->name('pengaduan.draf.simpan');
        Route::get('/pengaduan/{kode}/draf/ambil', [AdminDraftController::class, 'ambil'])
            ->middleware('throttle:60,1,'.ThrottleKeys::ADMIN_DRAF_AMBIL)
            ->name('pengaduan.draf.ambil');
        Route::delete('/pengaduan/{kode}/draf/hapus', [AdminDraftController::class, 'hapus'])
            ->middleware('throttle:60,1,'.ThrottleKeys::ADMIN_DRAF_HAPUS)
            ->name('pengaduan.draf.hapus');

        // Ekspor data
        Route::get('/pengaduan/ekspor/excel', [AdminExportController::class, 'pengaduanExcel'])
            ->middleware('throttle:30,1,'.ThrottleKeys::ADMIN_EKSPOR_EXCEL)
            ->name('pengaduan.ekspor.excel');
        Route::get('/pengaduan/{kode}/ekspor/disposisi-pdf', [AdminExportController::class, 'lembarDisposisiPdf'])
            ->middleware('throttle:30,1,'.ThrottleKeys::ADMIN_EKSPOR_PDF)
            ->name('pengaduan.ekspor.disposisi-pdf');
        Route::get('/pengaduan/{kode}/ekspor/disposisi-preview', [AdminExportController::class, 'lembarDisposisiPreview'])
            ->middleware('throttle:30,1,'.ThrottleKeys::ADMIN_EKSPOR_PREVIEW)
            ->name('pengaduan.ekspor.disposisi-preview');

        // Master data unit. Kode unit dipakai sebagai parameter rute karena
        // kode itulah yang dikenal petugas, bukan id angka yang tidak pernah
        // tampil di layar.
        Route::get('/unit', [AdminUnitController::class, 'index'])->name('unit.index');
        Route::get('/unit/tambah', [AdminUnitController::class, 'create'])->name('unit.create');
        Route::post('/unit', [AdminUnitController::class, 'store'])
            ->middleware('throttle:60,1,'.ThrottleKeys::ADMIN_UNIT_STORE)
            ->name('unit.store');
        Route::get('/unit/{unit}/ubah', [AdminUnitController::class, 'edit'])->name('unit.edit');
        Route::post('/unit/{unit}/perbarui', [AdminUnitController::class, 'update'])
            ->middleware('throttle:60,1,'.ThrottleKeys::ADMIN_UNIT_PERBARUI)
            ->name('unit.update');
        Route::post('/unit/{unit}/status', [AdminUnitController::class, 'status'])
            ->middleware('throttle:60,1,'.ThrottleKeys::ADMIN_UNIT_STATUS)
            ->name('unit.status');

        // Undangan aktivasi PIC dikirim massal ke semua unit yang akunnya
        // belum aktif. Lajunya dikunci terpisah karena satu tombol bisa
        // mengirim belasan email sekaligus.
        Route::post('/unit/undangan', [AdminUnitController::class, 'kirimUndangan'])
            ->middleware('throttle:20,1,'.ThrottleKeys::ADMIN_UNIT_UNDANGAN)
            ->name('unit.undangan');
    });

// Dashboard unit layanan (pintu masuk /unit).
//
// Grup ini tertutup oleh auth seperti konsol admin: tamu diarahkan ke
// halaman masuk, dan admin humas yang membuka URL unit diblokir PastikanPic.
//
// Satu akun PIC mengurus satu unit, jadi /unit bukan lagi halaman pemilih:
// petugas yang sudah ditautkan langsung dilempar ke dashboard unitnya, yang
// belum ditautkan hanya mendapat halaman penjelasan. Penguncian unit pun
// kini berlapis: PastikanUnitSendiri menolak unit milik PIC lain dengan
// 403, PastikanUnitAktif menolak unit non-aktif dengan 404, dan endpoint
// tulis jawaban tetap dibatasi throttle:20,1.
Route::prefix('unit')->name('unit.')
    ->middleware(['auth', PastikanPic::class])
    ->group(function () {
        // Pintu masuk konsol unit: mengantar petugas ke unit miliknya sendiri.
        Route::get('/', [UnitDashboardController::class, 'beranda'])->name('beranda');

        // Notifikasi untuk PIC tanpa unit (akun belum ditautkan)
        Route::post('/notifikasi/baca', [UnitNotificationController::class, 'tandaiDibaca'])
            ->defaults('unit', null)
            ->middleware('throttle:60,1,'.ThrottleKeys::UNIT_NOTIFIKASI_BACA)
            ->name('notifikasi.baca.user');
        Route::post('/notifikasi/baca-semua', [UnitNotificationController::class, 'tandaiSemuaDibaca'])
            ->defaults('unit', null)
            ->middleware('throttle:30,1,'.ThrottleKeys::UNIT_NOTIFIKASI_BACA_SEMUA)
            ->name('notifikasi.baca-semua.user');

        Route::middleware([PastikanUnitSendiri::class, PastikanUnitAktif::class])->group(function () {
            Route::get('/{unit}/dashboard', [UnitDashboardController::class, 'index'])->name('dashboard');

            // Halaman disposisi sisi unit. Sengaja terpisah dari rute admin.pengaduan
            // karena pemiliknya berbeda: di sini kode unit mengunci isi halaman, jadi
            // satu unit tidak bisa membuka daftar atau detail tiket milik unit lain.
            Route::get('/{unit}/disposisi', [UnitDisposisiController::class, 'index'])->name('disposisi.index');
            Route::get('/{unit}/arsip', [UnitDisposisiController::class, 'arsip'])->name('disposisi.arsip');
            Route::get('/{unit}/pengaduan/{kode}', [UnitDisposisiController::class, 'show'])->name('disposisi.show');

            // Jawaban unit membalik sinyal disposisi yang dibaca konsol humas, jadi
            // lajunya dibatasi sebagai lapisan kedua setelah auth.
            Route::post('/{unit}/pengaduan/{kode}/jawaban', [UnitDisposisiController::class, 'kirim'])
                ->middleware('throttle:20,1,'.ThrottleKeys::UNIT_JAWABAN)
                ->name('disposisi.kirim');

            // Ekspor lembar disposisi PDF untuk unit
            Route::get('/{unit}/pengaduan/{kode}/ekspor/disposisi-pdf', [UnitDisposisiController::class, 'eksporDisposisiPdf'])
                ->middleware('throttle:30,1,'.ThrottleKeys::UNIT_EKSPOR_PDF)
                ->name('disposisi.ekspor.disposisi-pdf');

            // Export daftar disposisi & arsip unit (Excel/PDF)
            Route::get('/{unit}/disposisi/export/excel', [UnitExportController::class, 'disposisiExcel'])
                ->middleware('throttle:30,1,'.ThrottleKeys::UNIT_EKSPOR_EXCEL)
                ->name('disposisi.export.excel');
            Route::get('/{unit}/arsip/export/excel', [UnitExportController::class, 'arsipExcel'])
                ->middleware('throttle:30,1,'.ThrottleKeys::UNIT_EKSPOR_EXCEL)
                ->name('arsip.export.excel');
            Route::get('/{unit}/disposisi/export/pdf', [UnitExportController::class, 'disposisiPdf'])
                ->middleware('throttle:30,1,'.ThrottleKeys::UNIT_EKSPOR_PDF)
                ->name('disposisi.export.pdf');
            Route::get('/{unit}/arsip/export/pdf', [UnitExportController::class, 'arsipPdf'])
                ->middleware('throttle:30,1,'.ThrottleKeys::UNIT_EKSPOR_PDF)
                ->name('arsip.export.pdf');

            // Notifikasi unit
            Route::post('/{unit}/notifikasi/baca', [UnitNotificationController::class, 'tandaiDibaca'])
                ->middleware('throttle:60,1,'.ThrottleKeys::UNIT_NOTIFIKASI_BACA)
                ->name('notifikasi.tandai-dibaca');
            Route::post('/{unit}/notifikasi/baca-semua', [UnitNotificationController::class, 'tandaiSemuaDibaca'])
                ->middleware('throttle:30,1,'.ThrottleKeys::UNIT_NOTIFIKASI_BACA_SEMUA)
                ->name('notifikasi.tandai-semua-dibaca');

            // Real-time notifikasi (polling)
            Route::get('/{unit}/notifikasi/realtime', [UnitRealTimeNotificationController::class, 'index'])
                ->middleware('throttle:120,1,'.ThrottleKeys::UNIT_NOTIFIKASI_REALTIME)
                ->name('notifikasi.realtime');
        });
    });
