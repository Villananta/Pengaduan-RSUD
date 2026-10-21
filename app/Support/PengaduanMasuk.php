<?php

namespace App\Support;

use App\Enums\KanalPengaduan;
use App\Enums\KategoriPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\User;
use App\Notifications\PengaduanDibuat;
use App\Notifications\PengaduanDisposisi;
use App\Notifications\PengaduanEmailVerifikasi;
use App\Rules\NormalizedNrmRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

/**
 * Penyimpanan pengaduan baru dari kanal mana pun.
 *
 * Formulir pelapor dan formulir input aduan admin memakai kolom, aturan,
 * dan pemetaan unit yang sama. Kalau keduanya dipisah, admin bisa membuat
 * tiket yang tidak bisa dibuat pelapor, dan standar pelayanan dua kanal itu
 * perlahan keluar jalur.
 */
final class PengaduanMasuk
{
    /**
     * Aturan validasi formulir pengaduan.
     *
     * Parameter $khusus dipakai untuk kolom yang hanya ada di satu kanal,
     * misalnya pilihan kanal penerimaan yang hanya ada di formulir admin.
     *
     * @param  array<string, array<int, mixed>>  $khusus
     * @return array<string, array<int, mixed>>
     */
    public static function aturan(array $khusus = []): array
    {
        return array_merge([
            'kategori' => ['required', Rule::enum(KategoriPengaduan::class)],
            'nama_lengkap' => ['required', 'string', 'min:3', 'max:255'],
            'nrm' => ['required', 'string', 'min:4', 'max:64', new NormalizedNrmRule],
            'no_wa' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'alamat' => ['required', 'string', 'min:10', 'max:1000'],
            'waktu_kejadian' => ['required', 'date', 'after:2020-01-01', 'before_or_equal:now'],
            'unit' => ['required', 'string', Rule::in(config('pengaduan.units'))],
            'subjek' => ['required', 'string', 'min:10', 'max:255'],
            'deskripsi' => ['required', 'string', 'min:20', 'max:10000'],
            'lampiran' => ['nullable', 'array', 'max:5'],
            'lampiran.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'persetujuan' => ['required', 'accepted'],
        ], $khusus);
    }

    /**
     * Pesan error custom agar isian yang tidak lolos dibaca orangnya, bukan
     * hanya nama field.
     *
     * @param  array<string, string>  $khusus
     * @return array<string, string>
     */
    public static function pesanValidasi(array $khusus = []): array
    {
        return array_merge([
            'persetujuan.accepted' => 'Anda harus menyetujui pernyataan kebenaran data sebelum mengirim aduan.',
            'nama_lengkap.min' => 'Nama lengkap minimal 3 karakter.',
            'nrm.min' => 'Nomor rekam media minimal 4 karakter.',
            'nrm.nrm' => 'Nomor rekam media hanya boleh berisi angka dan huruf (spasi, tanda hubung, titik akan diabaikan).',
            'no_wa.regex' => 'Nomor WhatsApp harus berupa nomor telepon aktif, contoh 081234567890.',
            'alamat.min' => 'Alamat minimal 10 karakter.',
            'waktu_kejadian.after' => 'Waktu kejadian tidak valid.',
            'waktu_kejadian.before_or_equal' => 'Waktu kejadian tidak boleh di masa depan.',
            'unit.in' => 'Unit atau instalasi yang dipilih tidak terdaftar.',
            'subjek.min' => 'Ringkasan masalah minimal 10 karakter agar mudah dipahami.',
            'deskripsi.min' => 'Uraian kronologi minimal 20 karakter.',
            'lampiran.max' => 'Maksimal 5 lampiran per pengaduan.',
            'lampiran.*.mimes' => 'Lampiran hanya boleh berformat JPG, PNG, atau PDF.',
            'lampiran.*.max' => 'Ukuran maksimal tiap lampiran adalah 4 MB.',
        ], $khusus);
    }

    /**
     * Buat tiket baru beserta lampirannya.
     *
     * Kode tiket tetap dibuat di sini, bukan diterima dari form, supaya
     * baik pelapor maupun admin humas tidak bisa menebak nomor tiket
     * berikutnya.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $berkas
     */
    public static function simpan(array $data, array $berkas = []): Pengaduan
    {
        // Normalisasi NRM sebelum disimpan: hapus spasi, tanda hubung, titik, ubah ke uppercase
        $data = array_merge($data, [
            'nrm' => Pengaduan::normalisasiNrm($data['nrm'] ?? ''),
        ]);

        $pengaduan = Pengaduan::buat(
            (new Collection($data))
                ->except(['persetujuan', 'kanal', 'lampiran'])
                ->put('master_unit_id', self::unitMaster($data['unit'] ?? null))
                ->all(),
            self::kodeTiketBaru(),
        );

        $pengaduan->forceFill(['lampiran' => self::simpanLampiran($berkas)])->save();

        // Generate OTP & kirim email verifikasi ke pelapor
        $otp = $pengaduan->generateEmailOtp();
        Notification::route('mail', $pengaduan->email)
            ->notify(new PengaduanEmailVerifikasi($pengaduan, $otp));

        // Notifikasi ke admin humas tentang tiket baru
        $admins = User::where('peran', 'admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new PengaduanDibuat($pengaduan, $pengaduan->kode_tiket));
        }

        // Notifikasi ke PIC unit jika tiket langsung ditugaskan ke unit
        $pengaduan->loadMissing('masterUnit.pengguna');
        if ($pengaduan->master_unit_id !== null && $pengaduan->masterUnit?->pengguna->isNotEmpty()) {
            Notification::send($pengaduan->masterUnit->pengguna, new PengaduanDisposisi(
                $pengaduan,
                $pengaduan->masterUnit,
                'Tiket otomatis ditugaskan berdasarkan pilihan pelapor.'
            ));
        }

        return $pengaduan;
    }

