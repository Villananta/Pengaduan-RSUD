<?php

namespace Database\Seeders;

use App\Enums\PeranPengguna;
use App\Enums\StatusAksesUnit;
use App\Models\MasterUnit;
use App\Models\User;
use App\Support\UndanganPic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Akun PIC untuk setiap unit aktif yang ada di master data.
 *
 * Di produksi akun PIC lahir dari undangan aktivasi, tapi jalur itu menunggu
 * PIC membuka emailnya sendiri. Seeder ini mempersingkat jalur tersebut untuk
 * lingkungan percobaan: tiap unit aktif langsung mendapat satu akun yang sudah
 * menempel di pivot master_unit_user, sehingga petugas bisa membuka konsol
 * unitnya sendiri begitu database di-seed.
 *
 * Alamat akun memakai aturan yang sama dengan alur undangan
 * (UndanganPic::tujuan) supaya tidak ada dua aturan berbeda tentang "alamat
 * PIC unit ini siapa". Unit yang belum punya alamat sama sekali mendapat
 * alamat contoh pic-<kode>@rsud.test yang ditulis ke kolom akun_simrs — kolom
 * yang memang bisa disunting admin lewat form unit.
 *
 * Kata sandinya sama dengan akun contoh lain: "password". Akun yang sudah
 * ada tidak pernah ditimpa, baik sandi maupun perannya, karena bisa saja
 * sudah dipakai petugas sungguhan.
 */
class AkunPicSeeder extends Seeder
{
    public function run(): void
    {
        MasterUnit::query()->aktif()->orderBy('kode')->each(function (MasterUnit $unit): void {
            $pengguna = $this->akunUntuk($unit);

            if ($pengguna === null) {
                return;
            }

            $pengguna->unit()->syncWithoutDetaching([
                $unit->getKey() => ['peran_akses' => $unit->peran_akses->value],
            ]);

            // Status akses digeser seperti pada aktivasi undangan, supaya
            // tombol "Kirim Undangan" berhenti menawarkan unit yang akunnya
            // sudah hidup dan tidak menimpa tautan yang masih beredar.
            $unit->status_akses = StatusAksesUnit::AkunAktif;
            $unit->undangan_token = null;
            $unit->undangan_berlaku = null;
            $unit->save();
        });
    }

    /**
     * Cari atau buat akun PIC milik satu unit.
     *
     * Mengembalikan null ketika alamat unit sudah dipakai akun berperan
     * lain, misalnya akun admin yang kebetulan memakai alamat serupa.
     * Akun admin tidak boleh dipinjam menjadi akun unit karena konsol unit
     * tetap menolaknya, dan status unit akan terlihat aktif padahal tidak
     * ada yang bisa masuk.
     */
    private function akunUntuk(MasterUnit $unit): ?User
    {
        $alamat = $this->alamatUntuk($unit);

        $pengguna = User::query()->where('email', $alamat)->first();

        if ($pengguna === null) {
            return User::create([
                'name' => $unit->pic ?? 'PIC '.$unit->kode,
                'email' => $alamat,
                'peran' => PeranPengguna::Unit,
                'password' => 'password',
            ]);
        }

        if (! $pengguna->berperanUnit()) {
            $this->command?->warn("Alamat {$alamat} (unit {$unit->kode}) sudah dipakai akun lain; unit dilewati.");

            return null;
        }

        return $pengguna;
    }

    /**
     * Alamat email PIC sebuah unit, lengkap dengan pengisian alamat contoh.
     *
     * Alamat contoh ditulis ke akun_simrs, bukan ke kolom email, karena
     * kolom itulah yang tampil di form unit; admin jadi bisa menggantinya
     * dengan alamat sungguhan tanpa perlu menyentuh basis data.
     */
    private function alamatUntuk(MasterUnit $unit): string
    {
        $alamat = UndanganPic::tujuan($unit);

        if ($alamat !== null) {
            return $alamat;
        }

        $alamat = 'pic-'.Str::lower($unit->kode).'@rsud.test';

        $unit->akun_simrs = $alamat;
        $unit->save();

        return $alamat;
    }
}
