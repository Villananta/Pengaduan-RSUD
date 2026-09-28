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
}
