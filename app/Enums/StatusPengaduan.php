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

    /**
     * Nama sub-status unit pada konsol admin.
     *
     * Keempat sub-status itu hanya sebutan di sisi unit, dipetakan dari
     * tahap pengaduan yang sama supaya tidak ada tahap baru yang harus
     * diisi ulang setiap kali admin menangani tiket.
     */
    public function subStatus(): string
    {
        return match ($this) {
            self::Diterima => 'Menunggu Unit Membuka',
            self::Diproses => 'Sedang Diinvestigasi Unit',
            self::Revisi => 'Unit Butuh Info Tambahan',
            self::Selesai => 'Jawaban Unit Masuk',
        };
    }

    /** Jumlah pengaduan pada tahap ini, untuk kartu bento konsol admin. */
    public function warnaAngka(): string
    {
        return match ($this) {
            self::Diterima => 'text-admin-ink',
            self::Diproses => 'text-admin-brand-600',
            self::Revisi => 'text-admin-danger-base',
            self::Selesai => 'text-admin-brand-900',
        };
    }

    /** Warna kotak ikon pada kartu bento konsol admin. */
    public function warnaIkon(): string
    {
        return match ($this) {
            self::Diterima => 'bg-admin-info-soft text-admin-brand-900',
            self::Diproses => 'bg-admin-success-soft text-admin-brand-600',
            self::Revisi => 'bg-admin-danger-soft text-admin-danger-strong',
            self::Selesai => 'bg-admin-brand-200 text-admin-brand-900',
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

    // Warna badge pada konsol admin, mengikuti palet Material green.
    public function badgeAdmin(): string
    {
        return match ($this) {
            self::Diterima => 'bg-admin-success-soft text-admin-success-strong',
            self::Diproses => 'bg-admin-success-soft text-admin-success-strong',
            self::Revisi => 'bg-admin-danger-base text-white',
            self::Selesai => 'bg-admin-success-soft text-admin-success-strong',
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
