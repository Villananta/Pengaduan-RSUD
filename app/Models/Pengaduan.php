<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];
}
