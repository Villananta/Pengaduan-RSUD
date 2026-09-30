<?php

namespace App\Support;

use App\Enums\DisposisiUnit;
use App\Enums\StatusPengaduan;
use App\Models\Pengaduan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Tindak lanjut admin humas terhadap satu tiket pengaduan.
 *
 * Halaman detail danach bisa melakukan empat hal: menyusun draf jawaban,
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
     * Balasan disimpan sebagai pesan berperan admin supaya langsung muncul
     * di kronologi percakapan yang dibaca pelapor, lalu tahap tiket
     * dipindahkan ke Selesai sehingga waktu penyelesaian ikut tercatat.
     *
     * @throws RuntimeException bila tahap sekarang tidak mengizinkan ditutup
     */
    public static function kirimJawabanResmi(
        Pengaduan $pengaduan,
        string $isi,
        ?string $catatan = null,
        ?int $adminId = null,
    ): void {
        $alasan = self::tindakanTersedia($pengaduan)['alasanTutup'];

        if ($alasan !== null) {
            throw new RuntimeException($alasan);
        }

        DB::transaction(function () use ($pengaduan, $isi, $catatan, $adminId): void {
            $pengaduan->pesan()->create([
                'peran' => 'admin',
                'isi' => $isi,
            ]);

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

        // Unit yang sudah memberi jawaban ditandai menunggu investigasi lagi,
        // supaya kembalinya tidak terlihat sebagai pekerjaan yang belum dimulai.
        $pengaduan->masterUnit?->update([
            'disposisi' => DisposisiUnit::MenungguInvestigasi,
        ]);

        $pengaduan->pindahTahap(StatusPengaduan::Diproses, $catatan, $adminId);

        self::segarkanStatistik();
    }

    /** Simpan draf jawaban tanpa mengirim apa pun ke pelapor. */
    public static function simpanDraf(Pengaduan $pengaduan, ?string $isi): void
    {
        $pengaduan->forceFill([
            'draf_jawaban' => filled($isi) ? $isi : null,
        ])->save();
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
