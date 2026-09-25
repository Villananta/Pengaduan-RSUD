<?php

namespace App\Enums;

enum StatusPengaduan: string
{
    case Diterima = 'diterima';
    case Diproses = 'diproses';
    case Revisi = 'revisi';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Diterima => 'Diterima',
            self::Diproses => 'Diproses',
            self::Revisi => 'Perlu Revisi',
            self::Selesai => 'Selesai',
        };
    }

    // Warna badge pada daftar dan detail tiket.
    public function badge(): string
    {
        return match ($this) {
            self::Diterima => 'bg-brand-100 text-brand-800',
            self::Diproses => 'bg-brand-700 text-white',
            self::Revisi => 'bg-alert-light text-alert',
            self::Selesai => 'bg-brand-800 text-white',
        };
    }

    // Persentase progres SLA 12 hari kerja.
    public function persen(): int
    {
        return match ($this) {
            self::Diterima => 25,
            self::Diproses => 37,
            self::Revisi => 64,
            self::Selesai => 100,
        };
    }

    // Nomor tahap pada alur prosedur (0 sampai 3).
    public function tahap(): int
    {
        return match ($this) {
            self::Diterima => 0,
            self::Diproses => 1,
            self::Revisi => 2,
            self::Selesai => 3,
        };
    }

    public function perluAksi(): bool
    {
        return $this === self::Revisi;
    }

    public function diterima(): bool
    {
        return $this === self::Diterima;
    }

    public function diproses(): bool
    {
        return $this === self::Diproses;
    }

    public function selesai(): bool
    {
        return $this === self::Selesai;
    }

    public static function dariNilai(?string $nilai): ?self
    {
        return $nilai === null ? null : self::tryFrom($nilai);
    }
}
