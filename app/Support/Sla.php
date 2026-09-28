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

    /** Tanggal jatuh tempo penyelesaian pengaduan. */
    public static function target(CarbonInterface $mulai): CarbonInterface
    {
        return $mulai->copy()->addWeekdays(self::hariKerja());
    }

    /** Jumlah hari kerja yang tersisa dari $sekarang menuju target. */
    public static function sisaHariKerja(CarbonInterface $mulai, ?CarbonInterface $sekarang = null): int
    {
        $target = self::target($mulai);

        return max(0, ($sekarang ?? now())->diffInWeekdays($target, false));
    }

    /**
     * Zona SLA sebuah pengaduan.
     *
     * Pengaduan yang sudah selesai dinilai dari waktu penyelesaiannya,
     * sedangkan pengaduan yang masih berjalan dinilai dari posisi hari ini.
     */
    public static function zona(CarbonInterface $mulai, ?CarbonInterface $selesai = null): ZonaSla
    {
        $target = self::target($mulai);
        $peringatan = (int) config('pengaduan.sla.peringatan_hari_kerja', 2);

        if ($selesai !== null) {
            return $selesai->lessThanOrEqualTo($target) ? ZonaSla::TepatWaktu : ZonaSla::Terlambat;
        }

        $sisa = self::sisaHariKerja($mulai);

        if ($sisa === 0) {
            return ZonaSla::Terlambat;
        }

        return $sisa <= $peringatan ? ZonaSla::Mendek : ZonaSla::TepatWaktu;
    }

    /** Persentase progres tahap terhadap total hari kerja SLA. */
    public static function persen(int $tahap): int
    {
        $prosedur = config('pengaduan.prosedur', []);

        if ($tahap < 0 || ! array_key_exists($tahap, $prosedur)) {
            return 0;
        }

        $batas = (int) ($prosedur[$tahap]['batas'] ?? 0);
        $hariKerja = self::hariKerja();

        if ($batas >= $hariKerja) {
            return 100;
        }

        return (int) round($batas / $hariKerja * 100);
    }
}
