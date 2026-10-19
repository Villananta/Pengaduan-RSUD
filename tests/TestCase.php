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
     * Masuk sebagai PIC unit lalu lanjutkan uji.
     *
     * Penguncian per-unit belum dipasang, jadi cukup satu akun PIC tanpa
     * terikat unit pun sudah boleh membuka konsol mana pun di grup /unit.
     */
    protected function masukSebagaiPic(array $atribut = []): User
    {
        $pengguna = User::factory()
            ->state(['peran' => PeranPengguna::Unit])
            ->create($atribut);

        $this->actingAs($pengguna);

        return $pengguna;
    }

    /** Buat PIC yang benar-benar menempel ke unit lewat pivot, tanpa masuk. */
    protected function picUntuk(MasterUnit $unit, array $atribut = []): User
    {
        return User::factory()->picUnit($unit)->create($atribut);
    }
}
