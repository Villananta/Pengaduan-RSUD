<?php

namespace Tests\Feature;

use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\User;
use App\Support\MonitorDisposisi;
use App\Support\Sla;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Uji halaman Monitor Disposisi & SLA Unit.
 *
 * Halaman ini punya dua panel: tabel beban per unit dan antrian prioritas
 * yang dipaginasi. Keduanya hanya boleh memuat tiket yang benar-benar
 * sudah didisposisikan ke unit aktif, jadi sebagian besar uji di sini
 * memeriksa batas itu.
 */
class MonitorDisposisiAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Tanggal dipatok supaya perhitungan hari kerja dan zona SLA tidak
        // bergeser mengikuti hari test dijalankan.
        $this->travelTo('2026-11-06 09:00:00');
        $this->seed(MasterUnitSeeder::class);

        $this->admin = $this->masukSebagaiAdmin();
    }

    public function test_halaman_monitor_butuh_login(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $this->tiket($unit, [
            'kode_tiket' => 'ADUAN-MONITOR-01',
            'nama_lengkap' => 'Slamet Riyadi',
        ]);

        auth()->logout();

        $this->get(route('admin.monitor.index'))->assertRedirect(route('masuk'));

        $this->actingAs($this->admin);

        $this->get(route('admin.monitor.index'))
            ->assertOk()
            ->assertSee('Monitor Disposisi & SLA Unit', false)
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
            ->assertSee('Tidak ada unit dengan tiket aktif')
            ->assertSee('Tidak ada tiket aktif');
    }

    public function test_tiket_tanpa_disposisi_unit_tidak_ikut_dihitung(): void
    {
        // Pengaduan yang ditangani humas langsung tidak pernah menyentuh
        // master unit, jadi tidak boleh muncul di papan disposisi unit.
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-LANGSUNG-01',
            'master_unit_id' => null,
            'unit' => 'Instalasi Rawat Jalan (Poliklinik)',
        ]);

        $monitor = MonitorDisposisi::dariRequest(1, 50);

        $this->assertSame([], $monitor->unitTable);
        $this->assertSame([], $monitor->priorityQueue);
        $this->assertSame(0, $monitor->totalDitugaskan);

        $this->get(route('admin.monitor.index'))
            ->assertOk()
            ->assertDontSee('ADUAN-LANGSUNG-01');
    }

    public function test_unit_nonaktif_tidak_ikut_ditampilkan(): void
    {
        // Tiket yang menempel pada unit nonaktif menggantung tanpa pihak
        // yang bisa ditagih, jadi tidak boleh masuk hitungan monitor.
        $unit = MasterUnit::factory()->nonaktif()->create();

        $this->tiket($unit, ['kode_tiket' => 'ADUAN-UNIT-MATI']);

        $monitor = MonitorDisposisi::dariRequest(1, 50);

        $this->assertSame([], $monitor->unitTable);
        $this->assertSame([], $monitor->priorityQueue);
        $this->assertSame(0, $monitor->totalDitugaskan);
    }

    public function test_unit_yang_semua_tiketnya_selesai_keluar_dari_tabel_unit(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        Pengaduan::factory()->selesai()->create([
            'kode_tiket' => 'ADUAN-SUDAH-TUTUP',
            'master_unit_id' => $unit->id,
            'unit' => $unit->nama,
        ]);

        $monitor = MonitorDisposisi::dariRequest(1, 50);

        // Tabel unit hanya menghitung unit yang masih punya pekerjaan aktif,
        // tetapi KPI "Total Ditugaskan" tetap menjumlahkan tiket yang sudah
        // ditutup karena halaman ini juga mengawasi arsipnya.
        $this->assertSame([], $monitor->unitTable);
        $this->assertSame([], $monitor->priorityQueue);
        $this->assertSame(1, $monitor->totalDitugaskan);
    }

    public function test_tabel_unit_menghitung_tiket_aktif_selesai_dan_zona_sla(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $this->tiket($unit, [
            'kode_tiket' => 'ADUAN-TEPAT',
            'created_at' => now(),
        ]);
        $this->tiket($unit, [
            'kode_tiket' => 'ADUAN-MENDEK',
            'created_at' => now()->subWeekdays(10),
        ]);
        $this->tiket($unit, [
            'kode_tiket' => 'ADUAN-TELAT',
            'created_at' => now()->subWeekdays(13),
        ]);

        Pengaduan::factory()->selesai(now()->subDays(3))->create([
            'kode_tiket' => 'ADUAN-TUTUP',
            'master_unit_id' => $unit->id,
            'unit' => $unit->nama,
            'created_at' => now()->subWeekdays(5),
        ]);

        $baris = collect(MonitorDisposisi::dariRequest(1, 50)->unitTable)
            ->firstWhere('kode', 'IFP-01');

        $this->assertNotNull($baris);
        $this->assertSame(3, $baris['total_aktif']);
        $this->assertSame(1, $baris['total_selesai']);
        $this->assertSame(1, $baris['overdue']);
        $this->assertSame(1, $baris['at_risk']);
        $this->assertSame(1, $baris['on_track']);

        // Hari ke-1, ke-11, dan ke-14 dirata-rata menjadi 8,7 hari kerja.
        $this->assertSame(8.7, $baris['avg_hari']);
    }

    public function test_tabel_unit_diurutkan_dari_beban_paling_mendesak(): void
    {
        $sibuk = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $santai = MasterUnit::where('kode', 'IRS-03')->firstOrFail();

        $this->tiket($santai, [
            'kode_tiket' => 'ADUAN-SANTAI-01',
            'created_at' => now(),
        ]);

        $this->tiket($sibuk, [
            'kode_tiket' => 'ADUAN-SIBUK-01',
            'created_at' => now()->subWeekdays(13),
        ]);
        $this->tiket($sibuk, [
            'kode_tiket' => 'ADUAN-SIBUK-02',
            'created_at' => now()->subWeekdays(14),
        ]);

        $urutan = array_column(MonitorDisposisi::dariRequest(1, 50)->unitTable, 'kode');

        $this->assertSame(['IFP-01', 'IRS-03'], $urutan);
    }

    public function test_antrian_prioritas_mengurutkan_kasus_berat_dan_terlambat_lebih_dulu(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        // Enam kelompok prioritas, semuanya aktif dan sudah didisposisikan.
        $this->tiket($unit, [
            'kode_tiket' => 'ADUAN-BERAT-TELAT',
            'status' => StatusPengaduan::Diproses,
            'kasus_berat' => true,
            'created_at' => now()->subWeekdays(21),
        ]);
        $this->tiket($unit, [
            'kode_tiket' => 'ADUAN-BERAT-TEPAT',
            'status' => StatusPengaduan::Diproses,
            'kasus_berat' => true,
            'created_at' => now()->subWeekdays(5),
        ]);
        $this->tiket($unit, [
            'kode_tiket' => 'ADUAN-SEDANG-TELAT',
            'status' => StatusPengaduan::Diproses,
            'created_at' => now()->subWeekdays(13),
        ]);
        $this->tiket($unit, [
            'kode_tiket' => 'ADUAN-SEDANG-TEPAT',
            'status' => StatusPengaduan::Revisi,
            'created_at' => now(),
        ]);
        $this->tiket($unit, [
            'kode_tiket' => 'ADUAN-RINGAN-TELAT',
            'status' => StatusPengaduan::Diterima,
            'created_at' => now()->subWeekdays(13),
        ]);
        $this->tiket($unit, [
            'kode_tiket' => 'ADUAN-RINGAN-TEPAT',
            'status' => StatusPengaduan::Diterima,
            'created_at' => now(),
        ]);

        $antrian = MonitorDisposisi::dariRequest(1, 50)->priorityQueue;

        $this->assertSame([
            'ADUAN-BERAT-TELAT',
            'ADUAN-BERAT-TEPAT',
            'ADUAN-SEDANG-TELAT',
            'ADUAN-SEDANG-TEPAT',
            'ADUAN-RINGAN-TELAT',
            'ADUAN-RINGAN-TEPAT',
        ], array_column($antrian, 'kode'));

        $this->assertSame([1, 2, 3, 4, 5, 6], array_column($antrian, 'priority_group'));
    }

    public function test_antrian_prioritas_hanya_memuat_tiket_aktif_berunit(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $this->tiket($unit, ['kode_tiket' => 'ADUAN-MASIH-JALAN']);

        Pengaduan::factory()->selesai()->create([
            'kode_tiket' => 'ADUAN-SELESAI',
            'master_unit_id' => $unit->id,
            'unit' => $unit->nama,
        ]);

        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-TANPA-UNIT',
            'master_unit_id' => null,
        ]);

        $monitor = MonitorDisposisi::dariRequest(1, 50);

        $this->assertSame(['ADUAN-MASIH-JALAN'], array_column($monitor->priorityQueue, 'kode'));
        $this->assertSame(1, $monitor->priorityTotal);
    }

    public function test_antrian_prioritas_dipaginasi_dan_totalnya_tetap_jujur(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        Pengaduan::factory()->count(60)->create([
            'master_unit_id' => $unit->id,
            'unit' => $unit->nama,
            'status' => StatusPengaduan::Diproses,
        ]);

        $halamanKedua = MonitorDisposisi::dariRequest(2, 50);

        $this->assertSame(60, $halamanKedua->priorityTotal);
        $this->assertCount(10, $halamanKedua->priorityQueue);
        $this->assertSame(2, $halamanKedua->priorityCurrentPage);
        $this->assertSame(50, $halamanKedua->priorityPerPage);

        // Nilai per_page di luar pilihan yang didukung jatuh kembali ke 50,
        // supaya URL yang dikarang tidak bisa memaksa render ratusan baris.
        $this->assertSame(50, MonitorDisposisi::dariRequest(1, 7)->priorityPerPage);
    }

    public function test_batas_investigasi_diambil_dari_konfigurasi_sla(): void
    {
        $this->assertSame(
            Sla::hariInvestigasi(),
            MonitorDisposisi::dariRequest(1, 50)->hariInvestigasi,
        );
    }

    public function test_monitor_menautkan_ke_halaman_yang_benar_benar_ada(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $this->tiket($unit, [
            'kode_tiket' => 'ADUAN-AKSI-01',
            'status' => StatusPengaduan::Diproses,
            'created_at' => now()->subWeekdays(13),
        ]);

        $tampilan = $this->get(route('admin.monitor.index'))
            ->assertOk()
            ->assertSee('href="'.route('admin.pengaduan.show', 'ADUAN-AKSI-01').'"', false)
            ->assertSee('href="'.route('admin.pengaduan.index', ['unit' => $unit->kode]).'"', false)
            ->getContent();

        // Tidak ada tombol fitur yang dipalsukan dengan href="#".
        $this->assertStringNotContainsString('href="#"', $tampilan);
    }

    /**
     * Satu tiket di unit tujuan.
     *
     * @param  array<string, mixed>  $atribut
     */
    private function tiket(MasterUnit $unit, array $atribut): Pengaduan
    {
        return Pengaduan::factory()->create($atribut + [
            'master_unit_id' => $unit->id,
            'unit' => $unit->nama,
        ]);
    }
}
