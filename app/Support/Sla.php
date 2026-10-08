<?php

namespace App\Support;

use App\Enums\ZonaSla;
use Carbon\CarbonInterface;

/**
 * Perhitungan Standar Pelayanan Minimal dalam hari kerja.
 *
 * Semua angka SLA pada halaman publik berasal dari sini, bukan dari
 * teks hardcoded, sehingga batas waktu dan tampilan selalu konsisten.
 *
 * Konvensinya inklusif: hari kerja tiket masuk adalah hari ke-1. Jadi
 * standar 12 hari kerja berakhir pada hari kerja ke-12, bukan pada
 * hari ke-13. Semua angka turunan memakai Sla::hariKe() supaya nomor
 * hari, sisa hari, dan tanggal target tidak pernah berbeda satu hari
 * antar halaman.
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
     * Nomor hari kerja yang sedang berjalan, dihitung inklusif.
     *
     * Tiket yang baru masuk pada hari kerja ini sudah berstatus hari ke-1,
     * belum hari ke-0. Angka inilah yang dipakai semua tampilan "Hari ke-N",
     * dan sisa hari kerja diturunkan dari angka yang sama supaya
     * "hari ke-N" ditambah "sisa M hari kerja" selalu sama dengan standar.
     */
    public static function hariKe(CarbonInterface $mulai, ?CarbonInterface $sekarang = null): int
    {
        return self::hariKerjaLewat($mulai, $sekarang) + 1;
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
     *
     * Karena hari masuk dihitung sebagai hari ke-1, tanggal target jatuh di
     * hari kerja ke-(total), yaitu total dikurangi satu dari nomor minggu
     * kerja yang ditambahkan. Menambah sebanyak total akan memberi satu hari
     * kerja lebih banyak dari standar yang dijanjikan.
     */
    public static function target(CarbonInterface $mulai, bool $kasusBerat = false): CarbonInterface
    {
        // addWeekdays tidak senapas dengan diffInWeekdays untuk tanggal mulai
        // akhir pekan: dari Sabtu, addWeekdays sudah menghitung Senin berikutnya
        // sebagai minggu kerja pertama, padahal diffInWeekdays masih mencatat nol.
        // Karena semua tampilan "Hari ke-N" memakai diffInWeekdays, target ikut
        // dihitung dari hari kerja pertamanya supaya keduanya tidak berselisih
        // satu hari kerja untuk tiket yang masuk Sabtu atau Minggu.
        $awal = $mulai->copy();

        if ($awal->isWeekend()) {
            $awal->nextWeekday();
        }

        return $awal->addWeekdays(self::totalHariKerja($kasusBerat) - 1);
    }

    /**
     * Jumlah hari kerja tersisa menuju target, nol bila sudah lewat.
     *
     * Dihitung dari nomor hari yang sedang berjalan, bukan dari selisih
     * tanggal ke tanggal target. Kalau lewat tanggal target, selisih tanggal
     * akan menghasilkan angka negatif atau satu, sehingga tiket yang sudah
     * terlambat masih terbaca punya sisa satu hari.
     */
    public static function sisaHariKerja(
        CarbonInterface $mulai,
        ?CarbonInterface $sekarang = null,
        bool $kasusBerat = false,
    ): int {
        $total = self::totalHariKerja($kasusBerat);

        return max(0, $total - self::hariKe($mulai, $sekarang));
    }

    /**
     * Zona SLA sebuah pengaduan.
     *
     * Pengaduan yang sudah selesai dinilai dari waktu penyelesaiannya,
     * sedangkan pengaduan yang masih berjalan dinilai dari posisi hari ini.
     * Keduanya memakai ambang yang sama: tepat waktu selama hari ke yang
     * sedang berjalan belum melewati hari kerja terakhir standar.
     */
    public static function zona(
        CarbonInterface $mulai,
        ?CarbonInterface $selesai = null,
        bool $kasusBerat = false,
    ): ZonaSla {
        $total = self::totalHariKerja($kasusBerat);
        $peringatan = (int) config('pengaduan.sla.peringatan_hari_kerja', 2);

        if ($selesai !== null) {
            return self::hariKerjaLewat($mulai, $selesai) < $total
                ? ZonaSla::TepatWaktu
                : ZonaSla::Terlambat;
        }

        $hariKe = self::hariKe($mulai);

        if ($hariKe > $total) {
            return ZonaSla::Terlambat;
        }

        return $total - $hariKe < $peringatan ? ZonaSla::Mendek : ZonaSla::TepatWaktu;
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