    /**
     * Isi pesan pembuka yang langsung terbaca pelapor di halaman lacak.
     *
     * Tanpa $kanal, pesan ini dibaca sebagai kiriman dari formulir web.
     * Kalau $kanal diisi, pengaduan yang diterima di luar web dicatat
     * beserta petugas yang mencatatnya, karena pelapor tidak punya akses
     * ke portal untuk membaca tiket yang baru dibuat admin.
     */
    public static function pesanPembuka(Pengaduan $pengaduan, ?KanalPengaduan $kanal = null, ?string $petugas = null): string
    {
        $kode = $pengaduan->kode_tiket;
        $waktu = $pengaduan->created_at->translatedFormat('d F Y, H:i').' WIB';

        if ($kanal === null) {
            return "Terima kasih telah menyampaikan pengaduan kepada kami. Laporan Anda telah kami terima dengan nomor tiket {$kode} pada {$waktu}.\n\n"
                .'Tim Humas & Pengaduan RSUD Dr. Soetomo akan menindaklanjuti laporan ini sesuai prosedur yang berlaku, dengan estimasi penyelesaian maksimal '
                .Sla::hariKerja().' hari kerja. '
                ."Kami akan menginformasikan perkembangan penanganan melalui kontak yang telah Anda daftarkan.\n\n"
                ."Mohon simpan nomor tiket ini sebagai referensi jika Anda ingin menanyakan status laporan.\n\n"
                .'Terima kasih atas kepercayaan Anda kepada RSUD Dr. Soetomo.';
        }

        $petugas = $petugas !== null && trim($petugas) !== '' ? trim($petugas) : 'Admin Humas';

        return "Pengaduan ini diterima melalui {$kanal->frasa()} pada {$waktu} dan dicatat oleh {$petugas} atas nama pelapor.\n\n"
            ."Laporan telah kami terdaftarkan dengan nomor tiket {$kode}. Tim Humas & Pengaduan RSUD Dr. Soetomo akan menindaklanjuti laporan ini sesuai prosedur yang berlaku, dengan estimasi penyelesaian maksimal "
            .Sla::hariKerja().' hari kerja. Pelapor akan diberi tahu nomor tiket ini melalui kontak yang tercatat pada formulir ini,'
            ." dan informasi berikutnya disampaikan lewat kanal yang sama.\n\n"
            .'Mohon nomor tiket ini disampaikan kepada pelapor sebagai referensi bila nanti ingin menanyakan status laporan.';
    }

    /**
     * Terjemahkan label unit pilihan pelapor ke MASTER_UNITS.
     *
     * Pengaduan lama atau unit yang belum dipetakan tetap boleh kosong,
     * karena kolomnya nullable dan nama pada kolom unit tetap disimpan.
     * Unit yang sedang tidak bisa ditugaskan diperlakukan sama, supaya
     * tiket tidak menempel ke unit yang tidak bisa menanganinya.
     */
    public static function unitMaster(?string $label): ?int
    {
        $peta = config('pengaduan.peta_unit', []);
        $kode = $peta[$label] ?? null;

        if ($kode === null) {
            return null;
        }

        $unit = MasterUnit::where('kode', $kode)->first();

        // Unit non-aktif dan unit yang koneksinya terputus ditolak dengan
        // aturan yang sama seperti Humas saat mendisposisi. Kalau tautan
        // tetap dibuat di sini, pelapor justru bisa menugaskan tiket ke unit
        // yang tidak boleh dipilih Humas, dan tiketnya menggantung tanpa
        // pihak yang bisa ditagih.
        if ($unit === null || ! $unit->bisaDitugaskan()) {
            return null;
        }

        return $unit->id;
    }

    /**
     * Kode tiket lima huruf acak dengan tanggal dibuatnya.
     *
     * Diulang sampai benar-benar belum dipakai supaya tidak pernah bentrok
     * dengan tiket lama yang sekarang masih bisa dilacak pelapor.
     */
    public static function kodeTiketBaru(): string
    {
        do {
            $kode = 'ADUAN-'.now()->format('Ymd').'-'.strtoupper(collect(range(1, 5))->map(fn () => chr(random_int(65, 90)))->implode(''));
        } while (Pengaduan::where('kode_tiket', $kode)->exists());

        return $kode;
    }

    /**
     * Simpan berkas lampiran ke disk yang dikonfigurasi untuk lampiran.
     *
     * @param  array<int, UploadedFile>  $berkas
     * @return array<int, string>
     */
    private static function simpanLampiran(array $berkas): array
    {
        $lampiran = [];

        foreach ($berkas as $file) {
            $lampiran[] = $file->store('lampiran', config('pengaduan.disk_lampiran'));
        }

        return $lampiran;
    }
}
