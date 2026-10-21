<?php

namespace App\Http\Middleware;

/**
 * Kunci throttle terpusat agar nama awalan tidak tercecer di routes/web.php.
 *
 * Setiap endpoint tulis punya prefix sendiri supaya jatah 60/menit (admin)
 * atau 20/menit (unit) tidak saling memakan. Tanpa ini, Laravel memakai
 * domain|IP sebagai kunci tunggal sehingga satu tombol yang dipakai berulang
 * bisa mengunci tombol lain yang belum tersentuh.
 */
final class ThrottleKeys
{
    // Admin: tulis pengaduan
    public const ADMIN_BUAT_ADUAN = 'admin-buat-aduan';

    public const ADMIN_BALAS = 'admin-balas';

    public const ADMIN_KOORDINASI = 'admin-koordinasi';

    public const ADMIN_TAHAP = 'admin-tahap';

    public const ADMIN_DISPOSISI = 'admin-disposisi';

    public const ADMIN_TANGANI_LANGSUNG = 'admin-tangani-langsung';

    public const ADMIN_KASUS_BERAT = 'admin-kasus-berat';

    public const ADMIN_NUDGE = 'admin-nudge';

    public const ADMIN_BULK_ACTION = 'admin-bulk-action';

    // Admin: draf jawaban
    public const ADMIN_DRAF_SIMPAN = 'admin-draf-simpan';

    public const ADMIN_DRAF_AMBIL = 'admin-draf-ambil';

    public const ADMIN_DRAF_HAPUS = 'admin-draf-hapus';

    // Admin: ekspor
    public const ADMIN_EKSPOR_EXCEL = 'admin-ekspor-excel';

    public const ADMIN_EKSPOR_PDF = 'admin-ekspor-pdf';

    public const ADMIN_EKSPOR_PREVIEW = 'admin-ekspor-preview';

    // Admin: master unit
    public const ADMIN_UNIT_STORE = 'admin-unit-store';

    public const ADMIN_UNIT_PERBARUI = 'admin-unit-perbarui';

    public const ADMIN_UNIT_STATUS = 'admin-unit-status';

    public const ADMIN_UNIT_UNDANGAN = 'admin-unit-undangan';

    // Publik
    public const MASUK = 'sesi-masuk';

    public const BUAT_ADUAN = 'buat-aduan';

    public const LACAK_VERIFIKASI = 'lacak-verifikasi';

    public const PESAN_LACAK = 'pesan-lacak';

    // Publik: verifikasi email
    public const VERIFIKASI_EMAIL = 'verifikasi-email';

    public const KIRIM_ULANG_OTP = 'kirim-ulang-otp';

    // Publik: aktivasi undangan PIC
    public const AKTIVASI_UNDANGAN = 'aktivasi-undangan';

    // Unit
    public const UNIT_JAWABAN = 'unit-jawaban';

    // Unit: notifikasi
    public const UNIT_NOTIFIKASI_BACA = 'unit-notifikasi-baca';

    public const UNIT_NOTIFIKASI_BACA_SEMUA = 'unit-notifikasi-baca-semua';

    public const UNIT_NOTIFIKASI_DAFTAR = 'unit-notifikasi-daftar';

    // Unit: real-time notifikasi (polling)
    public const UNIT_NOTIFIKASI_REALTIME = 'unit-notifikasi-realtime';

    public const UNIT_NOTIFIKASI_REALTIME_BACA = 'unit-notifikasi-realtime-baca';

    public const UNIT_NOTIFIKASI_REALTIME_BACA_SEMUA = 'unit-notifikasi-realtime-baca-semua';

    // Unit: ekspor
    public const UNIT_EKSPOR_EXCEL = 'unit-export-excel';

    public const UNIT_EKSPOR_PDF = 'unit-export-pdf';
}
