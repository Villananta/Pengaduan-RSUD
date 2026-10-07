<?php

namespace App\Support;

use App\Enums\DisposisiUnit;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pengiriman jawaban oleh PIC unit ke humas.
 *
 * Kelas ini sengaja dipisah dari App\Support\TindakLanjutPengaduan karena
 * pemiliknya berbeda: TindakLanjutPengaduan hanya menjalankan perintah humas
 * (menutup tiket, mengembalikan disposisi, memindahkan tahap), sedangkan
 * kelas ini hanya menjalankan perintah unit. Dengan dipisah, aturan unit tidak
 * perlu ikut memeriksa tombol humas dan sebaliknya.
 *
 * Pesan disimpan dengan peran 'unit', bukan 'admin', supaya di konsol humas
 * jawaban ini terbaca sebagai suara PIC unit dan tidak tertukar dengan balasan
 * humas kepada pelapor. Peran itulah yang juga dipakai App\Enums\StatusInvestigasi
 * sebagai tanda "Menunggu Racikan Humas".
 */
final class JawabanUnit
{
    /**
     * Simpan jawaban unit pada percakapan tiket.
     *
     * Tahap tiket sengaja tidak diubah. Unit tidak berwenang memajukan tahap
     * pengaduan; unit hanya menyerahkan jawaban, dan penutupan tetap ada di
     * tangan humas lewat aksi yang sudah ada.
     *
     * @throws RuntimeException bila tiket sudah selesai sehingga tidak lagi menerima jawaban
     */
    public static function kirim(MasterUnit $unit, Pengaduan $pengaduan, string $isi): void
    {
        if ($pengaduan->status->selesai()) {
            throw new RuntimeException('Tiket ini sudah selesai sehingga jawaban unit tidak diterima lagi.');
        }

        DB::transaction(function () use ($unit, $pengaduan, $isi): void {
            $pengaduan->pesan()->create([
                'peran' => 'unit',
                'isi' => $isi,
            ]);

            // Panel koneksi SIMRS di konsol humas membaca kolom disposisi,
            // jadi jawaban yang baru masuk harus ikut ditandai di sana.
            $unit->update([
                'disposisi' => DisposisiUnit::JawabanMasuk,
            ]);
        });

        self::segarkanStatistik();
    }

    /**
     * Buang cache angka dashboard dan daftar pengaduan.
     *
     * Jawaban baru mengubah status investigasi tiap tiket dan angka telaah,
     * sehingga kedua layar itu harus menghitung ulang pada pembukaan berikutnya.
     */
    private static function segarkanStatistik(): void
    {
        StatistikDashboard::lupaCache();
        StatistikPengaduan::lupaCache();
    }
}
