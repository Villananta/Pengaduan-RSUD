<?php

namespace App\Enums;

/**
 * Peran akun pengguna di tingkat aplikasi.
 *
 * Membedakan pemegang konsol humas dari PIC unit yang hanya mengurus
 * unitnya sendiri. Nilai inilah yang dibaca middleware PastikanAdmin dan
 * PastikanPic untuk menutup konsol masing-masing.
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
