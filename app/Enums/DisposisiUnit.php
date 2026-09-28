<?php

namespace App\Enums;

enum DisposisiUnit: string
{
    case Terhubung = 'terhubung';
    case MenungguInvestigasi = 'menunggu_investigasi';
    case SedangInvestigasi = 'sedang_investigasi';
    case MenungguInfoTambahan = 'menunggu_info_tambahan';
    case JawabanMasuk = 'jawaban_masuk';
    case Terputus = 'terputus';

    public function label(): string
    {
        return match ($this) {
            self::Terhubung => 'Terhubung',
            self::MenungguInvestigasi => 'Menunggu Investigasi',
            self::SedangInvestigasi => 'Sedang Investigasi',
            self::MenungguInfoTambahan => 'Menunggu Info Tambahan',
            self::JawabanMasuk => 'Jawaban Masuk',
            self::Terputus => 'Koneksi Terputus',
        };
    }

    /** Ringkasan singkat untuk ditampilkan di panel status koneksi SIMRS. */
    public function ringkas(): string
    {
        return match ($this) {
            self::Terhubung => 'Siap menerima penugasan',
            self::MenungguInvestigasi => 'Menunggu investigasi internal',
            self::SedangInvestigasi => 'Investigasi internal berjalan',
            self::MenungguInfoTambahan => 'Butuh data pelengkap',
            self::JawabanMasuk => 'Menunggu racikan humas',
            self::Terputus => 'Belum dapat dihubungi',
        };
    }

    // Warna penanda pada panel status koneksi SIMRS di konsol admin.
    public function badge(): string
    {
        return match ($this) {
            self::Terhubung, self::JawabanMasuk => 'bg-admin-success-soft text-admin-success-strong',
            self::SedangInvestigasi => 'bg-admin-info-soft text-admin-success-strong',
            self::MenungguInvestigasi, self::MenungguInfoTambahan => 'bg-admin-warning-soft text-admin-warning-strong',
            self::Terputus => 'bg-admin-danger-soft text-admin-danger-strong',
        };
    }

    /** True bila unit masih bisa ditugaskan untuk menangani pengaduan. */
    public function siap(): bool
    {
        return $this !== self::Terputus;
    }
}
