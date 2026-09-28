<?php

namespace App\Models;

use App\Enums\KategoriPengaduan;
use App\Enums\StatusPengaduan;
use App\Enums\ZonaSla;
use App\Support\Sla;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Pengaduan extends Model
{
    use HasFactory;

    protected $table = 'pengaduan';

    protected $fillable = [
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
        'master_unit_id',
    ];

    protected $casts = [
        'waktu_kejadian' => 'datetime',
        'selesai_at' => 'datetime',
        'lampiran' => 'array',
        'kategori' => KategoriPengaduan::class,
        'status' => StatusPengaduan::class,
    ];

    /**
     * Setiap pengaduan baru selalu meninggalkan jejak audit tahap awal,
     * termasuk saat dibuat lewat factory, seeder, atau console command.
     */
    protected static function booted(): void
    {
        static::created(function (self $pengaduan): void {
            $pengaduan->riwayatStatus()->create([
                'dari' => null,
                'ke' => $pengaduan->status,
            ]);
        });
    }

    /**
     * Buat pengaduan baru dengan kode tiket yang dibuat di dalam sistem.
     *
     * Kode tiket sengaja tidak ada di $fillable sehingga tidak bisa
     * diisi massal dari input pengguna; kode dibuat di dalam sistem.
     *
     * @param  array<string, mixed>  $data
     */
    public static function buat(array $data, string $kodeTiket, ?StatusPengaduan $status = null): self
    {
        $pengaduan = new self;
        $pengaduan->fill($data);
        $pengaduan->kode_tiket = $kodeTiket;
        $pengaduan->status = $status ?? StatusPengaduan::Diterima;
        $pengaduan->save();

        return $pengaduan;
    }

    // Kolom chat antara pelapor dan admin humas.
    public function pesan(): HasMany
    {
        return $this->hasMany(PesanPengaduan::class)->oldest();
    }

    /**
     * Unit MASTER_UNITS yang ditugaskan menangani pengaduan ini.
     *
     * Hubungan ini boleh kosong karena kolomnya nullable, sehingga
     * pengaduan lama yang hanya menyimpan nama unit bebas tetap bisa
     * dibaca tanpa harus di-backfill. Nama unit pada kolom teks tetap
     * dipertahankan sebagai sumber nilai yang diketik pelapor.
     */
    public function masterUnit(): BelongsTo
    {
        return $this->belongsTo(MasterUnit::class);
    }

    /** Nama unit siap tampil, memakai kode MASTER_UNITS bila tertaut. */
    public function namaUnit(): string
    {
        return $this->masterUnit?->namaLengkap() ?? $this->unit;
    }

    // Jejak audit perubahan tahap pengaduan.
    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatusPengaduan::class)->oldest();
    }

    public function scopeSelesai(Builder $query): Builder
    {
        return $query->where('status', StatusPengaduan::Selesai->value);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', '!=', StatusPengaduan::Selesai->value);
    }

    /** Progres tahap terhadap standar pelayanan, dalam persen. */
    public function progresPersen(): int
    {
        return $this->status->selesai() ? 100 : Sla::persen($this->status->tahap());
    }

    /**
     * Nama sub-status unit sesuai Iterable atas tahap pengaduan.
     *
     * Empat sub-status pada konsol admin dipetakan dari empat tahap
     * pengaduan yang sudah ada, sehingga tidak ada tahap kelima yang
     * harus diisi ulang oleh setiap admin.
     */
    public function subStatus(): string
    {
        return $this->status->subStatus();
    }

    /** Tanggal jatuh tempo penyelesaian pengaduan. */
    public function targetSla(): CarbonInterface
    {
        return Sla::target($this->created_at);
    }

    /** Zona SLA berdasarkan waktu berjalan atau waktu penyelesaian. */
    public function zonaSla(): ZonaSla
    {
        return Sla::zona($this->created_at, $this->selesai_at);
    }

    /** Sisa hari kerja menuju target, nol bila sudah lewat atau selesai. */
    public function sisaHariSla(): int
    {
        return $this->status->selesai() ? 0 : Sla::sisaHariKerja($this->created_at);
    }

    /** Ringkasan SLA satu kalimat untuk ditampilkan pada panel. */
    public function ringkasSla(): string
    {
        if ($this->status->selesai()) {
            return 'Selesai pada '.($this->selesai_at?->format('d M Y') ?? 'tanggal tidak tercatat');
        }

        $sisa = $this->sisaHariSla();

        if ($sisa > 0) {
            return 'Sisa '.$sisa.' hari kerja, target '.$this->targetSla()->format('d M Y');
        }

        return 'Melewati target SLA '.Sla::hariKerja().' hari kerja';
    }

    /** Daftar berkas lampiran yang diunggah bersama pengaduan. */
    public function daftarLampiran(): array
    {
        return array_values(array_filter($this->lampiran ?? []));
    }

    public function urlLampiran(string $path): string
    {
        return Storage::disk(config('pengaduan.disk_lampiran', 'public'))->url($path);
    }

    public function namaLampiran(string $path): string
    {
        return basename($path);
    }

    /**
     * Bentuk baku NRM untuk perbandingan.
     *
     * Pelapor boleh mengetik dengan spasi, tanda hubung, atau huruf
     * kecil, sehingga perbandingan harus dinormalkan lebih dulu.
     */
    public static function normalisasiNrm(?string $nrm): string
    {
        return strtoupper(preg_replace('/[^0-9A-Z]/', '', (string) $nrm));
    }

    /** True bila NRM yang diberikan pelapor cocok dengan data pengaduan. */
    public function nrmCocok(?string $nrm): bool
    {
        $dimasukkan = self::normalisasiNrm($nrm);

        return $dimasukkan !== '' && hash_equals(self::normalisasiNrm($this->nrm), $dimasukkan);
    }
}
