<?php

namespace Tests\Feature;

use App\Enums\KategoriPengaduan;
use App\Enums\StatusInvestigasi;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Support\StatistikDashboard;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetailPengaduanAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);
        StatistikDashboard::lupaCache();
    }

    public function test_halaman_detail_menampilkan_identitas_dan_kronologi_pengaduan(): void
    {
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-DETAIL-01',
            'nama_lengkap' => 'Siti Aminah',
            'nrm' => '12-34-56-78',
            'subjek' => 'Keterlambatan surrender obat resep',
            'deskripsi' => 'Resep obat belum diserahkan sampai hari ketiga.',
            'kategori' => KategoriPengaduan::Medis,
            'master_unit_id' => null,
            'unit' => 'Instalasi Farmasi',
        ]);

        $this->get(route('admin.pengaduan.show', 'ADUAN-DETAIL-01'))
            ->assertOk()
            ->assertSee('Detail Tiket ADUAN-DETAIL-01')
            ->assertSee('Kronologi Pengaduan Pelapor')
            ->assertSee('Keterlambatan surrender obat resep')
            ->assertSee('Resep obat belum diserahkan sampai hari ketiga.')
            ->assertSee('Siti Aminah')
            ->assertSee('12-34-56-78');
    }

    public function test_stepper_menampilkan_empat_tahap_pengaduan(): void
    {
        Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['kode_tiket' => 'ADUAN-STEP-01']);

        $this->get(route('admin.pengaduan.show', 'ADUAN-STEP-01'))
            ->assertOk()
            ->assertSee('Progres Siklus Pengaduan RSUD (Lapis 1 &mdash; Humas Terpadu)', false)
            ->assertSee('1. Diterima')
            ->assertSee('2. Diproses')
            ->assertSee('3. Perlu Revisi')
            ->assertSee('4. Selesai');
    }

    public function test_status_lapis_2_membaca_tiket_tanpa_unit_dan_tiket_unit(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $tanpaUnit = Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-HUMAS-01',
            'master_unit_id' => null,
        ]);

        $denganUnit = Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'kode_tiket' => 'ADUAN-UNIT-01',
            'master_unit_id' => $unit->id,
        ]);
        $denganUnit->pesan()->create(['peran' => 'admin', 'isi' => 'Jawaban farmasi sudah masuk.']);

        $humas = $this->get(route('admin.pengaduan.show', $tanpaUnit->kode_tiket))
            ->assertOk()
            ->assertSee(StatusInvestigasi::LangsungHumas->label())
            ->assertSee('Belum ada disposisi ke instalasi teknis')
            ->getContent();

        $racikan = $this->get(route('admin.pengaduan.show', $denganUnit->kode_tiket))
            ->assertOk()
            ->assertSee(StatusInvestigasi::MenungguRacikan->label())
            ->assertSee('Jawaban farmasi sudah masuk.')
            ->getContent();

        $this->assertStringContainsString($unit->namaLengkap(), $humas);
        $this->assertStringContainsString($unit->namaLengkap(), $racikan);
    }

    public function test_percakapan_kosong_menampilkan_pesan_bukan_kotak_kosong(): void
    {
        Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-KOSONG-01']);

        $this->get(route('admin.pengaduan.show', 'ADUAN-KOSONG-01'))
            ->assertOk()
            ->assertSee('Belum ada pesan tambahan dari pelapor maupun balasan admin humas.');
    }

    public function test_halaman_detail_tidak_menautkan_aksi_yang_belum_ada(): void
    {
        Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-AKSI-01']);

        $tampilan = $this->get(route('admin.pengaduan.show', 'ADUAN-AKSI-01'))
            ->assertOk()
            ->assertSee('Panel Formulasi Jawaban Resmi Humas')
            ->assertSee('Keputusan Penanganan Pengaduan')
            ->getContent();

        // Formulasi jawaban, disposisi unit, dan penandaan kasus berat belum
        // punya endpoint, jadi tidak boleh muncul tautan palsu.
        $this->assertStringNotContainsString('href="#', $tampilan);
        $this->assertStringContainsString('cursor-not-allowed', $tampilan);
    }

    public function test_tiket_yang_tidak_ada_menghasilkan_halaman_not_found(): void
    {
        $this->get(route('admin.pengaduan.show', 'ADUAN-TIDAK-ADA-99'))->assertNotFound();
    }

    public function test_menu_detail_pengaduan_tetap_aktif_walaupun_tidak_punya_tautan(): void
    {
        Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-NAV-01']);

        $menu = function (string $html): string {
            preg_match('/<span[^>]*>\s*Detail Pengaduan\s*<\/span>/', $html, $cocok);

            return $cocok[0] ?? '';
        };

        $diBeranda = $menu($this->get(route('admin.dashboard'))->assertOk()->getContent());
        $diDetail = $menu($this->get(route('admin.pengaduan.show', 'ADUAN-NAV-01'))->assertOk()->getContent());

        // Menu ini sudah jadi bagian dari aplikasi, jadi tidak lagi tampil
        // sebagai fitur yang sedang dirancang.
        $this->assertStringNotContainsString('aria-disabled', $diBeranda);
        $this->assertStringNotContainsString('cursor-not-allowed', $diBeranda);

        // Tetap bukan tautan karena tidak ada halaman indeks untuknya.
        $this->assertStringNotContainsString('<a', $diBeranda);

        // Saat sedang membuka tiket, menunya ditandai sebagai halaman aktif.
        $this->assertStringContainsString('aria-current="page"', $diDetail);
    }
}
