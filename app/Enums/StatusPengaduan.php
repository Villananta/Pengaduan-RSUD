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
            self::Diterima => 'text-on-surface',
            self::Diproses => 'text-secondary',
            self::Revisi => 'text-error',
            self::Selesai => 'text-primary-container',
        };
    }

    /** Warna kotak ikon pada kartu bento konsol admin. */
    public function warnaIkon(): string
    {
        return match ($this) {
            self::Diterima => 'bg-surface-container text-primary-container',
            self::Diproses => 'bg-secondary-container text-on-secondary-container',
            self::Revisi => 'bg-error-container text-on-error-container',
            self::Selesai => 'bg-primary-fixed text-primary-container',
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
            self::Diterima => 'bg-secondary-container text-on-secondary-container',
            self::Diproses => 'bg-secondary-container text-on-secondary-container',
            self::Revisi => 'bg-error text-on-error',
            self::Selesai => 'bg-secondary-container text-on-secondary-container',
        };
    }

    /** Ikon Material Symbols untuk lencana tahap pada konsol admin. */
    public function ikon(): string
    {
        return match ($this) {
            self::Diterima => 'inbox',
            self::Diproses => 'autorenew',
            self::Revisi => 'rate_review',
            self::Selesai => 'task_alt',
        };
    }

    /** Warna lencana jumlah pada tab Lapis 1 yang sedang tidak aktif. */
    public function chip(): string
    {
        return match ($this) {
            self::Diproses => 'bg-secondary-container text-on-secondary-container',
            self::Revisi => 'bg-error-container text-on-error-container',
            self::Diterima, self::Selesai => 'bg-surface-container-highest text-on-surface',
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
