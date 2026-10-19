<?php

namespace App\Services;

use App\Models\Pengaduan;
use App\Models\User;
use App\Notifications\PengaduanKasusBerat;
use App\Support\StatistikDashboard;
use App\Support\StatistikPengaduan;
use App\Support\StatistikUnit;
use Illuminate\Support\Facades\Notification;

final class KasusBeratService
{
    /** Nyalakan atau matikan penandaan kasus berat beserta jejak auditnya. */
    public static function tandaiKasusBerat(Pengaduan $pengaduan, bool $aktif, ?int $adminId = null): void
    {
        $pengaduan->tandaiKasusBerat($aktif, $adminId);

        // Notifikasi ke admin humas
        $admins = User::where('peran', 'admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new PengaduanKasusBerat($pengaduan, $aktif));
        }

        // Notifikasi ke PIC unit jika ada
        $pengaduan->loadMissing('masterUnit.pengguna');
        if ($pengaduan->master_unit_id !== null && $pengaduan->masterUnit?->pengguna->isNotEmpty()) {
            Notification::send($pengaduan->masterUnit->pengguna, new PengaduanKasusBerat($pengaduan, $aktif));
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
