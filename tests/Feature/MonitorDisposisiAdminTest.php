<?php

namespace Tests\Feature;

use App\Enums\StatusInvestigasi;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Support\MonitorDisposisi;
use App\Support\StatistikDashboard;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
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
            ->assertSee('Tidak ada tiket pada kolom ini');
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

    public function test_tiket_yang_lewat_batas_ditandai_di_kanban(): void
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

        $this->get(route('admin.monitor.index'))
            ->assertOk()
            ->assertSee('ADUAN-LEWAT-BATAS')
            ->assertSee('Lewat Batas Unit');
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
            ->assertDontSee('Permintaan Keputusan Humas')
            ->assertSee('ADUAN-REVISI-01');
    }

    public function test_empat_kartu_metrik_diisi_dan_terbaca(): void
    {
        $monitor = MonitorDisposisi::dariRequest();

        $this->assertCount(4, $monitor->kartu);

        $this->assertSame(
            [
                'Kepatuhan SLA Pengaduan',
                'Tiket Lewat Batas Investigasi',
                'Menunggu Racikan Humas',
                'Rata-rata Penyelesaian',
            ],
            array_column($monitor->kartu, 'label'),
        );

        // Templat mengloop kartu ini, jadi halaman harus benar-benar
        // menampilkan keempatnya.
        $this->get(route('admin.monitor.index'))
            ->assertOk()
            ->assertSee('Kepatuhan SLA Pengaduan')
            ->assertSee('Tiket Lewat Batas Investigasi')
            ->assertSee('Menunggu Racikan Humas')
            ->assertSee('Rata-rata Penyelesaian');
    }

    /**
     * Jumlah tiket pada tiap kolom papan disposisi.
     *
     * Dipakai untuk memastikan keempat kolom saling lepas dan mencakup
     * semua tiket yang sudah ditugaskan. Kalau tidak, satu tiket bisa
     * muncul di dua kolom atau hilang dari papan.
     *
     * @return Collection<string, int>
     */
    private function kolomPapan(): Collection
    {
        $papan = (new \ReflectionMethod(MonitorDisposisi::class, 'papan'))->invoke(null);

        return collect($papan)->pluck('total', 'kunci');
    }

    /** Satu tiket di unit tujuan dengan tahap dan ada-tidaknya balasan unit. */
    private function tiketUnit(MasterUnit $unit, StatusPengaduan $status, bool $dibalas): Pengaduan
    {
        $pengaduan = Pengaduan::factory()->status($status)->create([
            'master_unit_id' => $unit->id,
            'unit' => $unit->nama,
        ]);

        if ($dibalas) {
            $pengaduan->pesan()->create([
                'peran' => 'admin',
                'isi' => 'Klarifikasi dari PIC unit.',
            ]);
        }

        return $pengaduan;
    }

    public function test_kolom_papan_saling_lepas_dan_mencakup_semua_tiket(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        // Seluruh kombinasi tahap dan ada-tidaknya balasan unit, supaya
        // ada tiket yang harus di tiap kolom dan ada yang terbuang dari
        // semua kolom kalau syaratnya salah.
        $tiket = [
            $this->tiketUnit($unit, StatusPengaduan::Diterima, false),
            $this->tiketUnit($unit, StatusPengaduan::Diterima, true),
            $this->tiketUnit($unit, StatusPengaduan::Diproses, false),
            $this->tiketUnit($unit, StatusPengaduan::Diproses, true),
            $this->tiketUnit($unit, StatusPengaduan::Selesai, false),
        ];

        $kolom = $this->kolomPapan();

        $this->assertSame(1, $kolom['belum_dibuka'], 'Diterima tanpa balasan unit.');
        $this->assertSame(2, $kolom['menunggu_racikan'], 'Sudah dibalas unit, tahap apa pun.');
        $this->assertSame(1, $kolom['sedang_investigasi'], 'Diproses tanpa balasan unit.');
        $this->assertSame(1, $kolom['jawaban_unit'], 'Selesai tanpa pesan tetap tampil.');

        $this->assertSame(count($tiket), $kolom->sum());
    }

    public function test_kolom_papan_sejalan_dengan_status_investigasi_lapis_dua(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $this->tiketUnit($unit, StatusPengaduan::Diterima, false);
        $this->tiketUnit($unit, StatusPengaduan::Diterima, true);
        $this->tiketUnit($unit, StatusPengaduan::Diproses, false);
        $this->tiketUnit($unit, StatusPengaduan::Diproses, true);
        $this->tiketUnit($unit, StatusPengaduan::Selesai, false);

        $kolom = $this->kolomPapan();
        $hitung = fn (StatusInvestigasi $status): int => $status->terapkan(Pengaduan::query())->count();

        // Monitor memecah kelompok yang di tab Lapis 2 masih satu status,
        // jadi angkanya boleh berbeda satu tiket, tapi tidak boleh nol.
        $this->assertSame(
            $kolom['belum_dibuka'] + $kolom['sedang_investigasi'],
            $hitung(StatusInvestigasi::SedangInvestigasi),
        );
        $this->assertSame($kolom['menunggu_racikan'], $hitung(StatusInvestigasi::MenungguRacikan));
        $this->assertSame($kolom['jawaban_unit'], $hitung(StatusInvestigasi::JawabanUnit));
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
