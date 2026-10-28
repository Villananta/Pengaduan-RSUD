<?php

namespace App\Support;

use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use App\Models\User;
use App\Notifications\PengaduanJawabanUnit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

/**
 * Pengiriman jawaban oleh PIC unit ke humas.
 *
 * Kelas ini sengaja dipisah dari App\Services\KomunikasiService karena
 * pemiliknya berbeda: KomunikasiService hanya menjalankan perintah humas
 * (balas pelapor, koordinasi unit, nudge), sedangkan
 * kelas ini hanya menjalankan perintah unit. Dengan dipisah, aturan unit tidak
 * perlu ikut memeriksa tombol humas dan sebaliknya.
 *
 * Pesan disimpan dengan peran 'unit' pada kanal koordinasi supaya di konsol
 * humas jawaban ini terbaca sebagai suara PIC unit dan tidak tertukar dengan
 * balasan humas kepada pelapor. Peran itulah yang juga dipakai
 * App\Enums\StatusInvestigasi sebagai tanda "Menunggu Racikan Humas".
 */
final class JawabanUnit
{
    /**
     * Simpan jawaban unit di kanal koordinasi humas dan unit.
     *
     * Tahap tiket sengaja tidak diubah. Unit tidak berwenang memajukan tahap
     * pengaduan; unit hanya menyerahkan jawaban, dan penutupan tetap ada di
     * tangan humas lewat aksi yang sudah ada.
     *
     * @param  array<int, UploadedFile|null>  $berkas
     *
     * @throws RuntimeException bila tiket sudah selesai sehingga tidak lagi menerima jawaban
     */
    public static function kirim(MasterUnit $unit, Pengaduan $pengaduan, string $isi, array $berkas = []): void
    {
        if ($pengaduan->status->selesai()) {
            throw new RuntimeException('Tiket ini sudah selesai sehingga jawaban unit tidak diterima lagi.');
        }

        DB::transaction(function () use ($pengaduan, $isi, $berkas): void {
            $pengaduan->pesan()->create([
                'kanal' => PesanPengaduan::KANAL_UNIT,
                'peran' => 'unit',
                'isi' => $isi,
                'lampiran' => PesanPengaduan::simpanBerkas($berkas),
            ]);
        });

        // Notifikasi ke admin humas
        $admins = User::where('peran', 'admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new PengaduanJawabanUnit($pengaduan, $unit));
        }

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
        StatistikUnit::lupaCache();
    }
}
