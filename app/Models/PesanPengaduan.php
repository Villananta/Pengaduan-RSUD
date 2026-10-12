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

    /** Peran pesan yang ditulis PIC unit sendiri. */
    public const PERAN_UNIT = 'unit';

    /**
     * Peran yang dihitung sebagai "sudah ada balasan masuk".
     *
     * Daftar ini sengaja lebar karena pesan berperan admin punya dua arti:
     * balasan humas kepada pelapor, atau jawaban unit yang dulu dititipkan
     * lewat konsol humas. Angka telaah di dashboard memakai arti lebar ini,
     * sedangkan status investigasi unit butuh sinyal yang tegas, sehingga
     * keduanya tidak boleh berbagi satu daftar peran.
     */
    public const PERAN_JAWABAN = ['admin', self::PERAN_UNIT];

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
     * True bila pesan ditulis humas lewat konsol admin.
     *
     * Pesan pembuka otomatis memakai peran 'pembuka', jadi tidak ikut di sini.
     * Penanda ini memisahkan suara humas dari suara PIC unit; ia bukan bukti
     * unit sudah menjawab, karena humas memakai peran yang sama saat membalas
     * pelapor. Sinyal "unit sudah menjawab" ada di dariUnit().
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
        return $this->peran === self::PERAN_UNIT;
    }

    /** True bila pesan ini termasuk balasan yang sudah masuk, dari humas maupun unit. */
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
     * Sinyal "sudah ada balasan masuk" untuk angka telaah humas.
     *
     * Sifatnya lebar karena menghitung balasan humas dan jawaban unit
     * sekaligus. Status investigasi dan papan disposisi tidak boleh memakai
     * scope ini: balasan humas kepada pelapor akan ikut terbaca seolah-olah
     * unit sudah menjawab. Untuk keduanya pakai scopeJawabanUnit().
     */
    public function scopeJawaban(Builder $query): Builder
    {
        return $query->whereIn('peran', self::PERAN_JAWABAN);
    }

    /**
     * Sinyal "PIC unit sudah menjawab" untuk status investigasi dan papan disposisi.
     *
     * Ditempatkan berdampingan dengan scopeJawaban() supaya beda keduanya
     * terbaca langsung: yang satu menghitung keterlibatan humas, yang ini
     * menghitung jawaban unit yang benar-benar masuk.
     */
    public function scopeJawabanUnit(Builder $query): Builder
    {
        return $query->where('peran', self::PERAN_UNIT);
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
        return Storage::disk(config('pengaduan.disk_lampiran'))->url($path);
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

            $path = $file->store('lampiran', config('pengaduan.disk_lampiran'));

            if ($path !== false) {
                $daftar[] = $path;
            }
        }

        return $daftar === [] ? null : $daftar;
    }
}
