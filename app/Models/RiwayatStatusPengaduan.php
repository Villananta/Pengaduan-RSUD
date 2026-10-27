<?php

namespace App\Models;

use App\Enums\StatusPengaduan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak audit perubahan tahap pengaduan.
 *
 * Setiap kali admin memindahkan tahap, satu baris riwayat tersimpan
 * sehingga jawabannya "siapa yang mengubah dan kapan" selalu bisa ditelusuri.
 */
class RiwayatStatusPengaduan extends Model
{
    protected $table = 'riwayat_status_pengaduan';

    protected $fillable = [
        'pengaduan_id',
        'dari',
        'ke',
        'catatan',
        'admin_id',
        'is_nudge',
        'is_keparahan',
        'is_kategori',
    ];

    protected $casts = [
        'dari' => StatusPengaduan::class,
        'ke' => StatusPengaduan::class,
        'is_nudge' => 'boolean',
        'is_keparahan' => 'boolean',
        'is_kategori' => 'boolean',
    ];

    public function pengaduan(): BelongsTo
    {
        return $this->belongsTo(Pengaduan::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // Baris pertama riwayat adalah tahap awal saat pengaduan dibuat.
    public function dariAwal(): bool
    {
        return $this->dari === null;
    }

    /**
     * True bila baris ini mencatat penandaan kasus berat, bukan perpindahan tahap.
     *
     * Penandaan kasus berat ditulis dengan tahap asal dan tujuan yang sama,
     * sehingga tidak ada kolom yang bisa membedakannya dari disposisi biasa.
     * Yang tersisa hanya kalimat bawaan yang ditulis sistem, dan itulah yang
     * dipakai untuk menyaringnya dari panel arahan disposisi halaman unit.
     */
    public function perubahanKasusBerat(): bool
    {
        return in_array($this->catatan, [
            Pengaduan::CATATAN_KASUS_BERAT_AKTIF,
            Pengaduan::CATATAN_KASUS_BERAT_LEPAS,
        ], true);
    }

    /**
     * True bila baris ini mencatat perubahan tingkat keparahan.
     *
     * Tahap asal dan tujuan pada baris ini sama, sama seperti penandaan
     * kasus berat. Penanda kolom dipakai supaya halaman riwayat tidak
     * salah menyebutnya sebagai disposisi.
     */
    public function perubahanKeparahan(): bool
    {
        return (bool) $this->is_keparahan;
    }

    /**
     * True bila baris ini mencatat penetapan atau perubahan kategori.
     *
     * Sama seperti keparahan, baris kategori tidak memindahkan tahap. Halaman
     * riwayat memakai penanda ini supaya aksinya tidak disebut disposisi.
     */
    public function perubahanKategori(): bool
    {
        return (bool) $this->is_kategori;
    }
}
