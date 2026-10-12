<?php

namespace App\Support;

use App\Enums\DisposisiUnit;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Tindak lanjut admin humas terhadap satu tiket pengaduan.
 *
 * Halaman detail dapat melakukan beberapa hal: menugaskan tiket ke unit,
 * memindahkan tahap termasuk menutupnya, menangani langsung tanpa unit,
 * membalas pelapor, mengirim koordinasi ke unit, dan menyalakan penandaan
 * kasus berat. Semuanya dikumpulkan di sini supaya aturan transisinya hanya
 * ada satu tempat, tidak pecah antara controller, template, dan model.
 *
 * Daftar tujuan tahap yang sah tetap dibaca dari enum status, sehingga
 * kelas ini tidak perlu ikut diubah setiap kali alur pengaduan berubah.
 */
final class TindakLanjutPengaduan
{
    /**
     * Tugaskan tiket ke satu instalasi teknis.
     *
     * Penugasan menyentuh dua baris sekaligus. Tiket menyimpan master_unit_id
     * supaya masuk daftar unit yang bersangkutan, sedangkan unitnya ditandai
     * menunggu investigasi agar papan disposisi sisi unit langsung memuat
     * tiket ini. Tahap tiket ikut naik ke Diproses, karena disposisi hanya
     * berguna kalau tiketnya memang sedang berjalan.
     *
     * Tiket yang sudah Diproses tidak dipindah lagi; yang berubah hanya unit
     * tujuannya, dan pengalihannya tetap dicatat di riwayat supaya jejaknya
     * tidak hilang.
     *
     * @throws RuntimeException bila tiket sudah selesai atau unit tujuannya tidak siap
     */
    public static function disposisiKeUnit(
        Pengaduan $pengaduan,
        MasterUnit $unit,
        ?string $catatan = null,
        ?int $adminId = null,
    ): void {
        if ($pengaduan->status->selesai()) {
            throw new RuntimeException('Tiket ini sudah selesai sehingga tidak bisa didisposisikan lagi.');
        }

        if (! $unit->bisaDitugaskan()) {
            throw new RuntimeException('Unit '.$unit->namaLengkap().' sedang tidak aktif atau koneksinya terputus.');
        }

        DB::transaction(function () use ($pengaduan, $unit, $catatan, $adminId): void {
            // Unit yang ditinggalkan dikembalikan ke terhubung supaya benderanya
            // tidak tertinggal "menunggu investigasi" padahal tiketnya pindah.
            $unitSebelumnya = $pengaduan->masterUnit;

            $pengaduan->master_unit_id = $unit->id;

            if ($pengaduan->status === StatusPengaduan::Diproses) {
                $pengaduan->save();
                $pengaduan->riwayatStatus()->create([
                    'dari' => $pengaduan->status,
                    'ke' => $pengaduan->status,
                    'catatan' => $catatan ?? 'Disposisi dialihkan ke '.$unit->namaLengkap().'.',
                    'admin_id' => $adminId,
                ]);
            } else {
                $pengaduan->pindahTahap(StatusPengaduan::Diproses, $catatan, $adminId);
            }

            if ($unitSebelumnya !== null && $unitSebelumnya->id !== $unit->id) {
                $unitSebelumnya->update(['disposisi' => DisposisiUnit::Terhubung]);
            }

            $unit->update(['disposisi' => DisposisiUnit::MenungguInvestigasi]);
        });

        self::segarkanStatistik();
    }

