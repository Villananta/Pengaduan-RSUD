<?php

namespace App\Enums;

/**
 * Kondisi akun PIC pada sistem SIMRS unit.
 *
 * Status ini kini ikut berubah otomatis lewat alur undangan aktivasi
 * (App\Support\UndanganPic), tapi tetap disimpan sebagai kolom sendiri,
 * bukan turunan dari kolom waktu: admin masih bisa mencatat kondisi yang
 * diketahui di luar alur tersebut, misalnya akun SIMRS yang memang sudah
 * aktif tanpa pernah lewat undangan.
 */
enum StatusAksesUnit: string
{
    case BelumDiundang = 'belum_diundang';
    case UndanganTerkirim = 'undangan_terkirim';
    case AkunAktif = 'akun_aktif';

    /** Sebutan kondisi untuk kolom Akses Dashboard. */
    public function label(): string
    {
        return match ($this) {
            self::BelumDiundang => 'Belum Diundang',
            self::UndanganTerkirim => 'Undangan Terkirim',
            self::AkunAktif => 'Akun Aktif',
        };
    }

    /** Ikon Material yang membedakan ketiga kondisi tersebut. */
    public function ikon(): string
    {
        return match ($this) {
            self::BelumDiundang => 'schedule',
            self::UndanganTerkirim => 'mail',
            self::AkunAktif => 'check_circle',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::BelumDiundang => 'bg-surface-container text-on-surface-variant',
            self::UndanganTerkirim => 'bg-tertiary-fixed text-on-tertiary-fixed',
            self::AkunAktif => 'bg-secondary-container text-on-secondary-container',
        };
    }

    /** Keterangan singkat kondisi untuk dipakai di form master unit. */
    public function catatan(): string
    {
        return match ($this) {
            self::BelumDiundang => 'Belum ada undangan aktivasi yang tercatat untuk unit ini.',
            self::UndanganTerkirim => 'Undangan sudah tercatat, tinggal menunggu PIC membuka tautannya.',
            self::AkunAktif => 'PIC sudah masuk memakai akunnya dan bisa melihat tiketnya sendiri.',
        };
    }
}
