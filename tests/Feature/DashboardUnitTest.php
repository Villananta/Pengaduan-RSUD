<?php

namespace Tests\Feature;

use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Support\StatistikUnit;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);
    }

    public function test_halaman_pilih_unit_dapat_diakses_tanpa_login(): void
    {
        $this->get(route('unit.pilih'))
            ->assertOk()
            ->assertSee('Pilih Unit Layanan')
            ->assertSee('Pintu Masuk Dashboard Unit');
    }

    public function test_pilih_unit_menampilkan_daftar_unit_aktif(): void
    {
        $tampilan = $this->get(route('unit.pilih'))
            ->assertOk()
            ->assertSee('Instalasi Farmasi Pusat')
            ->assertSee('Buka Dashboard')
            ->getContent();

        $this->assertStringContainsString(
            'href="'.route('unit.dashboard', 'IFP-01').'"',
            $tampilan,
        );
    }

    public function test_unit_yang_tidak_aktif_tidak_mendapat_tautan_dashboard(): void
    {
        MasterUnit::updateOrCreate(['kode' => 'IFP-01'], ['aktif' => false]);

        $tampilan = $this->get(route('unit.pilih'))
            ->assertOk()
            ->assertSee('Tidak Aktif')
            ->assertSee('cursor-not-allowed')
            ->getContent();

        // Unit non-aktif tidak boleh ditautkan, apalagi ke tautan palsu.
        $this->assertStringNotContainsString(
            'href="'.route('unit.dashboard', 'IFP-01').'"',
            $tampilan,
        );
    }

    public function test_kode_unit_membuka_dashboard_yang_sesuai(): void
    {
        $this->get(route('unit.dashboard', 'IFP-01'))
            ->assertOk()
            ->assertSee('Instalasi Farmasi Pusat [IFP-01]')
            ->assertSee('Dashboard Unit Kerja')
            ->assertSee('Pengaduan Butuh Tindakan Unit')
            ->assertSee('Pengaduan Terbaru');
    }

    public function test_kode_unit_yang_tidak_ada_menghasilkan_404(): void
    {
        $this->get('/unit/TIDAK-ADA/dashboard')->assertNotFound();
    }

    public function test_dashboard_unit_hanya_menghitung_pengaduan_unit_itu(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $radiologi = MasterUnit::where('kode', 'IRS-03')->firstOrFail();

        Pengaduan::factory()->count(2)->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);
        Pengaduan::factory()->status(StatusPengaduan::Diterima)
            ->create(['master_unit_id' => $radiologi->id]);

        $ringkasan = StatistikUnit::ringkasan($farmasi);

        $this->assertSame(2, $ringkasan['total']);
        $this->assertSame(2, $ringkasan['aktif']);
        $this->assertSame(0, $ringkasan['terlambat']);
        $this->assertSame(0, $ringkasan['mendek']);
        $this->assertSame(2, $ringkasan['per_tahap'][StatusPengaduan::Diproses->value]);
        $this->assertSame(0, $ringkasan['per_tahap'][StatusPengaduan::Diterima->value]);
    }

    public function test_dashboard_unit_tidak_menampilkan_pengaduan_unit_lain(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $radiologi = MasterUnit::where('kode', 'IRS-03')->firstOrFail();

        Pengaduan::factory()->create([
            'master_unit_id' => $farmasi->id,
            'kode_tiket' => 'ADUAN-FARMASI-1',
        ]);

        Pengaduan::factory()->create([
            'master_unit_id' => $radiologi->id,
            'kode_tiket' => 'ADUAN-RADIOLOGI-1',
        ]);

        $this->get(route('unit.dashboard', 'IFP-01'))
            ->assertOk()
            ->assertSee('ADUAN-FARMASI-1')
            ->assertDontSee('ADUAN-RADIOLOGI-1');
    }

    public function test_tiket_aktif_di_unit_lain_tidak_dihitung_sebagai_keterlambatan(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        // Tiket lama di unit lain: tidak boleh membuat banner unit ini berwarna merah.
        MasterUnit::query()->where('kode', '!=', 'IFP-01')->get()
            ->each(function (MasterUnit $unit): void {
                Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
                    'master_unit_id' => $unit->id,
                    'created_at' => now()->subWeeks(4),
                ]);
            });

        Pengaduan::factory()->create(['master_unit_id' => $farmasi->id]);

        $this->get(route('unit.dashboard', 'IFP-01'))
            ->assertOk()
            ->assertSee('Semua Dalam Batas')
            ->assertDontSee('Peringatan: Pengaduan Melewati Batas');
    }

    public function test_banner_mendeteksi_pengaduan_unit_yang_melewati_batas(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        // Hari ke-13, sudah berada di luar ambang 12 hari kerja.
        Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'master_unit_id' => $farmasi->id,
            'kode_tiket' => 'ADUAN-UNIT-TERLAMBAT',
            'created_at' => now()->subWeekdays(12)->setTime(8, 0),
        ]);

        $ringkasan = StatistikUnit::ringkasan($farmasi);

        $this->assertSame(1, $ringkasan['terlambat']);

        $this->get(route('unit.dashboard', 'IFP-01'))
            ->assertOk()
            ->assertSee('Peringatan: Pengaduan Melewati Batas')
            ->assertSee('ADUAN-UNIT-TERLAMBAT')
            ->assertSee('LEWAT BATAS');
    }

    public function test_banner_mengingatkan_pengaduan_yang_mendek_batas(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        // Hari ke-11, tersisa satu hari kerja lagi sebelum batas SLA.
        Pengaduan::factory()->create([
            'master_unit_id' => $farmasi->id,
            'kode_tiket' => 'ADUAN-UNIT-MENDEK',
            'created_at' => now()->subWeekdays(10)->setTime(8, 0),
        ]);

        $this->get(route('unit.dashboard', 'IFP-01'))
            ->assertOk()
            ->assertSee('Pengaduan Mendek Batas SLA')
            ->assertSee('ADUAN-UNIT-MENDEK')
            ->assertSee('Hari ke-11, sisa 1 hari kerja')
            ->assertDontSee('LEWAT BATAS');
    }

    public function test_daftar_mendesak_tidak_menampilkan_tiket_yang_masih_aman(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        Pengaduan::factory()->create([
            'master_unit_id' => $farmasi->id,
            'kode_tiket' => 'ADUAN-UNIT-AMAN',
            'created_at' => now()->subDay(),
        ]);

        $this->assertCount(0, StatistikUnit::mendesak($farmasi));

        $this->get(route('unit.dashboard', 'IFP-01'))
            ->assertOk()
            ->assertSee('Tidak ada pengaduan yang mendekati batas SLA')
            ->assertDontSee('#ADUAN-UNIT-AMAN');
    }

    public function test_tautan_lihat_semua_membawa_filter_unit_yang_sedang_dibuka(): void
    {
        $tampilan = $this->get(route('unit.dashboard', 'IFP-01'))
            ->assertOk()
            ->assertSee('Lihat Semua')
            ->getContent();

        $this->assertStringContainsString(
            'href="'.route('admin.pengaduan.index', ['unit' => 'IFP-01']).'"',
            $tampilan,
        );
    }

    public function test_dashboard_unit_tidak_memakai_tautan_palsu(): void
    {
        Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'master_unit_id' => MasterUnit::where('kode', 'IFP-01')->firstOrFail()->id,
            'kode_tiket' => 'ADUAN-TANPA-TAUTAN-PALSU',
            'created_at' => now()->subWeekdays(14)->setTime(8, 0),
        ]);

        foreach (['unit.pilih', 'unit.dashboard'] as $namaRute) {
            $tampilan = $this->get(route($namaRute, $namaRute === 'unit.dashboard' ? 'IFP-01' : []))
                ->assertOk()
                ->getContent();

            $this->assertStringNotContainsString('href="#', $tampilan);
        }
    }
}
