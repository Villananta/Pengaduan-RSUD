<?php

namespace App\Support;

use App\Enums\DisposisiUnit;
use App\Enums\StatusPengaduan;
use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Tindak lanjut admin humas terhadap satu tiket pengaduan.
 *
 * Halaman detail dapat melakukan empat hal: menyusun draf jawaban,
 * mengirim jawaban resmi sekaligus menutup tiket, mengembalikan tiket ke
 * unit untuk klarifikasi ulang, dan menyalakan penandaan kasus berat.
 * Semuanya dikumpulkan di sini supaya aturan transisinya hanya ada satu
 * tempat, tidak pecah antara controller, template, dan model.
 *
 * Daftar tujuan tahap yang sah tetap dibaca dari enum status, sehingga
 * kelas ini tidak perlu ikut diubah setiap kali alur pengaduan berubah.
 */
final class TindakLanjutPengaduan
{
    /**
     * Aksi A: kirim jawaban resmi lalu tutup tiket.
     *
     * Isi jawaban boleh null karena panel formulasi sekarang hanya
     * menyediakan tombol, jadi admin bisa menutup tiket tanpa surat.
     * Kalau ada isinya, balasan disimpan sebagai pesan berperan admin supaya
     * langsung muncul di kronologi percakapan yang dibaca pelapor. Baik
     * dengan maupun tanpa balasan, tahap tiket dipindahkan ke Selesai
     * sehingga waktu penyelesaian ikut tercatat.
     *
     * @throws RuntimeException bila tahap sekarang tidak mengizinkan ditutup
     */
    public static function kirimJawabanResmi(
        Pengaduan $pengaduan,
        ?string $isi = null,
        ?string $catatan = null,
        ?int $adminId = null,
    ): void {
        $alasan = self::tindakanTersedia($pengaduan)['alasanTutup'];

        if ($alasan !== null) {
            throw new RuntimeException($alasan);
        }

        DB::transaction(function () use ($pengaduan, $isi, $catatan, $adminId): void {
            if (filled($isi)) {
                $pengaduan->pesan()->create([
                    'kanal' => PesanPengaduan::KANAL_PELAPOR,
                    'peran' => 'admin',
                    'isi' => $isi,
                ]);
            }

            $pengaduan->pindahTahap(StatusPengaduan::Selesai, $catatan, $adminId);
        });

        self::segarkanStatistik();
    }

    /**
     * Aksi B: kembalikan tiket ke unit untuk klarifikasi ulang.
     *
     * Balasan lama sengaja tidak dihapus. Yang dikembalikan hanya tahapnya,
     * jadi unit bisa menulis jawaban baru sementara percakapan sebelumnya
     * tetap terbaca sebagai bagian dari proses.
     *
     * @throws RuntimeException bila tiket belum ditugaskan ke unit atau tahapnya tidak mendukung
     */
    public static function kembalikanKeUnit(Pengaduan $pengaduan, ?string $catatan = null, ?int $adminId = null): void
    {
        $alasan = self::tindakanTersedia($pengaduan)['alasanKembalikan'];

        if ($alasan !== null) {
            throw new RuntimeException($alasan);
        }

        DB::transaction(function () use ($pengaduan, $catatan, $adminId): void {
            // Unit yang sudah memberi jawaban ditandai menunggu investigasi lagi,
            // supaya kembalinya tidak terlihat sebagai pekerjaan yang belum dimulai.
            $pengaduan->masterUnit?->update([
                'disposisi' => DisposisiUnit::MenungguInvestigasi,
            ]);

            $pengaduan->pindahTahap(StatusPengaduan::Diproses, $catatan, $adminId);
        });

        self::segarkanStatistik();
    }

    /** Simpan draf jawaban tanpa mengirim apa pun ke pelapor. */
    public static function simpanDraf(Pengaduan $pengaduan, ?string $isi): void
    {
        $pengaduan->forceFill([
            'draf_jawaban' => filled($isi) ? $isi : null,
        ])->save();
    }

    /**
     * Pindahkan tiket ke tahap yang dipilih admin dari tombol di bawah halaman.
     *
     * Ini jalur umum untuk tombol Proses, Selesaikan, dan Revisi. Semua
     * perpindahan harus lewat sini supaya validasi tahap, pencatatan riwayat,
     * dan pembuangan cache angka dashboard tidak tercecer di tiap endpoint.
     *
     * @throws RuntimeException bila tahap sekarang tidak mengizinkan tujuan itu
     */
    public static function pindahkanTahap(
        Pengaduan $pengaduan,
        StatusPengaduan $tujuan,
        ?string $catatan = null,
        ?int $adminId = null,
    ): void {
        $alasan = self::alasanPindah($pengaduan, $tujuan);

        if ($alasan !== null) {
            throw new RuntimeException($alasan);
        }

        DB::transaction(function () use ($pengaduan, $tujuan, $catatan, $adminId): void {
            $pengaduan->pindahTahap($tujuan, $catatan, $adminId);
        });

        self::segarkanStatistik();
    }

