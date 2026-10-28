<?php

namespace App\Services;

use App\Enums\PeranPengguna;
use App\Models\Pengaduan;
use App\Models\User;
use App\Notifications\PengaduanKasusBerat;
use App\Support\StatistikDashboard;
use App\Support\StatistikPengaduan;
use App\Support\StatistikUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;

final class KasusBeratService
{
    /** Nyalakan atau matikan penandaan kasus berat beserta jejak auditnya. */
    public static function tandaiKasusBerat(Pengaduan $pengaduan, bool $aktif, ?int $adminId = null): void
    {
        $pengaduan->tandaiKasusBerat($aktif, $adminId);

        // Pelepasan penandaan hanya koreksi yang dilakukan humas sendiri,
        // jadi tidak ada yang perlu dikabari: jejaknya sudah ada di riwayat
        // status, dan notifikasi "penandaan dilepas" hanya menambah bising
        // di lonceng admin maupun PIC unit.
        if (! $aktif) {
            self::segarkanStatistik();

            return;
        }

        // Admin yang menekan tombolnya sendiri tidak perlu menerima kabar
        // atas tindakannya; yang perlu tahu adalah admin lain.
        $admins = User::query()
            ->where('peran', PeranPengguna::Admin)
            ->when($adminId !== null, fn (Builder $q): Builder => $q->whereKeyNot($adminId))
            ->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new PengaduanKasusBerat($pengaduan, true));
        }

        // Notifikasi ke PIC unit jika ada
        $pengaduan->loadMissing('masterUnit.pengguna');
        if ($pengaduan->master_unit_id !== null && $pengaduan->masterUnit?->pengguna->isNotEmpty()) {
            Notification::send($pengaduan->masterUnit->pengguna, new PengaduanKasusBerat($pengaduan, true));
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
