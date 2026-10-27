<?php

namespace Tests\Feature;

use App\Enums\StatusPengaduan;
use App\Enums\TingkatKeparahan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Support\Sla;
use App\Support\StatistikDashboard;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Penetapan tingkat keparahan oleh admin humas.
 *
 * Keparahan hanya penanda warna dan filter di konsol. Target SLA tetap
 * dihitung dari App\Support\Sla, jadi uji di sini menjaga agar keputusan
 * admin tidak diam-diam menggeser tenggat yang dijanjikan ke pelapor.
 */
class TingkatKeparahanPengaduanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);
        StatistikDashboard::lupaCache();

        $this->masukSebagaiAdmin();
    }

    public function test_admin_menetapkan_tingkat_keparahan_dan_mencatat_riwayat(): void
    {
        $pengaduan = $this->tiket('ADUAN-KEPARAHAN-01');

        $this->post(route('admin.pengaduan.keparahan', $pengaduan->kode_tiket), [
            'tingkat' => TingkatKeparahan::Berat->value,
        ])
            ->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertSessionHas('sukses');

        $pengaduan->refresh();

        $this->assertSame(TingkatKeparahan::Berat, $pengaduan->tingkat_keparahan);

        // Baris riwayat tidak mengubah tahap, jadi dari dan ke harus sama.
        // Penanda is_keparahan yang membedakannya dari disposisi biasa.
        $catatan = $pengaduan->riwayatStatus()->reorder('id', 'desc')->first();

        $this->assertTrue($catatan->is_keparahan);
        $this->assertSame(StatusPengaduan::Diproses, $catatan->dari);
        $this->assertSame(StatusPengaduan::Diproses, $catatan->ke);
        $this->assertStringContainsString('Berat', $catatan->catatan);
        $this->assertNotNull($catatan->admin_id);
    }

    public function test_tingkat_keparahan_tidak_mengubah_target_sla(): void
    {
        $pengaduan = $this->tiket('ADUAN-KEPARAHAN-02');
        $targetAwal = $pengaduan->targetSla();

        $this->post(route('admin.pengaduan.keparahan', $pengaduan->kode_tiket), [
            'tingkat' => TingkatKeparahan::Berat->value,
        ])->assertRedirect();

        $pengaduan->refresh();

        $this->assertSame(Sla::hariKerja(), $pengaduan->totalHariKerjaSla());
        $this->assertTrue($pengaduan->targetSla()->equalTo($targetAwal));
    }

    public function test_halaman_detail_menampilkan_pilihan_tiga_warna(): void
    {
        $pengaduan = $this->tiket('ADUAN-KEPARAHAN-03');

        $tampilan = $this->get(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertSee('Tingkat Keparahan')
            ->assertSee('Belum ditetapkan')
            ->assertSee('Ringan')
            ->assertSee('Sedang')
            ->assertSee('Berat')
            ->getContent();

        $this->assertStringContainsString(
            'action="'.route('admin.pengaduan.keparahan', $pengaduan->kode_tiket).'"',
            $tampilan
        );

        foreach (TingkatKeparahan::cases() as $tingkat) {
            $this->assertStringContainsString('value="'.$tingkat->value.'"', $tampilan);
        }
    }

    public function test_tingkat_keparahan_tidak_dikenal_ditolak(): void
    {
        $pengaduan = $this->tiket('ADUAN-KEPARAHAN-04');

        $this->from(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->post(route('admin.pengaduan.keparahan', $pengaduan->kode_tiket), [
                'tingkat' => 'parah',
            ])
            ->assertSessionHasErrors('tingkat');

        $this->assertNull($pengaduan->fresh()->tingkat_keparahan);
        $this->assertSame(0, $pengaduan->riwayatStatus()->where('is_keparahan', true)->count());
    }

    public function test_daftar_pengaduan_menyaring_tiap_tingkat_keparahan(): void
    {
        Pengaduan::factory()->keparahan(TingkatKeparahan::Ringan)->create(['kode_tiket' => 'ADUAN-RINGAN-01']);
        Pengaduan::factory()->keparahan(TingkatKeparahan::Sedang)->create(['kode_tiket' => 'ADUAN-SEDANG-01']);
        Pengaduan::factory()->keparahan(TingkatKeparahan::Berat)->create(['kode_tiket' => 'ADUAN-BERAT-01']);
        Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-BELUM-01']);

        $this->get(route('admin.pengaduan.index', ['keparahan' => TingkatKeparahan::Ringan->value]))
            ->assertOk()
            ->assertSee('ADUAN-RINGAN-01')
            ->assertDontSee('ADUAN-SEDANG-01')
            ->assertDontSee('ADUAN-BERAT-01')
            ->assertDontSee('ADUAN-BELUM-01');

        $this->get(route('admin.pengaduan.index', ['keparahan' => TingkatKeparahan::Sedang->value]))
            ->assertOk()
            ->assertSee('ADUAN-SEDANG-01')
            ->assertDontSee('ADUAN-RINGAN-01')
            ->assertDontSee('ADUAN-BERAT-01')
            ->assertDontSee('ADUAN-BELUM-01');

        $this->get(route('admin.pengaduan.index', ['keparahan' => TingkatKeparahan::Berat->value]))
            ->assertOk()
            ->assertSee('ADUAN-BERAT-01')
            ->assertDontSee('ADUAN-RINGAN-01')
            ->assertDontSee('ADUAN-SEDANG-01')
            ->assertDontSee('ADUAN-BELUM-01');

        // Tiket lama yang belum diputuskan admin hanya muncul lewat sentinel
        // 'belum', bukan ikut salah satu warna.
        $this->get(route('admin.pengaduan.index', ['keparahan' => 'belum']))
            ->assertOk()
            ->assertSee('ADUAN-BELUM-01')
            ->assertDontSee('ADUAN-RINGAN-01')
            ->assertDontSee('ADUAN-SEDANG-01')
            ->assertDontSee('ADUAN-BERAT-01');
    }

    public function test_filter_keparahan_diabaikan_di_halaman_unit(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        Pengaduan::factory()
            ->status(StatusPengaduan::Diproses)
            ->keparahan(TingkatKeparahan::Ringan)
            ->create(['kode_tiket' => 'ADUAN-UNIT-RINGAN', 'master_unit_id' => $unit->id]);

        Pengaduan::factory()
            ->status(StatusPengaduan::Diproses)
            ->keparahan(TingkatKeparahan::Berat)
            ->create(['kode_tiket' => 'ADUAN-UNIT-BERAT', 'master_unit_id' => $unit->id]);

        $this->masukSebagaiPicUntuk($unit);

        // Halaman unit tidak menawarkan filter keparahan, jadi parameter
        // yang diselipkan ke URL tidak boleh menyembunyikan tiket apa pun.
        $this->get(route('unit.disposisi.index', $unit).'?keparahan='.TingkatKeparahan::Berat->value)
            ->assertOk()
            ->assertSee('ADUAN-UNIT-RINGAN')
            ->assertSee('ADUAN-UNIT-BERAT');
    }

    private function tiket(string $kode): Pengaduan
    {
        return Pengaduan::factory()
            ->status(StatusPengaduan::Diproses)
            ->create(['kode_tiket' => $kode]);
    }
}
