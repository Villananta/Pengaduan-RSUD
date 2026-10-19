<?php

namespace App\Support;

use App\Enums\PeranPengguna;
use App\Enums\StatusAksesUnit;
use App\Models\MasterUnit;
use App\Models\User;
use App\Notifications\UndanganAktivasiPic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
     * Alamat email tujuan undangan sebuah unit.
     *
     * Kolom email memang dibuat khusus untuk notifikasi, tapi form admin
     * saat ini mengisi "Email / Akun SIMRS". Selama keduanya belum
     * diseragamkan, undangan dikirim ke alamat mana pun yang sudah terisi
     * supaya unit dengan data lama tidak kehilangan hak undangnya.
     */
    public static function tujuan(MasterUnit $unit): ?string
    {
        return filled($unit->email) ? $unit->email : $unit->akun_simrs;
    }

    /**
     * Kirim undangan ke semua unit yang akunnya belum aktif.
     *
     * Unit yang alamat emailnya kosong ikut terhitung "dilewat" karena
     * memang tidak ada tujuan pengiriman; perbaikannya sama: admin
     * melengkapi data unit lalu menekan tombol sekali lagi.
     *
     * @return array{terkirim: int, dilewat: int}
     */
    public static function kirimMassal(): array
    {
        $daftarUnit = MasterUnit::query()
            ->where('status_akses', '!=', StatusAksesUnit::AkunAktif->value)
            ->orderBy('kode')
            ->get();

        $terkirim = 0;
        $dilewat = 0;

        foreach ($daftarUnit as $unit) {
            self::kirim($unit) ? $terkirim++ : $dilewat++;
        }

        return compact('terkirim', 'dilewat');
    }

    /**
     * Kirim satu unit undangan beserta tautan aktivasi barunya.
     *
     * Mengembalikan false berarti unit itu tidak boleh diajak: alamat
     * emailnya kosong atau akunnya sudah aktif. Guard status ada di sini
     * (bukan hanya di kirimMassal) karena mengirim ulang undangan ke unit
     * yang sudah berakun akan menurunkan statusnya kembali ke
     * UndanganTerkirim dan menimpa tautan yang mungkin sedang dibuka PIC.
     */
    public static function kirim(MasterUnit $unit): bool
    {
        $alamat = self::tujuan($unit);

        if ($alamat === null || $unit->status_akses === StatusAksesUnit::AkunAktif) {
            return false;
        }

        $unit->undangan_token = Str::random(40);
        $unit->undangan_berlaku = now()->addHours(self::UMUR_JAM);
        $unit->status_akses = StatusAksesUnit::UndanganTerkirim;
        $unit->save();

        Notification::route('mail', $alamat)->notify(new UndanganAktivasiPic($unit));

        return true;
    }

    /** Cari unit pemilik sebuah token, apa pun kondisi masa berlakunya. */
    public static function cari(string $token): ?MasterUnit
    {
        return MasterUnit::query()->where('undangan_token', $token)->first();
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
     */
    public static function aktivasi(MasterUnit $unit, string $nama, string $kataSandi): User
    {
        $alamat = self::tujuan($unit);

        // Kolom email users tidak boleh kosong, jadi unit yang alamatnya
        // baru dikosongkan admin harus diajak mengundang ulang, bukan
        // memakai sisa tautan yang sudah terlanjur terkirim.
        if ($alamat === null) {
            throw ValidationException::withMessages([
                'undangan' => 'Alamat email unit ini sudah dikosongkan. Minta admin mengirim undangan baru.',
            ]);
        }

        return DB::transaction(function () use ($unit, $nama, $kataSandi, $alamat): User {
            $pengguna = User::firstOrCreate(
                ['email' => $alamat],
                ['name' => $nama, 'peran' => PeranPengguna::Unit, 'password' => $kataSandi],
            );

            $pengguna->peran = PeranPengguna::Unit;
            $pengguna->save();

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