    /**
     * Tombol tahap mana yang boleh ditunjukkan pada tahap sekarang.
     *
     * Daftar ini sengaja lebih sempit daripada tujuanBerikutnya() pada enum.
     * Enum masih mengizinkan reopening dari Selesai dan Revisi ke Diproses
     * untuk keperluan internal, sedangkan di halaman ini admin hanya boleh
     * majukan satu tahap atau menutup tiket.
     *
     * @return array<int, StatusPengaduan>
     */
    public static function tujuanTersedia(Pengaduan $pengaduan): array
    {
        return match ($pengaduan->status) {
            StatusPengaduan::Diterima => [StatusPengaduan::Diproses],
            StatusPengaduan::Diproses => [StatusPengaduan::Selesai, StatusPengaduan::Revisi],
            StatusPengaduan::Revisi => [StatusPengaduan::Selesai],
            StatusPengaduan::Selesai => [],
        };
    }

    /** Kalimat hasil tindakan yang ditampilkan setelah tiket berhasil dipindahkan. */
    public static function pesanTujuan(StatusPengaduan $tujuan): string
    {
        return match ($tujuan) {
            StatusPengaduan::Diproses => 'Pengaduan masuk tahap Diproses dan menunggu penanganan unit.',
            StatusPengaduan::Revisi => 'Pengaduan ditandai perlu revisi, pelapor diminta melengkapi data.',
            StatusPengaduan::Selesai => 'Pengaduan ditandai selesai.',
            default => 'Tahap pengaduan diperbarui.',
        };
    }

    /**
     * Label, ikon, dan warna tombol untuk satu tahap tujuan.
     *
     * Revisi sengaja memakai warna error supaya pilihan yang meminta data
     * ulang tidak salah dikira sebagai tombol menutup tiket.
     *
     * @return array{label: string, ikon: string, warna: string}
     */
    public static function tombolTujuan(StatusPengaduan $tujuan): array
    {
        return match ($tujuan) {
            StatusPengaduan::Diproses => [
                'label' => 'Proses',
                'ikon' => 'play_arrow',
                'warna' => 'bg-primary text-on-primary',
            ],
            StatusPengaduan::Revisi => [
                'label' => 'Revisi',
                'ikon' => 'rate_review',
                'warna' => 'bg-error text-white',
            ],
            StatusPengaduan::Selesai => [
                'label' => 'Selesaikan',
                'ikon' => 'check_circle',
                'warna' => 'bg-primary text-on-primary',
            ],
            default => [
                'label' => $tujuan->label(),
                'ikon' => $tujuan->ikon(),
                'warna' => 'bg-surface-container text-on-surface',
            ],
        };
    }

    /**
     * Alasan kenapa suatu tujuan tidak boleh dipakai, atau null kalau sah.
     *
     * Satu-satunya sumber aturan adalah tujuanTersedia(), jadi tombol yang
     * tampil di layar dan tombol yang diterima server tidak akan berbeda.
     */
    private static function alasanPindah(Pengaduan $pengaduan, StatusPengaduan $tujuan): ?string
    {
        if ($pengaduan->status === $tujuan) {
            return 'Pengaduan sudah berada di tahap '.$tujuan->label().'.';
        }

        if (! in_array($tujuan, self::tujuanTersedia($pengaduan), true)) {
            return $pengaduan->status->selesai()
                ? 'Tiket ini sudah selesai sehingga tahapnya tidak bisa diubah lagi.'
                : 'Tahap '.$pengaduan->status->label().' tidak punya tombol untuk pindah ke '.$tujuan->label().'.';
        }

        return null;
    }

    /**
     * Simpan satu balasan admin di kolom percakapan.
     *
     * Balasan ini sengaja tidak mengubah tahap tiket. Admin boleh membalas
     * sebanyak yang diperlukan, termasuk setelah tiket dinyatakan selesai,
     * karena percakapan dengan pelapor berjalan terus di luar alur tahap.
     *
     * Angka telaah pada dashboard justru bergantung pada keberadaan balasan
     * ini, jadi cache statistik ikut dibuang walaupun tahap tiket tidak
     * berubah. Tanpa itu angka telaah tertinggal sampai cache-nya kedaluwarsa.
     *
     * @param  array<int, UploadedFile|null>  $berkas
     */
    public static function balasPelapor(
        Pengaduan $pengaduan,
        string $isi,
        array $berkas = [],
    ): void {
        $pengaduan->pesan()->create([
            'kanal' => PesanPengaduan::KANAL_PELAPOR,
            'peran' => 'admin',
            'isi' => $isi,
            'lampiran' => PesanPengaduan::simpanBerkas($berkas),
        ]);

        self::segarkanStatistik();
    }

