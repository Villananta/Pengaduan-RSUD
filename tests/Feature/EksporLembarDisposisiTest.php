<?php

namespace Tests\Feature;

use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lembar disposisi: berkas PDF dan pratinjau cetaknya.
 *
 * Berkas ini mengunci dua hal yang mudah putus tanpa disadari. Pertama,
 * lembar disposisi hanya boleh dibuat untuk tiket yang memang sudah
 * ditugaskan ke unit, karena isinya memuat nama PIC dan catatan penugasan.
 * Kedua, tombol "Cetak" pada daftar disposisi sisi unit harus menunjuk ke
 * rute ekspor yang nyata; bila tombolnya jatuh kembali menjadi tombol mati,
 * petugas kehilangan satu-satunya pintu mencetak lembar dari daftar.
 */
class EksporLembarDisposisiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);
    }

    public function test_unit_bisa_mengunduh_lembar_disposisi_tiketnya_sendiri(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $this->masukSebagaiPicUntuk($farmasi);

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        $this->get(route('unit.disposisi.ekspor.disposisi-pdf', [
            'unit' => $farmasi->kode,
            'kode' => $tiket->kode_tiket,
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('lembar-disposisi-'.$tiket->kode_tiket.'-'.now()->format('Ymd').'.pdf');
    }

    public function test_unit_tidak_bisa_mengunduh_lembar_disposisi_tiket_unit_lain(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $radiologi = MasterUnit::where('kode', 'IRS-03')->firstOrFail();
        $this->masukSebagaiPicUntuk($farmasi);

        $tiketRadiologi = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $radiologi->id]);

        // Penguncian berjalan di DetailPengaduan, bukan di controller, jadi
        // tiket unit lain berujung 404 dan tidak membocorkan nama PIC unitnya
        // lewat berkas yang terunduh.
        $this->get(route('unit.disposisi.ekspor.disposisi-pdf', [
            'unit' => $farmasi->kode,
            'kode' => $tiketRadiologi->kode_tiket,
        ]))->assertNotFound();
    }

    public function test_tombol_cetak_pada_daftar_disposisi_mengarah_ke_ekspor_pdf(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $this->masukSebagaiPicUntuk($farmasi);

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        $tampilan = $this->get(route('unit.disposisi.index', $farmasi))
            ->assertOk()
            ->assertSee('Cetak Lembar Disposisi')
            ->getContent();

        $tautanCetak = route('unit.disposisi.ekspor.disposisi-pdf', [
            'unit' => $farmasi->kode,
            'kode' => $tiket->kode_tiket,
        ]);

        $this->assertStringContainsString('href="'.$tautanCetak.'"', $tampilan);
    }

    public function test_admin_bisa_mengunduh_dan_melihat_pratinjau_lembar_disposisi(): void
    {
        $this->masukSebagaiAdmin();

        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        $this->get(route('admin.pengaduan.ekspor.disposisi-pdf', $tiket->kode_tiket))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // Pratinjau memakai berkas yang sama, hanya tujuannya layar bukan
        // unduhan. Pratinjau ini yang dipakai tombol Cetak konsol humas.
        $this->get(route('admin.pengaduan.ekspor.disposisi-preview', $tiket->kode_tiket))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_lembar_disposisi_ditolak_bila_tiket_belum_didisposisikan(): void
    {
        $this->masukSebagaiAdmin();

        $tiketTanpaUnit = Pengaduan::factory()->create(['master_unit_id' => null]);

        $this->get(route('admin.pengaduan.ekspor.disposisi-pdf', $tiketTanpaUnit->kode_tiket))
            ->assertRedirect()
            ->assertSessionHasErrors('kode');
    }

    public function test_daftar_disposisi_unit_bisa_diekspor_ke_excel_dan_pdf(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $this->masukSebagaiPicUntuk($farmasi);

        Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        $this->get(route('unit.disposisi.export.excel', $farmasi))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->get(route('unit.disposisi.export.pdf', $farmasi))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_arsip_unit_bisa_diekspor_ke_excel_dan_pdf(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $this->masukSebagaiPicUntuk($farmasi);

        Pengaduan::factory()->selesai()
            ->create(['master_unit_id' => $farmasi->id]);

        $this->get(route('unit.arsip.export.excel', $farmasi))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->get(route('unit.arsip.export.pdf', $farmasi))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
