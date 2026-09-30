<?php

namespace App\Support;

use App\Enums\ZonaSla;
use Carbon\CarbonInterface;

/**
 * Perhitungan Standar Pelayanan Minimal dalam hari kerja.
 *
 * Semua angka SLA pada halaman publik berasal dari sini, bukan dari
 * teks hardcoded, sehingga batas waktu dan tampilan selalu konsisten.
 */
final class Sla
{
    /** Batas akhir penanganan pengaduan dalam hari kerja. */
    public static function hariKerja(): int
    {
        return (int) config('pengaduan.sla.hari_kerja', 12);
    }

    /** Batas respons awal dalam jam. */
    public static function responsAwalJam(): int
    {
        return (int) config('pengaduan.sla.respons_awal_jam', 24);
    }

    /** Batas investigasi internal unit dalam hari kerja. */
    public static function hariInvestigasi(): int
    {
        return (int) config('pengaduan.sla.investigasi_hari_kerja', 5);
    }

    /**
     * Tambahan hari kerja untuk tiket yang ditandai kasus berat.
     *
     * Penandaan kasus berat tidak menambah batas investigasi unit karena
     * yang perlu dipercepat tetap pengumpulan bukti, bukan jawaban akhir.
     */
    public static function tambahanKasusBerat(): int
    {
        return (int) config('pengaduan.sla.kasus_berat_hari_kerja', 8);
    }

    /** Total hari kerja penyelesaian, sudah termasuk tambahan kasus berat. */
    public static function totalHariKerja(bool $kasusBerat = false): int
    {
        return self::hariKerja() + ($kasusBerat ? self::tambahanKasusBerat() : 0);
    }

    /**
     * Jumlah hari kerja yang sudah berjalan sejak sebuah tanggal.
     *
     * Nilai selalu positif, dipakai untuk melihat sejauh mana satu
     * pengaduan sudah melewati batas investigasi unit.
     */
    public static function hariKerjaLewat(CarbonInterface $mulai, ?CarbonInterface $sekarang = null): int
    {
        return (int) abs($mulai->diffInWeekdays($sekarang ?? now(), true));
    }

    /**
     * True bila investigasi unit sudah melewati batas hari kerja.
     *
     * Dipakai untuk banner peringatan konsol admin, sehingga ambang yang
     * dihitung sama dengan angka eskalasi otomatis ke Wadir.
     */
    public static function lewatInvestigasi(CarbonInterface $mulai, ?CarbonInterface $sekarang = null): bool
    {
        return self::hariKerjaLewat($mulai, $sekarang) >= self::hariInvestigasi();
    }

    /**
     * Tanggal jatuh tempo penyelesaian pengaduan.
     *
     * Tiket kasus berat memakai total hari kerja yang lebih panjang. Tanpa
     * parameter ini setiap pemanggilan harus ingat ikut meneruskan penandanya,
     * dan target tiket yang sama bisa berbeda antara daftar dan detail.
     */
    public static function target(CarbonInterface $mulai, bool $kasusBerat = false): CarbonInterface
    {
        return $mulai->copy()->addWeekdays(self::totalHariKerja($kasusBerat));
    }

    /** Jumlah hari kerja yang tersisa dari $sekarang menuju target. */
    public static function sisaHariKerja(
        CarbonInterface $mulai,
        ?CarbonInterface $sekarang = null,
        bool $kasusBerat = false,
    ): int {
        $target = self::target($mulai, $kasusBerat);

        return max(0, ($sekarang ?? now())->diffInWeekdays($target, false));
    }

    /**
     * Zona SLA sebuah pengaduan.
     *
     * Pengaduan yang sudah selesai dinilai dari waktu penyelesaiannya,
     * sedangkan pengaduan yang masih berjalan dinilai dari posisi hari ini.
     */
    public static function zona(
        CarbonInterface $mulai,
        ?CarbonInterface $selesai = null,
        bool $kasusBerat = false,
    ): ZonaSla {
        $target = self::target($mulai, $kasusBerat);
        $peringatan = (int) config('pengaduan.sla.peringatan_hari_kerja', 2);

        if ($selesai !== null) {
            return $selesai->lessThanOrEqualTo($target) ? ZonaSla::TepatWaktu : ZonaSla::Terlambat;
        }

        $sisa = self::sisaHariKerja($mulai, null, $kasusBerat);

        if ($sisa === 0) {
            return ZonaSla::Terlambat;
        }

        return $sisa <= $peringatan ? ZonaSla::Mendek : ZonaSla::TepatWaktu;
    }

    /** Persentase progres tahap terhadap total hari kerja SLA. */
    public static function persen(int $tahap, bool $kasusBerat = false): int
    {
        $prosedur = config('pengaduan.prosedur', []);

        if ($tahap < 0 || ! array_key_exists($tahap, $prosedur)) {
            return 0;
        }

        $batas = (int) ($prosedur[$tahap]['batas'] ?? 0);
        $total = self::totalHariKerja($kasusBerat);

        if ($batas >= $total) {
            return 100;
        }

        return (int) round($batas / $total * 100);
    }
}
