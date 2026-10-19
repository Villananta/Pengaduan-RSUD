<?php

namespace App\Services;

use App\Enums\DisposisiUnit;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\User;
use App\Notifications\PengaduanDisposisi;
use App\Notifications\PengaduanTahapBerubah;
use App\Support\StatistikDashboard;
use App\Support\StatistikPengaduan;
use App\Support\StatistikUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

final class DisposisiService
{
    /**
     * Tugaskan tiket ke satu instalasi teknis.
     */
    public static function disposisiKeUnit(
        Pengaduan $pengaduan,
        MasterUnit $unit,
        ?string $catatan = null,
        ?int $adminId = null,
    ): void {
        if ($pengaduan->status->selesai()) {
            throw new RuntimeException('Tiket ini sudah selesai sehingga tidak bisa didisposisikan lagi.');
        }

        if (! $unit->bisaDitugaskan()) {
            throw new RuntimeException('Unit '.$unit->namaLengkap().' sedang tidak aktif atau koneksinya terputus.');
        }

        DB::transaction(function () use ($pengaduan, $unit, $catatan, $adminId): void {
            $unitSebelumnya = $pengaduan->masterUnit;

            $pengaduan->master_unit_id = $unit->id;

            if ($pengaduan->status === StatusPengaduan::Diproses) {
                $pengaduan->save();
                $pengaduan->riwayatStatus()->create([
                    'dari' => $pengaduan->status,
                    'ke' => $pengaduan->status,
                    'catatan' => $catatan ?? 'Disposisi dialihkan ke '.$unit->namaLengkap().'.',
                    'admin_id' => $adminId,
                ]);
            } else {
                $pengaduan->pindahTahap(StatusPengaduan::Diproses, $catatan, $adminId);
            }

            if ($unitSebelumnya !== null && $unitSebelumnya->id !== $unit->id) {
                $unitSebelumnya->update(['disposisi' => DisposisiUnit::Terhubung]);
            }

            $unit->update(['disposisi' => DisposisiUnit::MenungguInvestigasi]);
        });

        // Kirim notifikasi ke PIC unit yang baru ditugaskan
        $pengaduan->loadMissing('masterUnit.pengguna');
        if ($pengaduan->masterUnit?->pengguna->isNotEmpty()) {
            Notification::send(
                $pengaduan->masterUnit->pengguna,
                new PengaduanDisposisi($pengaduan, $unit, $catatan)
            );
        }

        self::segarkanStatistik();
    }

    /**
     * Jalur 2: humas menangani tiket sendiri tanpa melibatkan unit teknis.
     */
    public static function tanganiLangsung(
        Pengaduan $pengaduan,
        ?string $catatan = null,
        ?int $adminId = null,
    ): void {
        if (! in_array(StatusPengaduan::Diproses, TahapService::tujuanTersedia($pengaduan), true)) {
            throw new RuntimeException(
                $pengaduan->status->selesai()
                    ? 'Tiket ini sudah selesai sehingga tidak bisa ditangani langsung lagi.'
                    : 'Tahap '.$pengaduan->status->label().' tidak menawarkan penanganan langsung oleh humas.'
            );
        }

        // Status sebelum transaksi dibaca di sini: pindahTahap() di dalam
        // transaksi sudah menyimpan, sehingga getOriginal() setelahnya hanya
        // mengembalikan status yang baru dan notifikasi jadi "Diproses ke
        // Diproses" meski tahapnya berubah dari Baru atau Revisi.
        $statusSebelum = $pengaduan->status;

        DB::transaction(function () use ($pengaduan, $catatan, $adminId): void {
            $unitDilepas = $pengaduan->masterUnit;

            $pengaduan->master_unit_id = null;
            $pengaduan->pindahTahap(StatusPengaduan::Diproses, $catatan, $adminId);

            if ($unitDilepas !== null) {
                $unitDilepas->update(['disposisi' => DisposisiUnit::Terhubung]);

                $pengaduan->riwayatStatus()->create([
                    'dari' => StatusPengaduan::Diproses,
                    'ke' => StatusPengaduan::Diproses,
                    'catatan' => 'Disposisi ke '.$unitDilepas->namaLengkap().' dilepas; penanganan diambil alih humas.',
                    'admin_id' => $adminId,
                ]);
            }
        });

        // Notifikasi ke admin humas bahwa tiket ditangani langsung
        $admins = User::where('peran', 'admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new PengaduanTahapBerubah(
                $pengaduan,
                $statusSebelum,
                StatusPengaduan::Diproses
            ));
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
