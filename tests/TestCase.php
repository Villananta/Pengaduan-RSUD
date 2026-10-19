<?php

namespace Tests;

use App\Enums\PeranPengguna;
use App\Models\MasterUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Masuk sebagai admin humas lalu lanjutkan uji.
     *
     * Konsol admin kini dibungkus middleware auth + PastikanAdmin, jadi
     * hampir semua uji halaman admin memulai langkah dari sini.
     */
    protected function masukSebagaiAdmin(array $atribut = []): User
    {
        $pengguna = User::factory()->admin()->create($atribut);

        $this->actingAs($pengguna);

        return $pengguna;
    }

    /**
     * Masuk sebagai PIC unit tanpa unit tertentu.
     *
     * Dipakai hanya untuk menguji batas peran (PIC tidak boleh membuka
     * konsol admin), bukan untuk menembak rute ber-{unit}: konsol unit
     * kini mengunci unit di URL ke pemiliknya lewat
     * masukSebagaiPicUntuk().
     */
    protected function masukSebagaiPic(array $atribut = []): User
    {
        $pengguna = User::factory()
            ->state(['peran' => PeranPengguna::Unit])
            ->create($atribut);

        $this->actingAs($pengguna);

        return $pengguna;
    }

    /**
     * Masuk sebagai PIC yang menempel pada satu unit lalu lanjutkan uji.
     *
     * Kunci rute {unit} di grup konsol unit adalah keanggotaan akun, jadi
     * uji yang membuka dashboard atau disposisi wajib memakai akun yang
     * memang tercatat sebagai pengurus unit tersebut.
     */
    protected function masukSebagaiPicUntuk(MasterUnit $unit, array $atribut = []): User
    {
        $pengguna = User::factory()->picUnit($unit)->create($atribut);

        $this->actingAs($pengguna);

        return $pengguna;
    }

    /** Buat PIC yang benar-benar menempel ke unit lewat pivot, tanpa masuk. */
    protected function picUntuk(MasterUnit $unit, array $atribut = []): User
    {
        return User::factory()->picUnit($unit)->create($atribut);
    }
}
