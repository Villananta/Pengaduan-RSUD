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

    /**
     * Balasan unit yang sudah disusun admin humas.
     *
     * Pesan pembuka otomatis memakai peran 'pembuka', jadi tidak ikut di sini.
     * Inilah sinyal yang dipakai daftar pengaduan, monitor, dan dashboard
     * untuk menentukan apakah sebuah tiket sudah mendapat jawaban unit. Kalau
     * pembuka ikut terhitung, setiap tiket baru langsung terbaca terjawab.
     */
    public function dariAdmin(): bool
    {
        return $this->peran === 'admin';
    }

    /**
     * Pesan yang berasal dari sisi humas, jadi ditulis di gelembung kanan.
     *
     * Membedakan dari dariAdmin(): sapaan pembuka juga tampil di sisi humas,
     * hanya saja bukan bukti unit sudah menjawab.
     */
    public function dariHumas(): bool
    {
        return $this->peran !== 'pelapor';
    }

    public function adaLampiran(): bool
    {
        return filled($this->lampiran);
    }

    public function urlLampiran(): string
    {
        return Storage::disk(config('pengaduan.disk_lampiran', 'public'))->url($this->lampiran);
    }

    // Gambar ditampilkan langsung di dalam gelembung pesan, berkas lain lewat tautan unduh.
    public function lampiranGambar(): bool
    {
        return str($this->lampiran)->endsWith(['.jpg', '.jpeg', '.png']);
    }
}
