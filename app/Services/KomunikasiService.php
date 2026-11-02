<?php

namespace App\Services;

use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use App\Models\User;
use App\Notifications\PengaduanBalasanAdmin;
use App\Notifications\PengaduanNudgeUnit;
use App\Support\StatistikDashboard;
use App\Support\StatistikPengaduan;
use App\Support\StatistikUnit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

final class KomunikasiService
{
    /** Jarak minimal antar-nudge ke unit yang sama, dalam menit. */
    private const COOLDOWN_NUDGE_MENIT = 240;

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

        // Pelapor tidak punya akun, jadi balasan dikirim ke alamat email
        // yang tercatat pada tiket. Tanpa ini class PengaduanBalasanAdmin
        // tidak pernah terpakai dan pelapor tidak tahu ada jawaban baru.
        if (! empty($pengaduan->email)) {
            Notification::route('mail', $pengaduan->email)
                ->notify(new PengaduanBalasanAdmin($pengaduan, $isi));
        }

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

        // Cooldown: cek nudge terakhir via kolom is_nudge, minimal 4 jam (240 menit)
        $nudgeTerakhir = $pengaduan->riwayatStatus()
            ->where('is_nudge', true)
            ->latest('created_at')
            ->first();

        if ($nudgeTerakhir !== null && $nudgeTerakhir->created_at->gt(now()->subMinutes(self::COOLDOWN_NUDGE_MENIT))) {
            // Sisa waktu dihitung dari batas 4 jam, bukan dari waktu yang
            // sudah berjalan. Angka lama menampilkan durasi sejak nudge
            // terakhir, sehingga admin justru membaca kebalikannya.
            $sisaDetik = max(
                0,
                self::COOLDOWN_NUDGE_MENIT * 60 - (int) $nudgeTerakhir->created_at->diffInSeconds(now()),
            );
            $sisaJam = intdiv($sisaDetik, 3600);
            $sisaMenitSisa = intdiv($sisaDetik % 3600, 60);

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
            'is_nudge' => true,
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
        StatistikDashboard::lupaCache();
        StatistikPengaduan::lupaCache();
        StatistikUnit::lupaCache();
    }
}
