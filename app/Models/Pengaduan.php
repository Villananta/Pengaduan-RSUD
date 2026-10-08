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

    /**
     * Nilai bawaan yang ikut dikirim saat insert.
     *
     * Tanpa ini atribut kasus_berat kosong pada model yang baru dibuat,
     * sehingga perhitungan SLA menerima null dan gagal saat dipakai.
     */
    protected $attributes = [
        'kasus_berat' => false,
    ];

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
        'kasus_berat',
        'draf_jawaban',
    ];

    protected $casts = [
        'waktu_kejadian' => 'datetime',
        'selesai_at' => 'datetime',
        'kasus_berat' => 'boolean',
        'kasus_berat_at' => 'datetime',
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

    /**
     * Pindahkan tiket ke tahap lain dan catat jejak auditnya.
     *
     * Penulisan status, waktu selesai, dan baris riwayat harus atomik:
     * kalau salah satu gagal, halaman detail akan menampilkan tahap yang
     * tidak pernah tercatat di riwayat.
     *
     * Method ini tidak memeriksa apakah perpindahan itu wajar; pemeriksaannya
     * ada di App\Support\TindakLanjutPengaduan sesuai konteks tiap aksi.
     */
    public function pindahTahap(StatusPengaduan $tujuan, ?string $catatan = null, ?int $adminId = null): void
    {
        $sebelumnya = $this->status;

        $this->status = $tujuan;
        $this->selesai_at = $tujuan->selesai() ? now() : null;
        $this->save();

        $this->riwayatStatus()->create([
            'dari' => $sebelumnya,
            'ke' => $tujuan,
            'catatan' => $catatan,
            'admin_id' => $adminId,
        ]);
    }

    /**
     * Nyalakan atau matikan penandaan kasus berat.
     *
     * Waktu penandaan ikut disimpan karena melihat kapan masalahnya pertama
     * kali menaikkan batas SLA jauh lebih berguna daripada melihatnya saja.
     */
    public function tandaiKasusBerat(bool $aktif, ?int $adminId = null): void
    {
        if ($aktif === $this->kasus_berat) {
            return;
        }

        $this->kasus_berat = $aktif;
        $this->kasus_berat_at = $aktif ? now() : null;
        $this->save();

        $this->riwayatStatus()->create([
            'dari' => $this->status,
            'ke' => $this->status,
            'catatan' => $aktif
                ? 'Pengaduan ditandai sebagai kasus berat oleh admin humas.'
                : 'Penandaan kasus berat dilepas oleh admin humas.',
            'admin_id' => $adminId,
        ]);
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
        return $this->status->selesai()
            ? 100
            : Sla::persen($this->status->tahap(), $this->kasus_berat);
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

    /**
     * Target penyelesaian pengaduan.
     *
     * Tiket kasus berat memakai target yang lebih panjang karena perlu telaah
     * komite etik, sehingga batas 12 hari kerja tidak lagi berlaku padanya.
     */
    public function targetSla(): CarbonInterface
    {
        return Sla::target($this->created_at, $this->kasus_berat);
    }

    /** Zona SLA berdasarkan waktu berjalan atau waktu penyelesaian. */
    public function zonaSla(): ZonaSla
    {
        return Sla::zona($this->created_at, $this->selesai_at, $this->kasus_berat);
    }

    /** Sisa hari kerja menuju target, nol bila sudah lewat atau selesai. */
    public function sisaHariSla(): int
    {
        return $this->status->selesai()
            ? 0
            : Sla::sisaHariKerja($this->created_at, null, $this->kasus_berat);
    }

    /** Ringkasan SLA satu kalimat untuk ditampilkan pada panel. */
    public function ringkasSla(): string
    {
        if ($this->status->selesai()) {
            return 'Selesai pada '.($this->selesai_at?->format('d M Y') ?? 'tanggal tidak tercatat');
        }

        $sisa = $this->sisaHariSla();
        $target = $this->targetSla()->format('d M Y');

        // Sisa hari nol tidak otomatis berarti lewat: di hari terakhir yang
        // masih tepat waktu sisa juga nol. Penentu terlambatnya adalah zona
        // SLA, jadi kalimat "melewati target" hanya muncul setelah zona benar
        // benar Terlambat, bukan pada hari terakhir sebelum deadline.
        if ($this->zonaSla() === ZonaSla::Terlambat) {
            return 'Melewati target SLA '.$this->totalHariKerjaSla().' hari kerja';
        }

        if ($sisa === 0) {
            return 'Tenggat penyelesaian hari ini, target '.$target;
        }

        return 'Sisa '.$sisa.' hari kerja, target '.$target;
    }

    /** Total hari kerja SLA tiket ini, termasuk tambahan kasus berat. */
    public function totalHariKerjaSla(): int
    {
        return Sla::totalHariKerja($this->kasus_berat);
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
