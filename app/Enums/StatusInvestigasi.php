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
    // Tiket sudah masuk ke unit dan masih ditelusuri, apa pun tahapnya
    // di sisi humas termasuk pelengkapan pelapor.
    case SedangInvestigasi = 'sedang_investigasi';

    // Unit sudah menjawab, tinggal diracik dan diterbitkan humas.
    case MenungguRacikan = 'menunggu_racikan';

    // Arsip unit sudah lengkap, tiket ditutup.
    case JawabanUnit = 'jawaban_unit';

    // Tiket tidak pernah ditugaskan ke instalasi teknis.
    case LangsungHumas = 'langsung_humas';

    public function label(): string
    {
        return match ($this) {
            self::SedangInvestigasi => 'Sedang Investigasi',
            self::MenungguRacikan => 'Menunggu Racikan Humas',
            self::JawabanUnit => 'Jawaban Unit Masuk',
            self::LangsungHumas => 'Ditangani Langsung Humas',
        };
    }

    /** Uraian singkat kondisi investigasi, ditampilkan di dalam sel tabel. */
    public function ringkas(): string
    {
        return match ($this) {
            self::SedangInvestigasi => 'PIC unit sedang menelusuri kronologi, berkas, dan hasil pemeriksaan.',
            self::MenungguRacikan => 'Draf klarifikasi unit sudah masuk dan siap diracik humas.',
            self::JawabanUnit => 'Arsip unit lengkap, tiket ditutup dengan jawaban resmi.',
            self::LangsungHumas => 'Klarifikasi dikerjakan Customer Care humas tanpa disposisi unit teknis.',
        };
    }

    /**
     * Uraian satu baris untuk kolom tabel yang sempit.
     *
     * Versi panjang di ringkas() tetap dipakai sebagai teks alternatif pada
     * sel, sedangkan yang tampil hanya penjelas singkatnya.
     */
    public function ringkasPendek(): string
    {
        return match ($this) {
            self::SedangInvestigasi => 'PIC unit menelusuri',
            self::MenungguRacikan => 'Draf unit sudah masuk',
            self::JawabanUnit => 'Arsip unit tersimpan',
            self::LangsungHumas => 'Tanpa disposisi unit',
        };
    }

    /** Warna lencana pada tab filter dan sel tabel. */
    public function nada(): string
    {
        return match ($this) {
            self::SedangInvestigasi => 'bg-surface-container text-on-surface',
            self::MenungguRacikan, self::JawabanUnit => 'bg-secondary-container text-on-secondary-container',
            self::LangsungHumas => 'bg-surface-container-low text-on-surface-variant',
        };
    }

    /** Warna titik penanda pada deretan tab filter Lapis 2. */
    public function titik(): string
    {
        return match ($this) {
            self::SedangInvestigasi => 'bg-on-tertiary-container',
            self::MenungguRacikan, self::JawabanUnit => 'bg-secondary',
            self::LangsungHumas => 'bg-surface-tint',
        };
    }

    /**
     * Status investigasi satu tiket.
     *
     * Urutan pemeriksaan penting: tiket tanpa master_unit_id selalu
     * berstatus ditangani langsung humas, tiket yang sudah ditutup selalu
     * berstatus arsip unit, dan hanya tiket aktif bertaut unit yang bisa
     * berstatus menunggu racikan atau sedang ditelusuri unit.
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
            StatusPengaduan::Diterima,
            StatusPengaduan::Diproses,
            StatusPengaduan::Revisi => self::SedangInvestigasi,
            StatusPengaduan::Selesai => self::JawabanUnit,
        };
    }

    /**
     * Status investigasi yang ditandai hijau pada tabel.
     *
     * Dipisah dari kondisi "butuh actuation" karena dua syarat ini tidak
     * selalu sama: tiket yang sudah ditutup tidak lagi butuh tindakan, tetapi
     * tetap ditandai sebagai tahap yang mencapai target.
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
            self::SedangInvestigasi => $belumDibalas($ditugaskan($query->aktif()))
                ->whereIn('status', [
                    StatusPengaduan::Diterima->value,
                    StatusPengaduan::Diproses->value,
                    StatusPengaduan::Revisi->value,
                ]),
        };
    }

    public static function dariNilai(?string $nilai): ?self
    {
        return $nilai === null || $nilai === '' ? null : self::tryFrom($nilai);
    }
}
