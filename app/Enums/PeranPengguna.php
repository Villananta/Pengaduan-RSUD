<?php

namespace App\Enums;

/**
 * Peran akun pengguna di tingkat aplikasi.
 *
 * Membedakan pemegang konsol humas dari PIC unit yang hanya mengurus
 * unitnya sendiri. Nilai ini disimpan sekarang meski login belum dipasang,
 * supaya akun yang sudah ada tidak perlu diklasifikasi ulang begitu
 * middleware auth menyusul.
 */
enum PeranPengguna: string
{
    case Admin = 'admin';
    case Unit = 'unit';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin Humas',
            self::Unit => 'PIC Unit',
        };
    }
}
