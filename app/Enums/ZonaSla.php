<?php

namespace App\Enums;

enum ZonaSla: string
{
    case TepatWaktu = 'tepat_waktu';
    case Mendek = 'mendek';
    case Terlambat = 'terlambat';

    public function label(): string
    {
        return match ($this) {
            self::TepatWaktu => 'Tepat Waktu',
            self::Mendek => 'Mendek Batas',
            self::Terlambat => 'Terlambat',
        };
    }

    // Warna badge zona SLA pada ringkasan tiket.
    public function badge(): string
    {
        return match ($this) {
            self::TepatWaktu => 'bg-brand-100 text-brand-800',
            self::Mendek => 'bg-alert-light text-alert',
            self::Terlambat => 'bg-alert text-white',
        };
    }

    // Warna badge zona SLA pada konsol admin, mengikuti palet Material green.
    public function badgeAdmin(): string
    {
        return match ($this) {
            self::TepatWaktu => 'bg-secondary-container text-on-secondary-container',
            self::Mendek => 'bg-tertiary-container text-tertiary-fixed-dim',
            self::Terlambat => 'bg-error text-on-error',
        };
    }

    /**
     * Nilai prioritas untuk mengurutkan tiket paling mendesak.
     *
     * Makin besar makin didahulukan di konsol admin.
     */
    public function prioritas(): int
    {
        return match ($this) {
            self::Terlambat => 3,
            self::Mendek => 2,
            self::TepatWaktu => 1,
        };
    }
}