    /**
     * Jalur 2: humas menangani tiket sendiri tanpa unit teknis.
     *
     * Tiket dilepas dari unit dengan mengosongkan master_unit_id, karena
     * kolom itulah satu-satunya penanda yang dipakai dashboard dan daftar
     * disposisi unit. Begitu kosong, tiket otomatis hilang dari konsol unit
     * dan berpindah ke hitungan "ditangani langsung humas".
     *
     * Tahapnya ikut naik ke Diproses. Kalau tiket tadi sudah menempel ke unit,
     * pelepasan itu dicatat tersendiri di riwayat supaya keputusan humas tidak
     * menghapus jejak penugasan sebelumnya.
     *
     * @throws RuntimeException bila tahap sekarang bukan tahap yang boleh ditangani langsung
     */
    public static function tanganiLangsung(
        Pengaduan $pengaduan,
        ?string $catatan = null,
        ?int $adminId = null,
    ): void {
        if (! in_array(StatusPengaduan::Diproses, self::tujuanTersedia($pengaduan), true)) {
            throw new RuntimeException(
                $pengaduan->status->selesai()
                    ? 'Tiket ini sudah selesai sehingga tidak bisa ditangani langsung lagi.'
                    : 'Tahap '.$pengaduan->status->label().' tidak menawarkan penanganan langsung oleh humas.'
            );
        }

        DB::transaction(function () use ($pengaduan, $catatan, $adminId): void {
            // Relasi dibaca lebih dulu karena save() di pindahTahap() sekalian
            // menyimpan master_unit_id yang sudah dikosongkan.
            $unitDilepas = $pengaduan->masterUnit;

            $pengaduan->master_unit_id = null;
            $pengaduan->pindahTahap(StatusPengaduan::Diproses, $catatan, $adminId);

            if ($unitDilepas !== null) {
                // Unit yang dilepas kembali siap menerima penugasan; kalau
                // benderanya dibiarkan, konsol unit terus menampilkan disposisi
                // terakhir yang sudah tidak berlaku.
                $unitDilepas->update(['disposisi' => DisposisiUnit::Terhubung]);

                $pengaduan->riwayatStatus()->create([
                    'dari' => StatusPengaduan::Diproses,
                    'ke' => StatusPengaduan::Diproses,
                    'catatan' => 'Disposisi ke '.$unitDilepas->namaLengkap().' dilepas; penanganan diambil alih humas.',
                    'admin_id' => $adminId,
                ]);
            }
        });

        self::segarkanStatistik();
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
            self::selaraskanDisposisi($pengaduan, $tujuan);
        });

        self::segarkanStatistik();
    }

    /**
     * Selaraskan bendera disposisi unit yang menangani dengan tahap tiket.
     *
     * master_units.disposisi adalah satu bendera per unit, bukan per tiket,
     * dan hanya berarti sebagai penanda "disposisi terakhir". Karena itu tiap
     * aksi humas yang memindahkan tahap harus ikut memperbaruinya; kalau
     * tidak, konsol unit menampilkan disposisi yang sudah basi. Tiket tanpa
     * unit dilewati karena tidak ada bendera yang perlu diubah.
     */
    private static function selaraskanDisposisi(Pengaduan $pengaduan, StatusPengaduan $tahap): void
    {
        if ($pengaduan->master_unit_id === null) {
            return;
        }

        $disposisi = match ($tahap) {
            StatusPengaduan::Diproses => DisposisiUnit::MenungguInvestigasi,
            StatusPengaduan::Revisi => DisposisiUnit::MenungguInfoTambahan,
            StatusPengaduan::Selesai => DisposisiUnit::Terhubung,
            StatusPengaduan::Diterima => null,
        };

        if ($disposisi === null) {
            return;
        }

        $pengaduan->masterUnit?->update(['disposisi' => $disposisi]);
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
     * Cache statistik tidak perlu dibuang: balasan humas ke pelapor tidak
     * mengubah tahap maupun jawaban unit, sedangkan angka telaah hanya
     * bertambah saat PIC unit mengirim jawaban lewat JawabanUnit.
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
     * keputusan alur pengaduan. Cache statistik pun tidak dibuang karena pesan
     * admin di kanal unit tidak menambah angka telaah maupun angka lain.
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
    }

    /** Nyalakan atau matikan penandaan kasus berat beserta jejak auditnya. */
    public static function tandaiKasusBerat(Pengaduan $pengaduan, bool $aktif, ?int $adminId = null): void
    {
        $pengaduan->tandaiKasusBerat($aktif, $adminId);

        self::segarkanStatistik();
    }

    /**
     * Alasan tiket belum bisa ditutup dari panel tahap, atau null kalau bisa.
     *
     * Panel tahap menampilkan kalimat ini ketika tidak ada tombol tujuan yang
     * tersisa, sedangkan penutupan sebenarnya tetap lewat pindahkanTahap().
     * Dengan begitu penolakan di server dan keterangan di layar tetap sama.
     */
    public static function alasanTutup(Pengaduan $pengaduan): ?string
    {
        if ($pengaduan->status->selesai()) {
            return 'Tiket ini sudah selesai sehingga tidak ada tindakan lanjutan.';
        }

        if (! $pengaduan->status->bisaBerpindahKe(StatusPengaduan::Selesai)) {
            return 'Tahap '.$pengaduan->status->label().' belum boleh ditutup.';
        }

        return null;
    }

    /**
     * Buang cache angka dashboard dan daftar pengaduan.
     *
     * Kedua layar itu menyimpan hasil hitungnya supaya tidak query ulang pada
     * setiap pembukaan. Setelah tiket ditutup, dipindah tahapnya, atau
     * ditandai kasus berat angkanya jadi berbeda, jadi cache wajib dibuang
     * supaya admin tidak melihat jumlah yang sudah usang.
     */
    private static function segarkanStatistik(): void
    {
        StatistikDashboard::lupaCache();
        StatistikPengaduan::lupaCache();
        StatistikUnit::lupaCache();
    }
}
