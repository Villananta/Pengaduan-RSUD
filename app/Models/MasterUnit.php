<?php

namespace App\Models;

use App\Enums\DisposisiUnit;
use App\Enums\KategoriUnit;
use App\Enums\PeranAksesUnit;
use App\Enums\StatusAksesUnit;
use Database\Factories\MasterUnitFactory;
use Illuminate\Database\Eloquent\Builder;
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
        'kategori',
        'pic',
        'jabatan_pic',
        'nip',
        'kontak_wa',
        'ekstensi',
        'jam_layanan',
        'akun_simrs',
        'peran_akses',
        'status_akses',
        'aktif',
        'koneksi_simrs',
        'koneksi_simrs_terakhir',
        'disposisi',
    ];

    protected $casts = [
        'kategori' => KategoriUnit::class,
        'peran_akses' => PeranAksesUnit::class,
        'status_akses' => StatusAksesUnit::class,
        'aktif' => 'boolean',
        'koneksi_simrs' => 'boolean',
        'koneksi_simrs_terakhir' => 'datetime',
        'disposisi' => DisposisiUnit::class,
    ];

    /**
     * Gunakan kolom kode sebagai kunci rute publik.
     *
     * Kode unitlah yang dikenal petugas karena kode itu yang selalu
     * tampil di layar. Dengan menjadikannya route key, binding implicit
     * Laravel bisa langsung mencari berdasarkan kode tanpa perlu
     * pencarian manual di controller.
     */
    public function getRouteKeyName(): string
    {
        return 'kode';
    }

    // Unit yang masih boleh dipilih sebagai tujuan disposisi pengaduan.
    public function scopeAktif(Builder $q): Builder
    {
        return $q->where('aktif', true);
    }

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

    /**
     * True bila unit masih bisa dipilih sebagai tujuan disposisi.
     *
     * Unit non-aktif dan unit yang koneksinya terputus sama-sama tidak
     * boleh ditugaskan, karena keduanya membuat tiket menggantung tanpa
     * satupun pihak yang bisa ditagih.
     */
    public function bisaDitugaskan(): bool
    {
        return $this->aktif && $this->disposisi->siap();
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
