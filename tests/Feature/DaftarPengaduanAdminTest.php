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

class DaftarPengaduanAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);
        StatistikDashboard::lupaCache();
    }

    public function test_halaman_daftar_pengaduan_dapat_diakses_tanpa_login(): void
    {
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-DAFTAR-01',
            'nama_lengkap' => 'Siti Aminah',
            'nrm' => '12-34-56-78',
        ]);

        $this->get(route('admin.pengaduan.index'))
            ->assertOk()
            ->assertSee('Daftar Pengaduan Pasien &amp; Disposisi Medis', false)
            ->assertSee('ADUAN-DAFTAR-01')
            ->assertSee('Siti Aminah')
            ->assertSee('12-34-56-78')
            ->assertSee('Lapis 1: Status Utama')
            ->assertSee('Lapis 2: Status di Unit');
    }

    public function test_halaman_kosong_menampilkan_pesan_bukan_tabel_kosong(): void
    {
        $this->get(route('admin.pengaduan.index'))
            ->assertOk()
            ->assertSee('Belum ada pengaduan yang masuk ke portal');
    }

    public function test_pencarian_yang_tidak_bergambar_menampilkan_kondisi_kosong(): void
    {
        Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-ADA-01']);

        $this->get(route('admin.pengaduan.index', ['q' => 'tidak-ada-sama-sekali']))
            ->assertOk()
            ->assertSee('Tidak ada pengaduan yang cocok')
            ->assertDontSee('ADUAN-ADA-01');
    }

    public function test_filter_status_utama_membatasi_hasil_dan_menandai_satu_tab_aktif(): void
    {
        Pengaduan::factory()->status(StatusPengaduan::Diterima)->create(['kode_tiket' => 'ADUAN-DITERIMA-01']);
        Pengaduan::factory()->status(StatusPengaduan::Diproses)->create(['kode_tiket' => 'ADUAN-DIPROSES-01']);

        $tampilan = $this->get(route('admin.pengaduan.index', ['status' => StatusPengaduan::Diproses->value]))
            ->assertOk()
            ->assertSee('ADUAN-DIPROSES-01')
            ->assertDontSee('ADUAN-DITERIMA-01')
            ->getContent();

        // Hanya tab Lapis 1 yang dipilih dan tab "Semua Unit" Lapis 2 yang
        // boleh memakai aria-current; navigasi utama memakai aria-current="page".
        $this->assertSame(2, substr_count($tampilan, 'aria-current="true"'));
    }

    public function test_filter_status_investigasi_memisahkan_tiap_tahap_unit(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-HUMAS-01',
            'master_unit_id' => null,
        ]);

        $sudahDibalas = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['kode_tiket' => 'ADUAN-RACIKAN-01', 'master_unit_id' => $unit->id]);
        $sudahDibalas->pesan()->create(['peran' => 'admin', 'isi' => 'Jawaban farmasi sudah masuk.']);

        Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['kode_tiket' => 'ADUAN-INVESTIGASI-01', 'master_unit_id' => $unit->id]);

        $daftar = fn (StatusInvestigasi $investigasi): string => $this->get(
            route('admin.pengaduan.index', ['investigasi' => $investigasi->value])
        )->assertOk()->getContent();

        $langsungHumas = $daftar(StatusInvestigasi::LangsungHumas);
        $this->assertStringContainsString('ADUAN-HUMAS-01', $langsungHumas);
        $this->assertStringNotContainsString('ADUAN-RACIKAN-01', $langsungHumas);
        $this->assertStringNotContainsString('ADUAN-INVESTIGASI-01', $langsungHumas);

        $racikan = $daftar(StatusInvestigasi::MenungguRacikan);
        $this->assertStringContainsString('ADUAN-RACIKAN-01', $racikan);
        $this->assertStringNotContainsString('ADUAN-HUMAS-01', $racikan);
        $this->assertStringNotContainsString('ADUAN-INVESTIGASI-01', $racikan);

        $sedangInvestigasi = $daftar(StatusInvestigasi::SedangInvestigasi);
        $this->assertStringContainsString('ADUAN-INVESTIGASI-01', $sedangInvestigasi);
        $this->assertStringNotContainsString('ADUAN-RACIKAN-01', $sedangInvestigasi);
    }

    public function test_pencarian_mencakup_tiket_nama_dan_nrm(): void
    {
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-CARI-01',
            'nama_lengkap' => 'Budi Santoso',
            'nrm' => '98-76-54-32',
        ]);
        Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-LAIN-01']);

        $this->get(route('admin.pengaduan.index', ['q' => 'Budi']))
            ->assertOk()
            ->assertSee('ADUAN-CARI-01')
            ->assertDontSee('ADUAN-LAIN-01');

        $this->get(route('admin.pengaduan.index', ['q' => '98-76-54-32']))
            ->assertOk()
            ->assertSee('ADUAN-CARI-01')
            ->assertDontSee('ADUAN-LAIN-01');
    }

    public function test_filter_unit_dan_kategori_membatasi_hasil(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-FARMASI-01',
            'master_unit_id' => $unit->id,
            'kategori' => KategoriPengaduan::Medis,
        ]);
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-PELAYANAN-01',
            'master_unit_id' => null,
            'kategori' => KategoriPengaduan::Fasilitas,
        ]);

        $this->get(route('admin.pengaduan.index', ['unit' => 'IFP-01']))
            ->assertOk()
            ->assertSee('ADUAN-FARMASI-01')
            ->assertDontSee('ADUAN-PELAYANAN-01');

        $this->get(route('admin.pengaduan.index', ['kategori' => KategoriPengaduan::Fasilitas->value]))
            ->assertOk()
            ->assertSee('ADUAN-PELAYANAN-01')
            ->assertDontSee('ADUAN-FARMASI-01');
    }

    public function test_filter_tidak_dikenal_diabaikan(): void
    {
        Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-AMAN-01']);

        $this->get(route('admin.pengaduan.index', [
            'status' => "selesai' or 1=1 --",
            'investigasi' => 'tidak-ada',
            'unit' => 'TIDAK-ADA',
            'kategori' => 'tidak-ada',
        ]))
            ->assertOk()
            ->assertSee('ADUAN-AMAN-01');
    }

    public function test_ukuran_halaman_tidak_dikenal_kembali_ke_default(): void
    {
        Pengaduan::factory()->count(2)->create();

        $tampilan = $this->get(route('admin.pengaduan.index', ['per_halaman' => 1000]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="10" selected', $tampilan);
    }

    public function test_ukuran_halaman_yang_terdaftar_dipakai(): void
    {
        Pengaduan::factory()->count(2)->create();

        $tampilan = $this->get(route('admin.pengaduan.index', ['per_halaman' => 25]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="25" selected', $tampilan);
    }

    public function test_aksi_menampilkan_antrean_tanpa_tautan(): void
    {
        Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-AKSI-01']);

        $tampilan = $this->get(route('admin.pengaduan.index'))
            ->assertOk()
            ->assertSee('Buka Detail', false)
            ->getContent();

        // Detail tiket belum dibangun, jadi tidak boleh ada tautan palsu.
        $this->assertStringNotContainsString('ADUAN-AKSI-01/', $tampilan);
        $this->assertStringNotContainsString('href="#', $tampilan);
    }
}
