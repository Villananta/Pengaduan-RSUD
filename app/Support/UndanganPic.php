<?php

namespace App\Support;

use App\Enums\PeranPengguna;
use App\Enums\StatusAksesUnit;
use App\Models\MasterUnit;
use App\Models\User;
use App\Notifications\UndanganAktivasiPic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Alur undangan aktivasi akun PIC unit.
 *
 * Admin menekan "Kirim Undangan" di halaman master unit, sistem membuat
 * tautan berumur pendek untuk tiap PIC, lalu mengirimkannya lewat email.
 * Status akses unit ikut bergeser ke UndanganTerkirim dan baru menjadi
 * AkunAktif setelah PIC membuka tautan serta membuat sandinya sendiri.
 *
 * Pivot master_unit_user baru terisi pada saat aktivasi, bukan saat
 * undangan dikirim, supaya tidak ada akun setengah jadi yang sudah
 * berhak membuka konsol unit.
 */
final class UndanganPic
{
    /** Umur tautan undangan sejak dikirim, dalam jam. */
    public const UMUR_JAM = 72;

    /**
     * Alamat email yang terikat pada undangan sebuah unit.
     *
     * Kolom email diperlakukan sebagai salinan tetap alamat pada saat
     * undangan dikirim, jadi akun yang lahir dari tautan lama tetap memakai
     * alamat yang benar-benar menerima email itu, bukan alamat yang baru
     * diketik admin setelahnya. Baris lama yang belum sempat menyalin
     * alamatnya tetap dilayani lewat akun_simrs sebagai cadangan.
     */
    public static function tujuan(MasterUnit $unit): ?string
    {
        return filled($unit->email) ? $unit->email : $unit->akun_simrs;
    }

    /**
     * Alamat yang akan menerima undangan berikutnya.
     *
     * Form unit hanya menyunting "Email / Akun SIMRS", jadi kolom itulah
     * yang dianggap paling baru. Kolom email hanya cadangan untuk baris
     * lama yang akun_simrs-nya kosong.
     */
    private static function alamatTerkini(MasterUnit $unit): ?string
    {
        return filled($unit->akun_simrs) ? $unit->akun_simrs : $unit->email;
    }

    /**
     * Kirim undangan ke semua unit yang akunnya belum aktif.
     *
     * Unit yang alamat emailnya kosong atau unitnya sedang nonaktif ikut
     * terhitung "dilewat" karena memang tidak boleh diundang. Perbaikannya
     * sama: admin melengkapi data unit atau mengaktifkannya lagi, lalu
     * menekan tombol sekali lagi.
     *
     * Satu unit yang gagal tidak menghentikan sisa batch. Kegagalannya
     * dihitung terpisah dan dicatat ke log, supaya admin tetap mendapat
     * ringkasan dan bisa mengirim ulang tanpa menebak unit mana yang
     * tertinggal.
     *
     * @return array{terkirim: int, dilewat: int, gagal: int}
     */
    public static function kirimMassal(): array
    {
        $daftarUnit = MasterUnit::query()
            ->where('status_akses', '!=', StatusAksesUnit::AkunAktif->value)
            ->orderBy('kode')
            ->get();

        $terkirim = 0;
        $dilewat = 0;
        $gagal = 0;

        foreach ($daftarUnit as $unit) {
            try {
                self::kirim($unit) ? $terkirim++ : $dilewat++;
            } catch (Throwable $e) {
                $gagal++;

                Log::error('Undangan aktivasi untuk unit '.$unit->kode.' gagal dikirim.', [
                    'exception' => $e,
                ]);
            }
        }

        return compact('terkirim', 'dilewat', 'gagal');
    }

    /**
     * Kirim satu unit undangan beserta tautan aktivasi barunya.
     *
     * Mengembalikan false berarti unit itu tidak boleh diajak: alamat
     * emailnya kosong, unitnya sedang nonaktif, alamatnya sudah dipakai akun
     * humas, atau akunnya sudah aktif. Unit nonaktif dilewati supaya status
     * Dinonaktifkan tidak berubah jadi UndanganTerkirim dan tidak ada akun
     * PIC yang dibuat untuk unit yang konsolnya sedang ditutup. Guard status
     * ada di sini (bukan hanya di kirimMassal) karena mengirim ulang undangan
     * ke unit yang sudah berakun akan menurunkan statusnya kembali ke
     * UndanganTerkirim dan menimpa tautan yang mungkin sedang dibuka PIC.
     *
     * Alamat milik akun humas juga dilewati sejak awal. Tautannya pasti
     * ditolak di halaman aktivasi, jadi mengirim email hanya akan membuat
     * PIC menunggu tautan yang tidak pernah bisa dipakai.
     *
     * Alamat yang dipakai saat kirim disalin ke kolom email. Salinan itulah
     * yang nanti menentukan akun siapa yang dibuat, supaya menyunting
     * "Email / Akun SIMRS" setelah email berangkat tidak memindahkan akun
     * ke pemegang alamat baru.
     */
    public static function kirim(MasterUnit $unit): bool
    {
        $alamat = self::alamatTerkini($unit);

        if ($alamat === null || ! $unit->aktif || $unit->status_akses === StatusAksesUnit::AkunAktif) {
            return false;
        }

        if (self::dipakaiAkunHumas($alamat)) {
            return false;
        }

        // Baris lama bisa hanya menyimpan alamat di kolom email yang tidak
        // tampil di form. Pindahkan ke akun_simrs supaya admin melihat alamat
        // yang dipakai, bukan alamat tersembunyi.
        $unit->akun_simrs = $alamat;
        $unit->email = $alamat;
        $unit->undangan_token = Str::random(40);
        $unit->undangan_berlaku = now()->addHours(self::UMUR_JAM);
        $unit->status_akses = StatusAksesUnit::UndanganTerkirim;
        $unit->save();

        Notification::route('mail', $alamat)->notify(new UndanganAktivasiPic($unit, $unit->undangan_token));

        return true;
    }

