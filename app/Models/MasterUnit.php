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

    /**
     * Batas heartbeat SIMRS yang masih dianggap hidup, dalam menit.
     *
     * Semua definisi "unit terhubung" di aplikasi, baik yang dihitung dalam
     * PHP maupun di dalam query, wajib memakai angka ini. Kalau ada satu
     * tempat menulis 15 secara lepas, angka unit terhubung bisa berbeda
     * antara kartu ringkasan dan filter saring yang berdampingan di layar
     * yang sama.
     */
    public const BATAS_KONEKSI = 15;

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

    /** True bila koneksi SIMRS terakhir masih dalam batas menit yang berlaku. */
    public function koneksiAktif(): bool
    {
        if (! $this->koneksi_simrs || $this->koneksi_simrs_terakhir === null) {
            return false;
        }

        return $this->koneksi_simrs_terakhir->greaterThanOrEqualTo(now()->subMinutes(self::BATAS_KONEKSI));
    }

    // Kondisi koneksi unit dalam bentuk query, supaya kartu ringkasan dan
    // filter saring memakai rumus yang persis sama.

    /**
     * Unit yang koneksi SIMRS-nya dianggap hidup.
     *
     * Di sini syaratnya keseluruhan: bendera koneksi aktif, heartbeat pernah
     * terkirim, heartbeat itu belum lewat batas, dan unitnya memang tidak
     * ditandai putus. Tanpa syarat ketiga, unit yang SIMRS-nya berhenti
     * mengirim heartbeat tetap dihitung terpadahal sudah tidak bisa
     * dihubungi. Syarat keempat dipakai supaya kelas ini dan kelas terputus
     * tidak pernah bisa memilih baris yang sama dua kali.
     */
    public function scopeTerhubung(Builder $query): Builder
    {
        return $query->where('koneksi_simrs', true)
            ->whereNotNull('koneksi_simrs_terakhir')
            ->where('koneksi_simrs_terakhir', '>=', now()->subMinutes(self::BATAS_KONEKSI))
            ->where('disposisi', '!=', DisposisiUnit::Terputus->value);
    }

    /**
     * Unit yang koneksinya dianggap putus.
     *
     * Dua kelompok digabung, karena sama-sama membuat unit tidak bisa
     * dihubungi: unit yang memang ditandai putus, dan unit yang pernah
     * mengirim heartbeat tapi kini sudah basi. Unit yang belum pernah sama
     * sekali mengirim heartbeat sengaja tidak ikut — belum pernah terhubung
     * bukan berarti terputus, dan dicampur jadi satu akan membuat unit baru
     * langsung terhitung rusak.
     */
    public function scopeTerputus(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('disposisi', DisposisiUnit::Terputus->value)
                ->orWhere(function (Builder $q): void {
                    $q->whereNotNull('koneksi_simrs_terakhir')
                        ->where(function (Builder $q): void {
                            $q->where('koneksi_simrs', false)
                                ->orWhere('koneksi_simrs_terakhir', '<', now()->subMinutes(self::BATAS_KONEKSI));
                        });
                });
        });
    }

    /**
     * Unit yang belum pernah ditiketkan.
     *
     * Tidak ditandai lewat keadaan `disposisi`, karena kolom itu punya nilai
     * bawaan sehingga tidak pernah kosong. Dicari dari dua bukti yang tidak
     * bisa bohong: belum pernah menerima tiket, dan belum pernah menyimpan
     * heartbeat. Syarat kedua yang membedakannya dari unit terputus, sesuai
     * maksud pemisahan di komentar filter saring.
     */
    public function scopeBelumDitugaskan(Builder $query): Builder
    {
        return $query->whereNull('koneksi_simrs_terakhir')
            ->where('disposisi', '!=', DisposisiUnit::Terputus->value)
            ->doesntHave('pengaduan');
    }
}
