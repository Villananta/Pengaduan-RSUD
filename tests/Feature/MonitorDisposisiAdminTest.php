<?php

namespace Tests\Feature;

use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Support\Sla;
use App\Support\StatistikDashboard;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorDisposisiAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);
        StatistikDashboard::lupaCache();
    }

    public function test_halaman_monitor_dapat_diakses_tanpa_login(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-MONITOR-01',
            'master_unit_id' => $unit->id,
            'unit' => $unit->nama,
            'nama_lengkap' => 'Slamet Riyadi',
        ]);

        $this->get(route('admin.monitor.index'))
            ->assertOk()
            ->assertSee('Monitor Disposisi &amp; SLA Unit', false)
            ->assertSee('Ringkasan Status Disposisi Unit Aktif (Lapis 2)')
            ->assertSee('Daftar Eskalasi &amp; Kritis Batas '.Sla::hariInvestigasi().' Hari Kerja', false)
            ->assertSee('ADUAN-MONITOR-01')
            ->assertSee('Slamet Riyadi');
    }

    public function test_menu_monitor_kini_mengarah_ke_halamannya(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.monitor.index'), false);
    }

    public function test_halaman_kosong_menampilkan_pesan_bukan_tabel_kosong(): void
    {
        $this->get(route('admin.monitor.index'))
            ->assertOk()
            ->assertSee('Tidak ada tiket yang cocok dengan pilihan saring ini.')
            ->assertSee('Belum ada unit yang punya pengaduan selesai');
    }

    public function test_tiket_tanpa_disposisi_unit_tidak_ikut_dihitung(): void
    {
        // Pengaduan yang ditangani humas langsung tidak pernah menyentuh
        // MASTER_UNITS, jadi tidak boleh muncul di papan disposisi unit.
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-LANGSUNG-01',
            'master_unit_id' => null,
            'unit' => 'Instalasi Rawat Jalan (Poliklinik)',
        ]);

        $this->get(route('admin.monitor.index'))
            ->assertOk()
            ->assertDontSee('ADUAN-LANGSUNG-01');
    }

    public function test_belum_dibuka_dipisah_dari_sedang_investigasi(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        Pengaduan::factory()->status(StatusPengaduan::Diterima)->create([
            'kode_tiket' => 'ADUAN-BELUM-DIBUKA',
            'master_unit_id' => $unit->id,
            'unit' => $unit->nama,
        ]);

        $tampilan = $this->get(route('admin.monitor.index'))
            ->assertOk()
            ->assertSee('Belum Dibuka Unit')
            ->assertSee('ADUAN-BELUM-DIBUKA')
            ->getContent();

        // Kedua tiket ini berstatus investigasi yang sama pada tab Lapis 2
        // di daftar pengaduan, jadi pemisahan hanya boleh terjadi di sini.
        $this->assertStringNotContainsString('ADUAN-BELUM-DIBUKA', $this->dariTab('Sedang Investigasi', $tampilan));
    }

    public function test_tiket_yang_lewat_batas_masuk_ke_saring_khusus(): void
    {
        $unit = MasterUnit::where('kode', 'IRS-03')->firstOrFail();

        // Tanggal dibuat mundur supaya diffInWeekdays benar-benar melewati
        // batas investigasi unit tanpa bergantung jam eksekusi test.
        Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'kode_tiket' => 'ADUAN-LEWAT-BATAS',
            'master_unit_id' => $unit->id,
            'unit' => $unit->nama,
            'created_at' => now()->subWeeks(4),
            'updated_at' => now()->subWeeks(4),
        ]);

        $semua = $this->get(route('admin.monitor.index'))->assertOk();
        $lewat = $this->get(route('admin.monitor.index', ['saring' => 'lewat']))->assertOk();

        $semua->assertSee('ADUAN-LEWAT-BATAS');
        $lewat->assertSee('ADUAN-LEWAT-BATAS');
        $lewat->assertSee('Lewat Batas Unit');
    }

    public function test_saring_tidak_dikenal_jatuh_ke_semua(): void
    {
        Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-SARING-01']);

        $this->get(route('admin.monitor.index', ['saring' => 'crafted-tidak-ada']))
            ->assertOk()
            ->assertSee('ADUAN-SARING-01');
    }

    public function test_aksi_yang_belum_ada_tidak_dibuat_menjadi_tautan_palsu(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'kode_tiket' => 'ADUAN-AKSI-01',
            'master_unit_id' => $unit->id,
            'unit' => $unit->nama,
            'created_at' => now()->subWeeks(4),
            'updated_at' => now()->subWeeks(4),
        ]);

        $tampilan = $this->get(route('admin.monitor.index'))
            ->assertOk()
            ->assertSee('href="'.route('admin.pengaduan.show', 'ADUAN-AKSI-01').'"', false)
            ->getContent();

        // Eskalasi ke pimpinan dan kirim dokumen ke unit belum punya
        // endpoint, jadi keduanya harus tampil sebagai tombol nonaktif.
        $this->assertStringNotContainsString('href="#', $tampilan);
        $this->assertStringContainsString('cursor-not-allowed', $tampilan);
    }

    public function test_permintaan_keputusan_menautkan_ke_endpoint_tahap_yang_benar(): void
    {
        $unit = MasterUnit::where('kode', 'DFR-02')->firstOrFail();

        Pengaduan::factory()->status(StatusPengaduan::Revisi)->create([
            'kode_tiket' => 'ADUAN-REVISI-01',
            'master_unit_id' => $unit->id,
            'unit' => $unit->nama,
        ]);

        $this->get(route('admin.monitor.index'))
            ->assertOk()
            ->assertSee('Permintaan Keputusan Humas')
            ->assertSee('ADUAN-REVISI-01')
            ->assertSee('action="'.route('admin.pengaduan.tahap', 'ADUAN-REVISI-01').'"', false)
            ->assertSee('value="'.StatusPengaduan::Diproses->value.'"', false);
    }

    /**
     * Isi satu kolom papan disposisi, diukur dari judulnya.
     *
     * Dipakai untuk memastikan sebuah tiket tidak bocor ke kolom yang
     * salah, karena kedua kolom itu hanya dibedakan oleh sub-status
     * yang tidak terlihat dari teks luar tabel.
     */
    private function dariTab(string $judul, string $tampilan): string
    {
        $awal = strpos($tampilan, '>'.$judul.'<');

        if ($awal === false) {
            return '';
        }

        $akhir = strpos($tampilan, 'End of Papan Disposisi', $awal);

        return $akhir === false ? substr($tampilan, $awal) : substr($tampilan, $awal, $akhir - $awal);
    }
}
