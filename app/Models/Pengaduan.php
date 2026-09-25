<?php

namespace App\Models;

use App\Enums\KategoriPengaduan;
use App\Enums\StatusPengaduan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengaduan extends Model
{
    protected $table = 'pengaduan';

    protected $fillable = [
        'kode_tiket',
        'kategori',
        'nama_lengkap',
        'nrm',
        'no_wa',
        'email',
        'alamat',
        'waktu_kejadian',
        'unit',
        'subjek',
        'deskripsi',
        'lampiran',
        'status',
    ];

    protected $casts = [
        'waktu_kejadian' => 'datetime',
        'lampiran' => 'array',
        'kategori' => KategoriPengaduan::class,
        'status' => StatusPengaduan::class,
    ];

    // Kolom chat antara pelapor dan admin humas.
    public function pesan(): HasMany
    {
        return $this->hasMany(PesanPengaduan::class)->oldest();
    }
}
