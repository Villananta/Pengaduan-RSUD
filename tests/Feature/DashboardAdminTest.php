<?php

namespace Tests\Feature;

use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Support\StatistikDashboard;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);
        StatistikDashboard::lupaCache();
    }

    public function test_beranda_admin_dapat_diakses_tanpa_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Sinkronisasi Sub-status Unit')
            ->assertSee('Beban Resolusi Unit Terbanyak')
            ->assertSee('Status Koneksi SIMRS');
    }

    public function test_beranda_menampilkan_jumlah_pengaduan_per_tahap(): void
    {
        Pengaduan::factory()->count(3)->create();
        Pengaduan::factory()->status(StatusPengaduan::Diproses)->create();

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Kepatuhan SLA');

        $this->assertSame([
            StatusPengaduan::Diterima->value => 3,
            StatusPengaduan::Diproses->value => 1,
            StatusPengaduan::Revisi->value => 0,
            StatusPengaduan::Selesai->value => 0,
        ], StatistikDashboard::jumlahPerTahap());
    }

    public function test_kepatuhan_sla_kosong_saat_belum_ada_pengaduan_selesai(): void
    {
        $this->assertNull(StatistikDashboard::kepatuhanSlaPersen());
        $this->assertNull(StatistikDashboard::rataRataHari());

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Belum ada pengaduan selesai');
    }

    public function test_kepatuhan_sla_dihitung_dari_pengaduan_selesai(): void
    {
        Pengaduan::factory()->selesai(now())->create();
        Pengaduan::factory()->create(['created_at' => now()->subMonths(3)]);
        Pengaduan::factory()->selesai(now()->addDays(30))->create(['created_at' => now()->subMonths(3)]);

        $this->assertSame(50.0, StatistikDashboard::kepatuhanSlaPersen());
    }

    public function test_banner_mendeteksi_tiket_yang_lewat_batas_investigasi_unit(): void
    {
        // Tiket lama yang masih diproses sudah melewati 5 hari kerja investigasi.
        Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'created_at' => now()->subWeeks(3),
            'kode_tiket' => 'ADUAN-LAMA-01',
        ]);

        $ringkasan = StatistikDashboard::ringkasanKritis();

        $this->assertSame(1, $ringkasan['total_lewat']);
        $this->assertSame('ADUAN-LAMA-01', $ringkasan['lewat']->first()['kode']);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Peringatan Kritis SLA')
            ->assertSee('ADUAN-LAMA-01')
            ->assertSee('Lewat 15 hari kerja');
    }

    public function test_tiket_baru_belum_terhitung_sebagai_pelanggaran(): void
    {
        Pengaduan::factory()->create(['created_at' => now()]);

        $this->assertSame(0, StatistikDashboard::ringkasanKritis()['total_lewat']);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Sinkronisasi Dalam Batas');
    }

    public function test_telaah_menghitung_pengaduan_aktif_yang_sudah_dibalas_admin(): void
    {
        $denganBalasan = Pengaduan::factory()->status(StatusPengaduan::Diproses)->create();
        $denganBalasan->pesan()->create(['peran' => 'admin', 'isi' => 'Jawaban unit sudah masuk.']);

        $hanyaPelapor = Pengaduan::factory()->create();
        $hanyaPelapor->pesan()->create(['peran' => 'pelapor', 'isi' => 'Saya lampirkan dokumen tambahan.']);

        Pengaduan::factory()->selesai()->create()->pesan()->create([
            'peran' => 'admin',
            'isi' => 'Sudah selesai diracik.',
        ]);

        $this->assertSame(1, StatistikDashboard::ringkasanKritis()['telaah']);
    }

    public function test_beban_unit_diambil_dari_master_units(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $radiologi = MasterUnit::where('kode', 'IRS-03')->firstOrFail();

        Pengaduan::factory()->count(2)->create(['master_unit_id' => $farmasi->id]);
        Pengaduan::factory()->create(['master_unit_id' => $radiologi->id]);
        Pengaduan::factory()->selesai()->create(['master_unit_id' => $farmasi->id]);

        $beban = StatistikDashboard::unitTerbebani()->keyBy('kode');

        $this->assertSame(2, $beban['IFP-01']->beban_aktif);
        $this->assertSame(1, $beban['IRS-03']->beban_aktif);
        $this->assertSame('IFP-01', $beban->keys()->first());
    }

    public function test_data_dashboard_aman_disimpan_di_cache(): void
    {
        Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'created_at' => now()->subWeeks(3),
            'kode_tiket' => 'ADUAN-CACHE-01',
        ]);

        $ringkasan = StatistikDashboard::ringkasanKritis();

        $this->assertSame('ADUAN-CACHE-01', $ringkasan['lewat']->first()['kode']);

        // Cache hanya boleh berisi array dan nilai sederhana. Objek di dalam
        // cache membuat halaman dashboard gagal saat payload di-unserialize.
        $tersimpan = Cache::get(StatistikDashboard::KUNCI);

        $this->assertIsArray($tersimpan);
        $this->assertSame([], array_filter($tersimpan, 'is_object'));
        $this->assertSame([], array_filter($tersimpan['lewat_tiket'], 'is_object'));
        $this->assertSame('ADUAN-CACHE-01', $tersimpan['lewat_tiket'][0]['kode']);
    }

    public function test_daftar_pengaduan_dapat_disaring_dan_dicari(): void
    {
        $sasaran = Pengaduan::factory()->create(['subjek' => 'Antrean radiologi terlalu lama']);
        Pengaduan::factory()->create(['subjek' => 'Obat tidak diberikan sesuai resep']);

        $this->get(route('admin.pengaduan.index', ['q' => 'Antrean radiologi']))
            ->assertOk()
            ->assertSee($sasaran->kode_tiket)
            ->assertDontSee('Obat tidak diberikan sesuai resep');
    }

    public function test_filter_telaah_hanya_menampilkan_tiket_berbalasan_admin(): void
    {
        $sasaran = Pengaduan::factory()->status(StatusPengaduan::Diproses)->create();
        $sasaran->pesan()->create(['peran' => 'admin', 'isi' => 'Silakan ambil di loket 3.']);

        $tanpaBalasan = Pengaduan::factory()->create();

        $this->get(route('admin.pengaduan.index', ['telaah' => 1]))
            ->assertOk()
            ->assertSee($sasaran->kode_tiket)
            ->assertDontSee($tanpaBalasan->kode_tiket);
    }

    public function test_angka_dashboard_diperbarui_setelah_pengaduan_baru_masuk(): void
    {
        $this->get(route('admin.dashboard'))->assertOk();

        $unit = MasterUnit::where('kode', 'IGD-02')->firstOrFail();

        $this->post(route('pengaduan.store'), [
            'kategori' => 'medis',
            'nama_lengkap' => 'Siti Aminah',
            'nrm' => '98-76-54-32',
            'no_wa' => '081298765432',
            'email' => 'siti@example.com',
            'alamat' => 'Jl. Pahlawan No. 4, Surabaya',
            'waktu_kejadian' => now()->subDay()->format('Y-m-d\TH:i'),
            'unit' => 'Instalasi Gawat Darurat (IGD)',
            'subjek' => 'Perawatan IGD lambat dan tidak ada informasi',
            'deskripsi' => 'Pasien menunggu lebih dari enam jam tanpa ada penjelasan dari petugas.',
            'persetujuan' => '1',
        ])->assertRedirect();

        // Cache harus dibuang oleh controller, jadi angka langsung ikut berubah.
        $this->assertSame(1, StatistikDashboard::jumlahPerTahap()[StatusPengaduan::Diterima->value]);
        $this->assertSame($unit->id, Pengaduan::first()->master_unit_id);

        $this->get(route('admin.dashboard'))->assertOk();
    }
}
