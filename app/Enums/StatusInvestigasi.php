<?php

namespace App\Enums;

use App\Models\Pengaduan;
use Illuminate\Database\Eloquent\Builder;

/**
 * Status investigasi internal unit atas satu tiket.
 *
 * Status pada App\Enums\StatusPengaduan melihat sisi pelapor, sedangkan
 * enum ini melihat sisi unit: apakah tiket sudah ditugaskan ke instalasi,
 * sedang ditelusuri, jawabannya sudah masuk dan tinggal diracik humas,
 * atau memang tidak pernah melibatkan unit karena ditangani humas
 * langsung.
 *
 * Kedua kumpulan status ini saling melengkapi dan tidak tumpang tindih,
 * jadi jumlah tiap tab pada daftar pengaduan bisa langsung dibandingkan.
 */
enum StatusInvestigasi: string
{
    // Tiket sudah masuk ke unit, tapi belum ada keputusan penanganan.
    case MenungguTriase = 'menunggu_triase';

    // Unit sedang menelusuri jalannya complain.
    case SedangInvestigasi = 'sedang_investigasi';

    // Tiket berhenti menunggu pelapor melengkapi data.
    case MenungguInfoTambahan = 'menunggu_info_tambahan';

    // Unit sudah menjawab, tinggal diracik dan diterbitkan humas.
    case MenungguRacikan = 'menunggu_racikan';

    // Arsip unit sudah lengkap, tiket ditutup.
    case JawabanUnit = 'jawaban_unit';

    // Tiket tidak pernah ditugaskan ke instalasi teknis.
    case LangsungHumas = 'langsung_humas';

    public function label(): string
    {
        return match ($this) {
            self::MenungguTriase => 'Menunggu Triase',
            self::SedangInvestigasi => 'Sedang Investigasi',
            self::MenungguInfoTambahan => 'Menunggu Info Tambahan',
            self::MenungguRacikan => 'Menunggu Racikan Humas',
            self::JawabanUnit => 'Jawaban Unit Masuk',
            self::LangsungHumas => 'Ditangani Langsung Humas',
        };
    }

    /** Uraian singkat kondisi investigasi, ditampilkan di dalam sel tabel. */
    public function ringkas(): string
    {
        return match ($this) {
            self::MenungguTriase => 'Belum didisposisi ke instalasi teknis, kepala unit belum menetapkan PIC.',
            self::SedangInvestigasi => 'PIC unit sedang menelusuri kronologi, berkas, dan hasil pemeriksaan.',
            self::MenungguInfoTambahan => 'Investigasi berhenti karena data pelapor belum lengkap.',
            self::MenungguRacikan => 'Draf klarifikasi unit sudah masuk dan siap diracik humas.',
            self::JawabanUnit => 'Arsip unit lengkap, tiket ditutup dengan jawaban resmi.',
            self::LangsungHumas => 'Klarifikasi dikerjakan Customer Care humas tanpa disposisi unit teknis.',
        };
    }

    /** Warna lencana pada tab filter dan sel tabel. */
    public function nada(): string
    {
        return match ($this) {
            self::SedangInvestigasi => 'bg-surface-container text-on-surface',
            self::MenungguRacikan, self::JawabanUnit => 'bg-secondary-container text-on-secondary-container',
            self::MenungguInfoTambahan => 'bg-tertiary-container text-tertiary-fixed-dim',
            self::MenungguTriase => 'bg-surface-container-lowest text-on-surface',
            self::LangsungHumas => 'bg-surface-container-low text-on-surface-variant',
        };
    }

    /** Warna titik penanda pada deretan tab filter Lapis 2. */
    public function titik(): string
    {
        return match ($this) {
            self::SedangInvestigasi => 'bg-on-tertiary-container',
            self::MenungguRacikan, self::JawabanUnit => 'bg-secondary',
            self::MenungguInfoTambahan => 'bg-tertiary-fixed-dim',
            self::MenungguTriase => 'bg-outline',
            self::LangsungHumas => 'bg-surface-tint',
        };
    }

    /**
     * Status investigasi satu tiket.
     *
     * Urutan pemeriksaan penting: tiket tanpa master_unit_id selalu
     * berstatus ditangani langsung humas, tiket yang sudah ditutup selalu
     * berstatus arsip unit, dan hanya tiket aktif bertaut unit yang bisa
     * berstatus menunggu racikan atau salah satu tahap investigasi.
     */
    public static function dariPengaduan(Pengaduan $pengaduan, bool $sudahDibalas = false): self
    {
        if ($pengaduan->master_unit_id === null) {
            return self::LangsungHumas;
        }

        if ($pengaduan->status->selesai()) {
            return self::JawabanUnit;
        }

        if ($sudahDibalas) {
            return self::MenungguRacikan;
        }

        return match ($pengaduan->status) {
            StatusPengaduan::Diterima => self::MenungguTriase,
            StatusPengaduan::Diproses => self::SedangInvestigasi,
            StatusPengaduan::Revisi => self::MenungguInfoTambahan,
            StatusPengaduan::Selesai => self::JawabanUnit,
        };
    }

    /**
     * True bila status ini menandai tiket yang sudah mencapai target unit.
     */
    public function nilaiButuhKiram(): bool
    {
        return in_array($this, [self::MenungguRacikan, self::JawabanUnit], true);
    }

    /**
     * Status investigasi yang ditandai hijau pada tabel.
     *
     * Dipisah dari nilaiButuhKiram() karena dua syarat ini tidak selalu
     * sama: tiket yang sudah ditutup tidak lagi butuh actuation, tetapi
     * tetap ditampilkan sebagai tahap yang mencapai target.
     *
     * @return array<int, self>
     */
    public static function racikanDanArsip(): array
    {
        return [self::MenungguRacikan, self::JawabanUnit];
    }

    /**
     * Batasi query ke satu status investigasi.
     *
     * Syarat tiap status disusun saling lepas supaya jumlah pada setiap
     * tab tidak beririsan dan masih bisa dibandingkan langsung.
     */
    public function terapkan(Builder $query): Builder
    {
        $ditugaskan = fn (Builder $q): Builder => $q->whereNotNull('master_unit_id');
        $sudahDibalas = fn (Builder $q): Builder => $q->whereHas(
            'pesan',
            fn (Builder $p): Builder => $p->where('peran', 'admin')
        );
        $belumDibalas = fn (Builder $q): Builder => $q->whereDoesntHave(
            'pesan',
            fn (Builder $p): Builder => $p->where('peran', 'admin')
        );

        return match ($this) {
            self::LangsungHumas => $query->whereNull('master_unit_id'),
            self::JawabanUnit => $ditugaskan($query)
                ->where('status', StatusPengaduan::Selesai->value),
            self::MenungguRacikan => $sudahDibalas($ditugaskan($query->aktif())),
            self::MenungguTriase => $belumDibalas(
                $ditugaskan($query->aktif())->where('status', StatusPengaduan::Diterima->value)
            ),
            self::SedangInvestigasi => $belumDibalas(
                $ditugaskan($query->aktif())->where('status', StatusPengaduan::Diproses->value)
            ),
            self::MenungguInfoTambahan => $belumDibalas(
                $ditugaskan($query->aktif())->where('status', StatusPengaduan::Revisi->value)
            ),
        };
    }

    public static function dariNilai(?string $nilai): ?self
    {
        return $nilai === null || $nilai === '' ? null : self::tryFrom($nilai);
    }
}
