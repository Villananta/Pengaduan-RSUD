<?php

namespace App\Models;

use App\Enums\DisposisiUnit;
use Database\Factories\MasterUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterUnit extends Model
{
    /** @use HasFactory<MasterUnitFactory> */
    use HasFactory;

    protected $table = 'master_units';

    protected $fillable = [
        'kode',
        'nama',
        'koneksi_simrs',
        'koneksi_simrs_terakhir',
        'disposisi',
    ];

    protected $casts = [
        'koneksi_simrs' => 'boolean',
        'koneksi_simrs_terakhir' => 'datetime',
        'disposisi' => DisposisiUnit::class,
    ];

    // Pengaduan yang ditugaskan ke unit ini.
    public function pengaduan(): HasMany
    {
        return $this->hasMany(Pengaduan::class, 'master_unit_id');
    }

    /** Nama unit lengkap dengan kode, misalnya "Instalasi Farmasi Pusat [IFP-01]". */
    public function namaLengkap(): string
    {
        return $this->nama.' ['.$this->kode.']';
    }

    /** True bila koneksi SIMRS terakhir masih dalam batas 15 menit. */
    public function koneksiAktif(): bool
    {
        if (! $this->koneksi_simrs || $this->koneksi_simrs_terakhir === null) {
            return false;
        }

        return $this->koneksi_simrs_terakhir->greaterThanOrEqualTo(now()->subMinutes(15));
    }
}
