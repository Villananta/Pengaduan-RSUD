<?php

namespace Tests\Feature;

use App\Http\Middleware\PastikanAdmin;
use App\Http\Middleware\PastikanPic;
use App\Http\Middleware\PastikanUnitAktif;
use App\Http\Middleware\PastikanUnitSendiri;
use App\Models\MasterUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected MasterUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = MasterUnit::factory()->create([
            'kode' => 'IFP-01',
            'nama' => 'Instalasi Farmasi',
            'aktif' => true,
        ]);
    }

    // ==================== PastikanAdmin ====================

    public function test_middleware_pastikan_admin_tolak_tamu(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('masuk'));
    }

    public function test_middleware_pastikan_admin_tolak_pic_unit(): void
    {
        $this->masukSebagaiPic();
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_middleware_pastikan_admin_izinkan_admin(): void
    {
        $this->masukSebagaiAdmin();
        $this->get(route('admin.dashboard'))->assertOk();
    }

    // ==================== PastikanPic ====================

    public function test_middleware_pastikan_pic_tolak_tamu(): void
    {
        $this->get(route('unit.beranda'))
            ->assertRedirect(route('masuk'));
    }

    public function test_middleware_pastikan_pic_tolak_admin(): void
    {
        $this->masukSebagaiAdmin();
        $this->get(route('unit.beranda'))->assertForbidden();
    }

    public function test_middleware_pastikan_pic_izinkan_pic(): void
    {
        $this->masukSebagaiPic();
        $this->get(route('unit.beranda'))->assertOk();
    }

    // ==================== PastikanUnitAktif ====================

    public function test_middleware_pastikan_unit_aktif_tolak_unit_nonaktif(): void
    {
        $unitNonaktif = MasterUnit::factory()->create([
            'kode' => 'NON-01',
            'nama' => 'Unit Nonaktif',
            'aktif' => false,
        ]);

        $user = $this->picUntuk($unitNonaktif);
        $this->actingAs($user);

        $this->get(route('unit.dashboard', $unitNonaktif))
            ->assertNotFound();
    }

    public function test_middleware_pastikan_unit_aktif_izinkan_unit_aktif(): void
    {
        $user = $this->picUntuk($this->unit);
        $this->actingAs($user);

        $this->get(route('unit.dashboard', $this->unit))
            ->assertOk();
    }

    // ==================== PastikanUnitSendiri ====================

    public function test_middleware_pastikan_unit_sendiri_tolak_unit_lain(): void
    {
        $unitLain = MasterUnit::factory()->create([
            'kode' => 'LIN-02',
            'nama' => 'Unit Lain',
            'aktif' => true,
        ]);

        $user = $this->picUntuk($this->unit);
        $this->actingAs($user);

        $this->get(route('unit.dashboard', $unitLain))
            ->assertForbidden();
    }

    public function test_middleware_pastikan_unit_sendiri_izinkan_unit_milik(): void
    {
        $user = $this->picUntuk($this->unit);
        $this->actingAs($user);

        $this->get(route('unit.dashboard', $this->unit))
            ->assertOk();
    }

    public function test_middleware_pastikan_unit_sendiri_tolak_pic_tanpa_unit(): void
    {
        $this->masukSebagaiPic(); // PIC tanpa unit
        $this->get(route('unit.dashboard', $this->unit))
            ->assertForbidden();
    }
}
