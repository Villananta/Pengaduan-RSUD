<?php

namespace App\Support;

use App\Models\Pengaduan;
use Illuminate\Http\Request;

/**
 * Aturan akses tiket dari sisi pelapor.
 *
 * Pelapor bisa membuka tiketnya lewat dua jalur: session setelah mengetik
 * kode tiket, atau cookie token 30 hari yang ditanam saat verifikasi
 * berhasil. Aturan itu dipakai halaman lacak, pengiriman pesan, dan unduhan
 * lampiran, jadi ditulis sekali di sini supaya ketiganya tidak bisa
 * berbeda diam-diam saat salah satu diubah.
 */
final class AksesTiketPelapor
{
    /** Kunci session yang menandai tiket sudah diverifikasi pelapor. */
    public const SESI = 'tiket_terverifikasi';

    /** Nama cookie token akses permanen. */
    public const COOKIE = 'pengaduan_token';

    /** Tandai tiket sebagai terverifikasi pada session pelapor. */
    public static function simpan(Request $request, string $kode): void
    {
        $request->session()->put(self::SESI, ['kode' => $kode]);
    }

    /** Hapus tanda verifikasi dari session. */
    public static function lupakan(Request $request): void
    {
        $request->session()->forget(self::SESI);
    }

    /** Kode tiket dari session saja, tanpa menyentuh cookie. */
    public static function kodeSesi(Request $request): ?string
    {
        $kode = $request->session()->get(self::SESI.'.kode');

        return is_string($kode) && $kode !== '' ? $kode : null;
    }

    /**
     * Kode tiket yang sedang terbuka di browser ini.
     *
     * Session diperiksa lebih dulu karena itu jejak verifikasi terbaru.
     * Kalau session kosong, cookie token dipakai dan session diisi ulang
     * supaya permintaan berikutnya tidak perlu memeriksa token lagi.
     */
    public static function kodeTerbuka(Request $request): ?string
    {
        $kode = self::kodeSesi($request);

        if ($kode !== null) {
            return $kode;
        }

        $pengaduan = self::dariToken($request);

        if ($pengaduan === null) {
            return null;
        }

        self::simpan($request, $pengaduan->kode_tiket);

        return $pengaduan->kode_tiket;
    }

    /**
     * Apakah pelapor berhak membuka tiket ini.
     *
     * Session dan cookie diperiksa terpisah, bukan lewat kodeTerbuka():
     * pelapor yang session-nya memegang tiket lain tetap boleh membuka
     * tiket yang tokennya masih tersimpan di browser, sama seperti sebelum
     * aturan ini disatukan.
     */
    public static function berhak(Request $request, Pengaduan $pengaduan): bool
    {
        if (self::kodeSesi($request) === $pengaduan->kode_tiket) {
            return true;
        }

        if (self::dariToken($request)?->kode_tiket !== $pengaduan->kode_tiket) {
            return false;
        }

        self::simpan($request, $pengaduan->kode_tiket);

        return true;
    }

    /** Tiket yang token aksesnya masih berlaku di cookie. */
    private static function dariToken(Request $request): ?Pengaduan
    {
        $token = $request->cookie(self::COOKIE);

        if (! is_string($token) || $token === '') {
            return null;
        }

        return Pengaduan::findByValidAksesToken($token);
    }
}
