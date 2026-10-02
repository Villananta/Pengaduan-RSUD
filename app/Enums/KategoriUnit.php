<?php

namespace App\Enums;

/**
 * Kategori pelayanan sebuah instalasi.
 *
 * Kategori ikut menentukan unit mana yang pantas masuk daftar tujuan
 * disposisi, jadi penambahannya selalu lewat enum dan bukan string bebas
 * di template. Dengan begitu pilihan pada form master unit dan pilihan
 * pada form disposisi tidak bisa berbeda isi.
 */
enum KategoriUnit: string
{
    case InstalasiMedis = 'instalasi_medis';
    case PenunjangKlinis = 'penunjang_klinis';
    case LayananRawatInap = 'layanan_rawat_inap';
    case LayananAdministrasi = 'layanan_administrasi';

    /** Sebutan panjang untuk judul kolom dan form. */
    public function label(): string
    {
        return match ($this) {
            self::InstalasiMedis => 'Instalasi Medis',
            self::PenunjangKlinis => 'Penunjang Klinis',
            self::LayananRawatInap => 'Layanan Rawat Inap',
            self::LayananAdministrasi => 'Layanan Administrasi',
        };
    }

    // Warna lencana pada tabel master unit supaya kategori cepat dibedakan.
    public function badge(): string
    {
        return match ($this) {
            self::InstalasiMedis => 'bg-primary-container text-on-primary',
            self::PenunjangKlinis => 'bg-surface-container text-secondary',
            self::LayananRawatInap => 'bg-tertiary-container text-on-tertiary-container',
            self::LayananAdministrasi => 'bg-surface-container-high text-on-surface-variant',
        };
    }
}
