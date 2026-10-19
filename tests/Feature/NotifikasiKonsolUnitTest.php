<?php

namespace Tests\Feature;

use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\User;
use App\Notifications\PengaduanNudgeUnit;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotifikasiKonsolUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterUnitSeeder::class);
    }

    public function test_pic_hanya_melihat_notifikasi_milik_akun_sendiri(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $picA = $this->masukSebagaiPicUntuk($farmasi);
        $picB = User::factory()->picUnit($farmasi)->create();

        $pengaduan = Pengaduan::factory()->create(['master_unit_id' => $farmasi->id]);
        $picB->notify(new PengaduanNudgeUnit($pengaduan, $farmasi, 'Cek segera'));

        $response = $this->get(route('unit.notifikasi.daftar', $farmasi));
        $response->assertOk()
            ->assertJson(['sukses' => true])
            ->assertJsonCount(0, 'notifikasi');
    }

    public function test_realtime_hanya_mengembalikan_notifikasi_akun_yang_masuk(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $picA = $this->masukSebagaiPicUntuk($farmasi);
        $picB = User::factory()->picUnit($farmasi)->create();

        $pengaduan = Pengaduan::factory()->create(['master_unit_id' => $farmasi->id]);
        $picB->notify(new PengaduanNudgeUnit($pengaduan, $farmasi, 'Cek segera'));

        $response = $this->get(route('unit.notifikasi.realtime', $farmasi));
        $response->assertOk()
            ->assertJson(['sukses' => true, 'total_belum_dibaca' => 0]);
    }

    public function test_tandai_semua_dibaca_bukan_ajax_mengembalikan_redirect(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $pic = $this->masukSebagaiPicUntuk($farmasi);
        $pengaduan = Pengaduan::factory()->create(['master_unit_id' => $farmasi->id]);
        $pic->notify(new PengaduanNudgeUnit($pengaduan, $farmasi, 'Cek segera'));

        $response = $this->post(route('unit.notifikasi.tandai-semua-dibaca', $farmasi));
        $response->assertRedirect();
    }

    public function test_lonceng_muncul_di_halaman_disposisi(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $this->masukSebagaiPicUntuk($farmasi);

        $response = $this->get(route('unit.disposisi.index', $farmasi));
        $response->assertOk()->assertSee('id="notifikasi-wrapper"', false);
    }
}
