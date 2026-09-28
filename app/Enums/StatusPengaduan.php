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

    /**
     * Tahap yang boleh dipilih berikutnya.
     *
     * Mencegah lompatan acak dan pengembalian ke tahap yang sudah
     * dilewati, kecuali reopen dari Selesai ke Diproses.
     *
     * @return array<int, self>
     */
    public function tujuanBerikutnya(): array
    {
        return match ($this) {
            self::Diterima => [self::Diproses, self::Revisi],
            self::Diproses => [self::Revisi, self::Selesai],
            self::Revisi => [self::Diproses, self::Selesai],
            self::Selesai => [self::Diproses],
        };
    }

    public function bisaBerpindahKe(self $tujuan): bool
    {
        return $this === $tujuan || in_array($tujuan, $this->tujuanBerikutnya(), true);
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
        return $nilai === null || $nilai === '' ? null : self::tryFrom($nilai);
    }
}