    /**
     * True bila alamat itu sudah menjadi akun petugas humas.
     *
     * Undangan tidak boleh memakai akun humas: halaman aktivasi hanya
     * memeriksa kepemilikan email, tidak tahu peran pemiliknya, dan dulu
     * sempat menurunkan peran admin menjadi PIC unit. Seeder akun PIC sudah
     * melewati kasus ini; jalur undangan kini mengikuti aturan yang sama.
     */
    private static function dipakaiAkunHumas(string $alamat): bool
    {
        $pengguna = User::query()->where('email', $alamat)->first();

        return $pengguna !== null && ! $pengguna->berperanUnit();
    }

    /**
     * Cari unit pemilik sebuah token, apa pun kondisi masa berlakunya.
     *
     * Perbandingan di MySQL bisa mengabaikan huruf besar/kecil lewat
     * collation utf8mb4_unicode_ci, jadi hasil query tetap dicek persis di
     * sini. Tautan yang hurufnya diubah tidak boleh menemukan unit mana pun,
     * termasuk di basis data yang migration collation-nya belum jalan.
     */
    public static function cari(string $token): ?MasterUnit
    {
        $unit = MasterUnit::query()->where('undangan_token', $token)->first();

        if ($unit !== null && ! hash_equals((string) $unit->undangan_token, $token)) {
            return null;
        }

        return $unit;
    }

    /** True bila token masih dipegang unit dan masa berlakunya belum lewat. */
    public static function masihBerlaku(MasterUnit $unit): bool
    {
        return $unit->undangan_token !== null
            && $unit->undangan_berlaku !== null
            && $unit->undangan_berlaku->isFuture();
    }

    /**
     * Hubungkan akun PIC ke unit lalu tandai unit sebagai berakun aktif.
     *
     * Nama dan sandi hanya dipakai saat email PIC belum punya akun sama
     * sekali. Akun yang sudah ada tidak pernah ditimpa: pemegang tautan
     * hanyalah pemilik email, dan menimpa sandi akun yang sudah beredar
     * bisa menutup akses petugas yang sudah memakainya.
     *
     * Akun humas ditolak, bukan dipinjam. Tautan undangan hanya membuktikan
     * kepemilikan alamat email, bukan bahwa pemiliknya bersedia menjadi PIC,
     * jadi peran yang sudah ada tidak boleh diturunkan hanya karena alamat
     * unit kebetulan sama dengan alamat admin.
     */
    public static function aktivasi(MasterUnit $unit, string $nama, string $kataSandi): User
    {
        // Alamat diambil dari salinan saat undangan dikirim, bukan dari
        // kolom yang mungkin sudah disunting admin setelah email berangkat.
        $alamat = self::tujuan($unit);

        // Kolom email users tidak boleh kosong, jadi unit yang alamatnya
        // baru dikosongkan admin harus diajak mengundang ulang, bukan
        // memakai sisa tautan yang sudah terlanjur terkirim.
        if ($alamat === null) {
            throw ValidationException::withMessages([
                'undangan' => 'Alamat email unit ini sudah dikosongkan. Minta admin mengirim undangan baru.',
            ]);
        }

        if (! $unit->aktif) {
            throw ValidationException::withMessages([
                'undangan' => 'Unit '.$unit->namaLengkap().' sedang nonaktif, jadi undangan ini tidak bisa dipakai. Minta admin mengaktifkannya kembali lalu mengirim undangan baru.',
            ]);
        }

        if (self::dipakaiAkunHumas($alamat)) {
            throw ValidationException::withMessages([
                'undangan' => 'Alamat '.$alamat.' sudah dipakai akun petugas humas. Minta admin mengganti alamat PIC unit ini di master data, lalu mengirim undangan baru.',
            ]);
        }

        return DB::transaction(function () use ($unit, $nama, $kataSandi, $alamat): User {
            $pengguna = User::firstOrCreate(
                ['email' => $alamat],
                ['name' => $nama, 'peran' => PeranPengguna::Unit, 'password' => $kataSandi],
            );

            $pengguna->unit()->syncWithoutDetaching([
                $unit->getKey() => ['peran_akses' => $unit->peran_akses->value],
            ]);

            $unit->status_akses = StatusAksesUnit::AkunAktif;
            $unit->undangan_token = null;
            $unit->undangan_berlaku = null;
            $unit->save();

            return $pengguna;
        });
    }
}
