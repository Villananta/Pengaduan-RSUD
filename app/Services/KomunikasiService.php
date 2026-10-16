<?php

namespace App\Services;

use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use App\Models\User;
use App\Notifications\PengaduanNudgeUnit;
use App\Notifications\PengaduanTahapBerubah;
use Illuminate\Http\UploadedFile;
use RuntimeException;

final class KomunikasiService
{
    /**
     * Simpan satu balasan admin di kolom percakapan.
     *
     * @param  array<int, UploadedFile|null>  $berkas
     */
    public static function balasPelapor(
        Pengaduan $pengaduan,
        string $isi,
        array $berkas = [],
    ): void {
        $pengaduan->pesan()->create([
            'kanal' => PesanPengaduan::KANAL_PELAPOR,
            'peran' => 'admin',
            'isi' => $isi,
            'lampiran' => PesanPengaduan::simpanBerkas($berkas),
        ]);

        $pengaduan->loadMissing('masterUnit');
    }

    /**
     * Kirim satu pesan admin humas ke kanal koordinasi unit.
     *
     * @param  array<int, UploadedFile|null>  $berkas
     */
    public static function koordinasiUnit(
        Pengaduan $pengaduan,
        string $isi,
        array $berkas = [],
    ): void {
        if ($pengaduan->master_unit_id === null) {
            throw new RuntimeException('Tiket ini belum ditugaskan ke unit sehingga tidak ada kanal koordinasi unit.');
        }

        $pengaduan->pesan()->create([
            'kanal' => PesanPengaduan::KANAL_UNIT,
            'peran' => 'admin',
            'isi' => $isi,
            'lampiran' => PesanPengaduan::simpanBerkas($berkas),
        ]);
    }

    /**
     * Kirim nudge (follow up manual) ke unit yang menangani tiket.
     */
    public static function nudgeUnit(
        Pengaduan $pengaduan,
        string $catatan,
        ?int $adminId = null,
    ): void {
        if ($pengaduan->master_unit_id === null) {
            throw new RuntimeException('Tiket ini belum ditugaskan ke unit sehingga tidak bisa di-nudge.');
        }

        if ($pengaduan->status->selesai()) {
            throw new RuntimeException('Tiket ini sudah selesai sehingga tidak perlu di-nudge.');
        }

        // Cooldown: cek nudge terakhir, minimal 4 jam (240 menit)
        $nudgeTerakhir = $pengaduan->riwayatStatus()
            ->whereRaw("catatan LIKE 'Nudge manual ke unit:%'")
            ->latest('created_at')
            ->first();

        if ($nudgeTerakhir !== null && $nudgeTerakhir->created_at->gt(now()->subMinutes(240))) {
            $sisaMenit = $nudgeTerakhir->created_at->diffInMinutes(now(), true) * -1;
            $sisaJam = floor(abs($sisaMenit) / 60);
            $sisaMenitSisa = abs($sisaMenit) % 60;
            throw new RuntimeException(
                'Nudge baru bisa dikirim lagi setelah 4 jam dari nudge terakhir. '
                .'Sisa waktu: '.$sisaJam.' jam '.$sisaMenitSisa.' menit.'
            );
        }

        // Catat ke riwayat status sebagai jejak audit
        $pengaduan->riwayatStatus()->create([
            'dari' => $pengaduan->status,
            'ke' => $pengaduan->status,
            'catatan' => 'Nudge manual ke unit: '.$catatan,
            'admin_id' => $adminId,
        ]);

        // Kirim notifikasi ke PIC unit
        $pengaduan->loadMissing('masterUnit.pengguna');
        if ($pengaduan->masterUnit?->pengguna->isNotEmpty()) {
            Notification::send(
                $pengaduan->masterUnit->pengguna,
                new PengaduanNudgeUnit($pengaduan, $pengaduan->masterUnit, $catatan)
            );
        }

        // Notifikasi ke admin humas juga biar tau sudah di-nudge
        $admins = User::where('peran', 'admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send(
                $admins,
                new PengaduanNudgeUnit($pengaduan, $pengaduan->masterUnit, $catatan)
            );
        }

        self::segarkanStatistik();
    }

    private static function segarkanStatistik(): void
    {
        \App\Support\StatistikDashboard::lupaCache();
        \App\Support\StatistikPengaduan::lupaCache();
        \App\Support\StatistikUnit::lupaCache();
    }
}