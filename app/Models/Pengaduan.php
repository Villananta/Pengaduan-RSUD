<?php

namespace App\Models;

use App\Enums\KategoriPengaduan;
use App\Enums\StatusPengaduan;
use App\Enums\TingkatKeparahan;
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

    /**
     * Catatan audit saat penandaan kasus berat dinyalakan.
     *
     * Kalimat ini sekaligus jadi penanda baris riwayat: penandaan kasus berat
     * sengaja tidak mengubah tahap, jadi satu-satunya cara mengenalinya adalah
     * teks bawaannya. Kalau kalimatnya diubah, penanda di
     * RiwayatStatusPengaduan::perubahanKasusBerat() harus ikut diubah.
     */
    public const CATATAN_KASUS_BERAT_AKTIF = 'Pengaduan ditandai sebagai kasus berat oleh admin humas.';

    /** Catatan audit saat penandaan kasus berat dilepas. */
    public const CATATAN_KASUS_BERAT_LEPAS = 'Penandaan kasus berat dilepas oleh admin humas.';

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
        'email_verified_at',
        'email_otp',
        'email_otp_expires_at',
        'alamat',
        'waktu_kejadian',
        'unit',
        'subjek',
        'deskripsi',
        'lampiran',
        'status',
        'master_unit_id',
        'kasus_berat',
        'tingkat_keparahan',
        'draf_jawaban',
        'akses_token',
        'akses_token_expires_at',
    ];

    protected $casts = [
        'waktu_kejadian' => 'datetime',
        'selesai_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'email_otp_expires_at' => 'datetime',
        'kasus_berat' => 'boolean',
        'kasus_berat_at' => 'datetime',
        'tingkat_keparahan' => TingkatKeparahan::class,
        'akses_token_expires_at' => 'datetime',
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
     * ada di App\Services\TahapService sesuai konteks tiap aksi.
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
                ? self::CATATAN_KASUS_BERAT_AKTIF
                : self::CATATAN_KASUS_BERAT_LEPAS,
            'admin_id' => $adminId,
        ]);
    }

    /**
     * Tetapkan tingkat keparahan tiket dan catat perubahannya.
     *
     * Keparahan sengaja tidak menyentuh target SLA: yang berubah hanya
     * penanda warna dan filter di konsol, supaya keputusan admin tidak
     * menggeser tenggat yang sudah dijanjikan ke pelapor. Baris riwayat
     * memakai tahap asal dan tujuan yang sama karena status tidak berubah.
     */
    public function aturTingkatKeparahan(?TingkatKeparahan $tingkat, ?int $adminId = null): void
    {
        if ($tingkat === $this->tingkat_keparahan) {
            return;
        }

        $sebelumnya = $this->tingkat_keparahan;
        $this->tingkat_keparahan = $tingkat;
        $this->save();

        $this->riwayatStatus()->create([
            'dari' => $this->status,
            'ke' => $this->status,
            'catatan' => 'Tingkat keparahan diubah dari '
                .($sebelumnya?->label() ?? 'Belum Ditetapkan')
                .' ke '
                .($tingkat?->label() ?? 'Belum Ditetapkan')
                .'.',
            'admin_id' => $adminId,
            'is_keparahan' => true,
        ]);
    }

    /**
     * Tiket yang sudah diteruskan Humas, bukan sekadar tertaut ke unit.
     *
     * Pilihan unit pada formulir pelapor sudah mengisi master_unit_id sejak
     * tiket masih 'Diterima', padahal Humas belum memprosesnya. Semua query
     * sisi unit memakai penyaring ini supaya antrean unit tidak diisi tiket
     * yang keputusannya masih di tangan Humas.
     */
    public function scopeSudahDiteruskanHumas(Builder $query): Builder
    {
        return $query->where('status', '!=', StatusPengaduan::Diterima->value);
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

    /**
     * True bila investigasi unit atas tiket ini sudah lewat batas hari kerja.
     *
     * Tiket yang ditangani humas langsung tidak punya investigasi unit, jadi
     * umurnya yang panjang tidak pernah dihitung sebagai keterlambatan unit.
     * Syarat master_unit_id ditahan di sini supaya daftar, kartu, dan detail
     * tiket tidak lagi berbeda pendapat soal tiket mana yang terlambat.
     */
    public function lewatInvestigasiUnit(): bool
    {
        return $this->master_unit_id !== null
            && Sla::lewatInvestigasi($this->created_at);
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
            return 'Selesai pada '.($this->selesai_at?->translatedFormat('d M Y') ?? 'tanggal tidak tercatat');
        }

        $sisa = $this->sisaHariSla();
        $target = $this->targetSla()->translatedFormat('d M Y');

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
        $disk = config('pengaduan.disk_lampiran');

        // Untuk keamanan, arahkan melalui route terproteksi
        if ($disk === 'public' || $disk === 'local') {
            return route('lampiran.pengaduan', [
                'kode' => $this->kode_tiket,
                'file' => basename($path),
            ]);
        }

        // Untuk disk cloud (s3/r2), masih bisa pakai URL signed jika dikonfigurasi
        try {
            return Storage::disk($disk)->temporaryUrl(
                $path,
                now()->addMinutes(60)
            );
        } catch (\Throwable $e) {
            return route('lampiran.pengaduan', [
                'kode' => $this->kode_tiket,
                'file' => basename($path),
            ]);
        }
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

    /**
     * Generate token akses acak untuk pelapor.
     *
     * Token 64 karakter (32 byte hex) yang unik di database.
     * Digunakan untuk akses permanen (30 hari) tanpa harus login ulang.
     */
    public function generateAksesToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (self::where('akses_token', $token)->exists());

        $this->akses_token = $token;
        $this->akses_token_expires_at = now()->addDays(30);
        $this->save();

        return $token;
    }

    /**
     * Cek apakah token akses masih valid (belum expired).
     */
    public function validAksesToken(?string $token = null): bool
    {
        $token = $token ?? $this->akses_token;

        if (! $token || $this->akses_token !== $token) {
            return false;
        }

        return $this->akses_token_expires_at?->isFuture() ?? false;
    }

    /**
     * Cari tiket via token akses (untuk middleware/controller).
     */
    public static function findByValidAksesToken(string $token): ?self
    {
        $pengaduan = self::where('akses_token', $token)->first();

        if (! $pengaduan || ! $pengaduan->validAksesToken($token)) {
            return null;
        }

        return $pengaduan;
    }

    /**
     * Hapus token akses (logout/revoke).
     */
    public function revokeAksesToken(): void
    {
        $this->akses_token = null;
        $this->akses_token_expires_at = null;
        $this->save();
    }

    /**
     * Generate OTP 6 digit untuk verifikasi email.
     */
    public function generateEmailOtp(): string
    {
        $otp = (string) random_int(100000, 999999);

        $this->email_otp = $otp;
        $this->email_otp_expires_at = now()->addMinutes(15);
        $this->save();

        return $otp;
    }

    /**
     * Cek apakah OTP email valid.
     */
    public function verifyEmailOtp(string $otp): bool
    {
        if (! $this->email_otp || ! hash_equals((string) $this->email_otp, (string) $otp)) {
            return false;
        }

        if (! $this->email_otp_expires_at?->isFuture()) {
            return false;
        }

        // OTP valid, tandai email terverifikasi
        $this->email_verified_at = now();
        $this->email_otp = null;
        $this->email_otp_expires_at = null;

        // Rotasi token akses: email sekarang terverifikasi, token lama dicabut
        // dan token baru dibuat agar akses permanen (30 hari) terikat ke email terverifikasi
        $this->akses_token = null;
        $this->akses_token_expires_at = null;
        $this->generateAksesToken();

        $this->save();

        return true;
    }

    /**
     * Cek apakah email sudah terverifikasi.
     */
    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }
}
