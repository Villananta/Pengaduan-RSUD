<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Satu baris percakapan pada kolom chat pelapor dan admin humas.
 *
 * Satu pesan boleh membawa lebih dari satu berkas, jadi kolom lampiran
 * dibaca sebagai daftar path, bukan sebagai satu path tunggal seperti
 * lampiran pada tiketnya.
 */
class PesanPengaduan extends Model
{
    protected $table = 'pesan_pengaduan';

    protected $fillable = [
        'pengaduan_id',
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
     * Pesan yang berasal dari sisi humas, jadi ditulis di gelembung kanan.
     *
     * Membedakan dari dariAdmin(): sapaan pembuka juga tampil di sisi humas,
     * hanya saja bukan bukti unit sudah menjawab.
     */
    public function dariHumas(): bool
    {
        return $this->peran !== 'pelapor';
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