    /**
     * Kirim satu pesan admin humas ke kanal koordinasi unit.
     *
     * Berbeda dari balasPelapor(), pesan ini duduk pada jalur 'unit' sehingga
     * tidak pernah tampil di halaman lacak pelapor. Jalurnya sendiri sama seperti
     * jawaban PIC unit, jadi obrolan antara humas dan unit terbaca sebagai satu
     * percakapan dua arah, bukan dua SALURAN terpisah.
     *
     * Tahap tiket sengaja tidak diubah; pesan ini hanya koordinasi kerja, bukan
     * keputusan alur pengaduan.
     *
     * @param  array<int, UploadedFile|null>  $berkas
     *
     * @throws RuntimeException bila tiket belum ditugaskan ke unit mana pun
     */
    public static function koordinasiUnit(
        Pengaduan $pengaduan,
        string $isi,
        array $berkas = [],
    ): void {
        if ($pengaduan->master_unit_id === null) {
            throw new RuntimeException('Tiket ini belum ditugaskan ke unit sehingga tidak ada kanal koordinasi unit.');
        }

        $pengaduan->pesan()->create([
            'kanal' => PesanPengaduan::KANAL_UNIT,
            'peran' => 'admin',
            'isi' => $isi,
            'lampiran' => PesanPengaduan::simpanBerkas($berkas),
        ]);

        self::segarkanStatistik();
    }

    /** Nyalakan atau matikan penandaan kasus berat beserta jejak auditnya. */
    public static function tandaiKasusBerat(Pengaduan $pengaduan, bool $aktif, ?int $adminId = null): void
    {
        $pengaduan->tandaiKasusBerat($aktif, $adminId);

        self::segarkanStatistik();
    }

    /**
     * Tindakan mana yang boleh dijalankan pada satu tiket.
     *
     * Template memakai hasilnya untuk menentukan tombol mana yang hidup dan
     * alasannya apa, sehingga aturan yang sama dipakai lagi di server saat
     * formulir dikirim. Tiket yang sudah Selesai tidak menawarkan tindakan
     * apa pun; membukanya kembali adalah persoalan lain, bukan aksi A atau B.
     *
     * @return array{tutup: bool, kembalikan: bool, alasanTutup: ?string, alasanKembalikan: ?string}
     */
    public static function tindakanTersedia(Pengaduan $pengaduan): array
    {
        $sudahSelesai = $pengaduan->status->selesai();

        $bisaTutup = ! $sudahSelesai
            && $pengaduan->status->bisaBerpindahKe(StatusPengaduan::Selesai);
        $bisaKembalikan = ! $sudahSelesai
            && $pengaduan->master_unit_id !== null
            && $pengaduan->status->bisaBerpindahKe(StatusPengaduan::Diproses);

        return [
            'tutup' => $bisaTutup,
            'kembalikan' => $bisaKembalikan,
            'alasanTutup' => match (true) {
                $bisaTutup => null,
                $sudahSelesai => 'Tiket ini sudah selesai, tidak perlu jawaban resmi lagi.',
                default => 'Tahap '.$pengaduan->status->label().' belum boleh ditutup.',
            },
            'alasanKembalikan' => match (true) {
                $bisaKembalikan => null,
                $sudahSelesai => 'Tiket ini sudah selesai sehingga tidak dikembalikan ke unit.',
                $pengaduan->master_unit_id === null => 'Pengaduan ini belum ditugaskan ke unit mana pun.',
                default => 'Tahap '.$pengaduan->status->label().' tidak bisa dikembalikan ke unit.',
            },
        ];
    }

    /**
     * Buang cache angka dashboard dan daftar pengaduan.
     *
     * Kedua layar itu menyimpan hasil hitungnya supaya tidak query ulang pada
     * setiap pembukaan. Setelah tiket ditutup, dikembalikan, atau ditandai kasus
     * berat angkanya jadi berbeda, jadi cache wajib dibuang supaya admin tidak
     * melihat jumlah yang sudah usang.
     */
    private static function segarkanStatistik(): void
    {
        StatistikDashboard::lupaCache();
        StatistikPengaduan::lupaCache();
    }
}
