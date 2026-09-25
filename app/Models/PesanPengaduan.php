<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesanPengaduan extends Model
{
    protected $table = 'pesan_pengaduan';

    protected $fillable = [
        'pengaduan_id',
        'peran',
        'isi',
    ];

    public function pengaduan(): BelongsTo
    {
        return $this->belongsTo(Pengaduan::class);
    }

    public function dariAdmin(): bool
    {
        return $this->peran === 'admin';
    }
}
