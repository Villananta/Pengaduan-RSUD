<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PesanPengaduan extends Model
{
    protected $table = 'pesan_pengaduan';

    protected $fillable = [
        'pengaduan_id',
        'peran',
        'isi',
        'lampiran',
    ];

    public function pengaduan(): BelongsTo
    {
        return $this->belongsTo(Pengaduan::class);
    }

    public function dariAdmin(): bool
    {
        return $this->peran === 'admin';
    }

    public function adaLampiran(): bool
    {
        return filled($this->lampiran);
    }

    public function urlLampiran(): string
    {
        return Storage::url($this->lampiran);
    }

    // Gambar ditampilkan langsung di dalam gelembung pesan, berkas lain lewat tautan unduh.
    public function lampiranGambar(): bool
    {
        return str($this->lampiran)->endsWith(['.jpg', '.jpeg', '.png']);
    }
}
