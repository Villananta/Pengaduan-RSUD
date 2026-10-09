<?php

namespace Tests\Feature;

use App\Enums\PeranAksesUnit;
use App\Enums\PeranPengguna;
use App\Models\MasterUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fondasi peran pengguna dan relasi akun ke unit.
 *
 * Login belum dipasang di tahap ini; yang diuji hanya bentuk data dan
 * relasinya supaya bisa langsung dipakai begitu middleware auth menyusul.
 */
class RelasiPenggunaUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_akun_bawaan_factory_berperan_admin(): void
    {
        $pengguna = User::factory()->create();

        $this->assertSame(PeranPengguna::Admin, $pengguna->peran);
        $this->assertTrue($pengguna->berperanAdmin());
        $this->assertFalse($pengguna->berperanUnit());
    }

    public function test_akun_pic_terkait_ke_unit_lewat_pivot(): void
    {
        $unit = MasterUnit::factory()->create();

        $pengguna = User::factory()
            ->picUnit($unit, PeranAksesUnit::SupervisorInvestigasi)
            ->create();

        $this->assertTrue($pengguna->berperanUnit());
        $this->assertTrue($pengguna->terdaftarDiUnit($unit));
        $this->assertSame(1, $pengguna->unit()->count());

        // Peran PIC di dalam unit ikut tersimpan di kolom pivot.
        $this->assertSame(
            PeranAksesUnit::SupervisorInvestigasi->value,
            $pengguna->unit()->first()->pivot->peran_akses,
        );
    }

    public function test_satu_unit_bisa_diurus_lebih_dari_satu_akun(): void
    {
        $unit = MasterUnit::factory()->create();

        User::factory()->picUnit($unit)->count(2)->create();

        $this->assertSame(2, $unit->pengguna()->count());
    }

    public function test_satu_akun_bisa_mengurus_beberapa_unit(): void
    {
        $unitA = MasterUnit::factory()->create();
        $unitB = MasterUnit::factory()->create();

        $pengguna = User::factory()->picUnit($unitA)->create();
        $pengguna->unit()->attach($unitB, ['peran_akses' => PeranAksesUnit::StafPelaksana->value]);

        $this->assertSame(2, $pengguna->unit()->count());
        $this->assertTrue($pengguna->terdaftarDiUnit($unitB));
    }

    public function test_akun_tanpa_relasi_tidak_dianggap_pengurus_unit(): void
    {
        $unit = MasterUnit::factory()->create();
        $pengguna = User::factory()->create();

        $this->assertFalse($pengguna->terdaftarDiUnit($unit));
        $this->assertSame(0, $pengguna->unit()->count());
    }
}
