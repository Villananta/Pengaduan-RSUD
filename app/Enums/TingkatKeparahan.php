<?php

namespace App\Enums;

/**
 * Tingkat keparahan pengaduan yang ditetapkan admin humas.
 *
 * Nilai ini hanya penanda warna dan filter di konsol, bukan pengubah
 * target SLA. Tenggat tetap dihitung dari App\Support\Sla supaya
 * keputusan admin tidak diam-diam menggeser janji waktu ke pelapor.
 */
enum TingkatKeparahan: string
{
    case Ringan = 'ringan';
    case Sedang = 'sedang';
    case Berat = 'berat';

    public function label(): string
    {
        return match ($this) {
            self::Ringan => 'Ringan',
            self::Sedang => 'Sedang',
            self::Berat => 'Berat',
        };
    }

    /** Warna badge pada daftar dan detail tiket. */
    public function badge(): string
    {
        return match ($this) {
            self::Ringan => 'bg-secondary-container text-on-secondary-container',
            self::Sedang => 'bg-tertiary-container text-on-tertiary-container',
            self::Berat => 'bg-error text-on-error',
        };
    }

    /** Warna titik penanda pada filter daftar. */
    public function titik(): string
    {
        return match ($this) {
            self::Ringan => 'bg-secondary',
            self::Sedang => 'bg-tertiary',
            self::Berat => 'bg-error',
        };
    }

    public function ikon(): string
    {
        return match ($this) {
            self::Ringan => 'check_circle',
            self::Sedang => 'warning',
            self::Berat => 'emergency',
        };
    }

    public static function dariNilai(?string $nilai): ?self
    {
        return $nilai === null || $nilai === '' ? null : self::tryFrom($nilai);
    }
}
