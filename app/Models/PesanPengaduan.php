<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Satu baris percakapan pengaduan: chat pelapor maupun koordinasi unit.
 *
 * Kolom kanal memisahkan dua jalur percakapan pada satu tiket. Jalur
 * 'pelapor' berisi pesan antara pelapor dan admin humas yang boleh dibaca
 * di halaman lacak; jalur 'unit' berisi koordinasi humas dengan PIC unit
 * yang tidak boleh bocor ke pelapor. Peran tetap dipakai untuk menentukan
 * pengirim setiap pesan di dalam jalurnya.
 *
 * Satu pesan boleh membawa lebih dari satu berkas, jadi kolom lampiran
 * dibaca sebagai daftar path, bukan sebagai satu path tunggal seperti
 * lampiran pada tiketnya.
 */
class PesanPengaduan extends Model
{
    /** Jalur percakapan pelapor dengan admin humas. */
    public const KANAL_PELAPOR = 'pelapor';

    /** Jalur koordinasi internal admin humas dengan PIC unit. */
    public const KANAL_UNIT = 'unit';

    /**
     * Peran yang dihitung sebagai jawaban sudah masuk.
     *
     * Daftar ini dipakai oleh scope maupun method baca supaya jumlah "balasan
     * unit" di daftar pengaduan, monitor, dan dashboard tidak mungkin berbeda
     * satu sama lain. 'admin' tetap ikut karena sejak awal balasan yang masuk
     * lewat konsol humas memakai peran itu; 'unit' ditambahkan begitu jawaban
     * bisa ditulis PIC unit langsung dari halamannya sendiri.
     */
    public const PERAN_JAWABAN = ['admin', 'unit'];

    protected $table = 'pesan_pengaduan';

    protected $fillable = [
        'pengaduan_id',
        'kanal',
        'peran',
        'isi',
        'lampiran',
    ];

    protected $casts = [
        'lampiran' => 'array',
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
     * Balasan yang ditulis PIC unit sendiri lewat halaman unit.
     *
     * Dipisah dari dariAdmin() karena pengirimnya memang berbeda entitas:
     * pesan ini tidak boleh terbaca sebagai suara humas di depan pelapor,
     * tapi tetap harus dihitung sebagai jawaban yang menunggu racikan humas.
     */
    public function dariUnit(): bool
    {
        return $this->peran === 'unit';
    }

    /** True bila pesan ini termasuk jawaban yang sudah masuk. */
    public function jawaban(): bool
    {
        return in_array($this->peran, self::PERAN_JAWABAN, true);
    }

    /**
     * Pesan milik kanal koordinasi humas dan unit.
     *
     * Dipisah dari dariUnit() karena jalur unit bisa memuat pesan dari kedua
     * pihak: jawaban PIC unit sekaligus catatan admin humas yang tidak pernah
     * terlihat oleh pelapor.
     */
    public function jalurUnit(): bool
    {
        return $this->kanal === self::KANAL_UNIT;
    }

    /** Kebalikan jalurUnit(): pesan yang boleh dibaca pelapor di halaman lacak. */
    public function jalurPelapor(): bool
    {
        return $this->kanal === self::KANAL_PELAPOR;
    }

    /**
     * Sinyal "jawaban sudah masuk" untuk withExists() dan hitungan lain.
     *
     * Satu scope dipakai di semua tempat karena enam file menghitung angka
     * yang sama; kalau aturannya ditulis ulang di tiap file, satu perubahan
     * peran bisa membuat daftar dan dashboard tidak lagi sejalan.
     */
    public function scopeJawaban(Builder $query): Builder
    {
        return $query->whereIn('peran', self::PERAN_JAWABAN);
    }

    /** Batasi query ke pesan kanal koordinasi humas dan unit. */
    public function scopeJalurUnit(Builder $query): Builder
    {
        return $query->where('kanal', self::KANAL_UNIT);
    }

    /** Batasi query ke pesan kanal pelapor yang boleh dibaca di halaman lacak. */
    public function scopeJalurPelapor(Builder $query): Builder
    {
        return $query->where('kanal', self::KANAL_PELAPOR);
    }

    /**
     * Pesan yang berasal dari sisi humas, jadi ditulis di gelembung kanan.
     *
     * Membedakan dari dariAdmin(): sapaan pembuka juga tampil di sisi humas,
     * hanya saja bukan bukti unit sudah menjawab. Pesan PIC unit sengaja
     * tidak ikut, karena dia digambar di sisi berlawanan dengan label sendiri.
     */
    public function dariHumas(): bool
    {
        return $this->peran === 'pembuka' || $this->peran === 'admin';
    }

    /** Daftar berkas lampiran yang menyertai pesan ini. */
    public function daftarLampiran(): array
    {
        return array_values(array_filter($this->lampiran ?? []));
    }

    public function adaLampiran(): bool
    {
        return $this->daftarLampiran() !== [];
    }

    public function urlLampiran(string $path): string
    {
        return Storage::disk(config('pengaduan.disk_lampiran', 'public'))->url($path);
    }

    // Gambar ditampilkan langsung di dalam gelembung pesan, berkas lain lewat tautan unduh.
    public function lampiranGambar(string $path): bool
    {
        return str($path)->endsWith(['.jpg', '.jpeg', '.png']);
    }

    /**
     * Simpan berkas lampiran pesan ke disk lampiran.
     *
     * Pengembaliannya null, bukan array kosong, saat tidak ada berkas sama
     * sekali: kolom lampiran yang berisi "[]" membingungkan saat dibaca
     * manual di luar model, sedangkan null terbaca jelas sebagai tanpa lampiran.
     *
     * @param  array<int, UploadedFile|null>  $berkas
     * @return array<int, string>|null
     */
    public static function simpanBerkas(array $berkas): ?array
    {
        $daftar = [];

        foreach ($berkas as $file) {
            if ($file === null) {
                continue;
            }

            $path = $file->store('lampiran', config('pengaduan.disk_lampiran', 'public'));

            if ($path !== false) {
                $daftar[] = $path;
            }
        }

        return $daftar === [] ? null : $daftar;
    }
}
