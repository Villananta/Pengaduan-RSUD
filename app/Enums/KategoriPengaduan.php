<?php

namespace App\Enums;

enum KategoriPengaduan: string
{
    case Fasilitas = 'fasilitas';
    case Medis = 'medis';

    // Nama singkat untuk badge dan daftar tiket.
    public function label(): string
    {
        return match ($this) {
            self::Fasilitas => 'Fasilitas & Pelayanan',
            self::Medis => 'Pelayanan Medis',
        };
    }

    // Judul panjang pada form pengajuan.
    public function judul(): string
    {
        return match ($this) {
            self::Fasilitas => 'Fasilitas Umum & Sarana Prasarana',
            self::Medis => 'Pelayanan Medis & Tenaga Kesehatan',
        };
    }

    public function deskripsi(): string
    {
        return match ($this) {
            self::Fasilitas => 'Antrean pendaftaran, kebersihan toilet/ruangan, AC, parkir, fasilitas lift, rambu petunjuk arah, kasir, dll.',
            self::Medis => 'Tindakan dokter/perawat, komunikasi DPJP, keterlambatan visitasi, dispensing obat farmasi, edukasi terapi medis.',
        };
    }

    // Kunci ikon yang dipakai pada form pengajuan.
    public function ikon(): string
    {
        return match ($this) {
            self::Fasilitas => 'bangunan',
            self::Medis => 'medis',
        };
    }
}
