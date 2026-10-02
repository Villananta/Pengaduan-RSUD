<?php

namespace App\Enums;

/**
 * Peran akun PIC unit di dashboard disposisi.
 *
 * Peran ini belum punya arti di sisi aplikasi karena halaman PIC belum
 * dibangun. Nilainya tetap disimpan supaya data master unit tidak perlu
 * diisi ulang begitu halamannya tersedia.
 */
enum PeranAksesUnit: string
{
    case KepalaUnit = 'kepala_unit';
    case SupervisorInvestigasi = 'supervisor_investigasi';
    case StafPelaksana = 'staf_pelaksana';

    /** Sebutan panjang untuk form master unit. */
    public function label(): string
    {
        return match ($this) {
            self::KepalaUnit => 'Ka. Unit / Dokter Penanggung Jawab',
            self::SupervisorInvestigasi => 'Supervisor / Tim Investigasi',
            self::StafPelaksana => 'Staf Pelaksana & Notulen Disposisi',
        };
    }
}
