<?php

namespace App\Services;

use App\Enums\DisposisiUnit;
use App\Enums\StatusPengaduan;
use App\Models\Pengaduan;
use App\Models\User;
use App\Notifications\PengaduanTahapBerubah;
use RuntimeException;

final class TahapService
{
    /**
     * Pindahkan tiket ke tahap yang dipilih admin.
     */
    public static function pindahkanTahap(
        Pengaduan $pengaduan,
        StatusPengaduan $tujuan,
        ?string $catatan = null,
        ?int $adminId = null,
    ): void {
        $alasan = self::alasanPindah($pengaduan, $tujuan);

        if ($alasan !== null) {
            throw new RuntimeException($alasan);
        }

        $statusSebelumnya = $pengaduan->status;

        \Illuminate\Support\Facades\DB::transaction(function () use ($pengaduan, $tujuan, $catatan, $adminId): void {
            $pengaduan->pindahTahap($tujuan, $catatan, $adminId);
            self::selaraskanDisposisi($pengaduan, $tujuan);
        });

        // Kirim notifikasi perubahan tahap
        $pengaduan->loadMissing('masterUnit.pengguna');

        // Notifikasi ke admin humas
        $admins = User::where('peran', 'admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new PengaduanTahapBerubah($pengaduan, $statusSebelumnya, $tujuan));
        }

        // Notifikasi ke PIC unit jika ada
        if ($pengaduan->master_unit_id !== null && $pengaduan->masterUnit?->pengguna->isNotEmpty()) {
            Notification::send($pengaduan->masterUnit->pengguna, new PengaduanTahapBerubah($pengaduan, $statusSebelumnya, $tujuan));
        }

        self::segarkanStatistik();
    }

    /**
     * Selaraskan bendera disposisi unit dengan tahap tiket.
     */
    private static function selaraskanDisposisi(Pengaduan $pengaduan, StatusPengaduan $tahap): void
    {
        if ($pengaduan->master_unit_id === null) {
            return;
        }

        $disposisi = match ($tahap) {
            StatusPengaduan::Diproses => DisposisiUnit::MenungguInvestigasi,
            StatusPengaduan::Revisi => DisposisiUnit::MenungguInfoTambahan,
            StatusPengaduan::Selesai => DisposisiUnit::Terhubung,
            StatusPengaduan::Diterima => null,
        };

        if ($disposisi === null) {
            return;
        }

        $pengaduan->masterUnit?->update(['disposisi' => $disposisi]);
    }

    /**
     * Tombol tahap mana yang boleh ditunjukkan pada tahap sekarang.
     *
     * @return array<int, StatusPengaduan>
     */
    public static function tujuanTersedia(Pengaduan $pengaduan): array
    {
        return match ($pengaduan->status) {
            StatusPengaduan::Diterima => [StatusPengaduan::Diproses],
            StatusPengaduan::Diproses => [StatusPengaduan::Selesai, StatusPengaduan::Revisi],
            StatusPengaduan::Revisi => [StatusPengaduan::Selesai],
            StatusPengaduan::Selesai => [],
        };
    }

    /** Kalimat hasil tindakan yang ditampilkan setelah tiket berhasil dipindahkan. */
    public static function pesanTujuan(StatusPengaduan $tujuan): string
    {
        return match ($tujuan) {
            StatusPengaduan::Diproses => 'Pengaduan masuk tahap Diproses dan menunggu penanganan unit.',
            StatusPengaduan::Revisi => 'Pengaduan ditandai perlu revisi, pelapor diminta melengkapi data.',
            StatusPengaduan::Selesai => 'Pengaduan ditandai selesai.',
            default => 'Tahap pengaduan diperbarui.',
        };
    }

    /**
     * Label, ikon, dan warna tombol untuk satu tahap tujuan.
     *
     * @return array{label: string, ikon: string, warna: string}
     */
    public static function tombolTujuan(StatusPengaduan $tujuan): array
    {
        return match ($tujuan) {
            StatusPengaduan::Diproses => [
                'label' => 'Proses',
                'ikon' => 'play_arrow',
                'warna' => 'bg-primary text-on-primary',
            ],
            StatusPengaduan::Revisi => [
                'label' => 'Revisi',
                'ikon' => 'rate_review',
                'warna' => 'bg-error text-white',
            ],
            StatusPengaduan::Selesai => [
                'label' => 'Selesaikan',
                'ikon' => 'check_circle',
                'warna' => 'bg-primary text-on-primary',
            ],
            default => [
                'label' => $tujuan->label(),
                'ikon' => $tujuan->ikon(),
                'warna' => 'bg-surface-container text-on-surface',
            ],
        };
    }

    /**
     * Alasan kenapa suatu tujuan tidak boleh dipakai, atau null kalau sah.
     */
    public static function alasanPindah(Pengaduan $pengaduan, StatusPengaduan $tujuan): ?string
    {
        if ($pengaduan->status === $tujuan) {
            return 'Pengaduan sudah berada di tahap '.$tujuan->label().'.';
        }

        if (! in_array($tujuan, self::tujuanTersedia($pengaduan), true)) {
            return $pengaduan->status->selesai()
                ? 'Tiket ini sudah selesai sehingga tahapnya tidak bisa diubah lagi.'
                : 'Tahap '.$pengaduan->status->label().' tidak punya tombol untuk pindah ke '.$tujuan->label().'.';
        }

        return null;
    }

    /**
     * Alasan tiket belum bisa ditutup dari panel tahap, atau null kalau bisa.
     */
    public static function alasanTutup(Pengaduan $pengaduan): ?string
    {
        if ($pengaduan->status->selesai()) {
            return 'Tiket ini sudah selesai sehingga tidak ada tindakan lanjutan.';
        }

        if (! $pengaduan->status->bisaBerpindahKe(StatusPengaduan::Selesai)) {
            return 'Tahap '.$pengaduan->status->label().' belum boleh ditutup.';
        }

        return null;
    }

    private static function segarkanStatistik(): void
    {
        \App\Support\StatistikDashboard::lupaCache();
        \App\Support\StatistikPengaduan::lupaCache();
        \App\Support\StatistikUnit::lupaCache();
    }
}