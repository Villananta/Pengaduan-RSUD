<?php

namespace Tests\Feature;

use App\Enums\DisposisiUnit;
use App\Enums\StatusInvestigasi;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Support\DaftarPengaduan;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman disposisi sisi unit: daftar masuk, workspace, dan arsip.
 *
 * Yang diuji bukan hanya tampilannya, tetapi juga penguncian unit: satu unit
 * tidak boleh melihat tiket unit lain, dan jawaban unit harus benar-benar
 * meninggalkan jejak (pesan berperan unit, disposisi unit, hitungan humas).
 */
class DisposisiUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);
    }

    public function test_daftar_disposisi_hanya_menampilkan_pengaduan_unit_tersebut(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $radiologi = MasterUnit::where('kode', 'IRS-03')->firstOrFail();

        $milikFarmasi = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);
        $milikRadiologi = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $radiologi->id]);

        $tampilan = $this->get(route('unit.disposisi.index', $farmasi))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($milikFarmasi->kode_tiket, $tampilan);
        $this->assertStringNotContainsString($milikRadiologi->kode_tiket, $tampilan);
    }

    public function test_detail_tiket_unit_lain_menghasilkan_404(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $radiologi = MasterUnit::where('kode', 'IRS-03')->firstOrFail();

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $radiologi->id]);

        $this->get(route('unit.disposisi.show', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]))
            ->assertNotFound();
    }

    public function test_workspace_unit_menampilkan_form_jawaban_dan_instruksi_disposisi(): void
    {
        $farmasi = MasterUnit::where('kode' => 'IFP-01')->firstOrFail();

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        $tiket->riwayatStatus()->create([
            'dari' => StatusPengaduan::Diterima,
            'ke' => StatusPengaduan::Diproses,
            'catatan' => 'Tolong telusuri catatan pemberian obat pada tiket ini.',
        ]);

        $tiket->pesan()->create([
            'peran' => 'unit',
            'isi' => 'Stok obat sudah diperiksa, tidak ada selisih.',
        ]);

        $tampilan = $this->get(route('unit.disposisi.show', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]))
            ->assertOk()
            ->assertSee('Arahan Disposisi dari Humas')
            ->assertSee('Tolong telusuri catatan pemberian obat pada tiket ini.')
            ->assertSee('Kirim Jawaban Unit')
            ->assertSee('PIC Unit')
            ->assertSee('Cetak Lembar Disposisi')
            ->assertSee('cursor-not-allowed')
            ->getContent();

        $this->assertStringNotContainsString('href="#"', $tampilan);
        $this->assertStringContainsString(
            'action="'.route('unit.disposisi.kirim', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]).'"',
            $tampilan,
        );
    }

    public function test_workspace_unit_yang_instruksi_kosong_menggunakan_pengganti_teks(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diterima)
            ->create(['master_unit_id' => $farmasi->id]);

        $this->get(route('unit.disposisi.show', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]))
            ->assertOk()
            ->assertSee('Humas belum menulis catatan khusus untuk tiket ini.');
    }

    public function test_kirim_jawaban_menyimpan_pesan_dengan_peran_unit(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        $this->post(route('unit.disposisi.kirim', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]), [
            'isi' => 'Pemeriksaan stok dan suhu lemari obat sudah dilakukan, tidak ditemukan selisih.',
        ])->assertRedirect(route('unit.disposisi.show', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]));

        $tiket->refresh();

        $jawaban = $tiket->pesan()->where('peran', 'unit')->first();

        $this->assertNotNull($jawaban);
        $this->assertSame(
            'Pemeriksaan stok dan suhu lemari obat sudah dilakukan, tidak ditemukan selisih.',
            $jawaban->isi,
        );
        $this->assertTrue($jawaban->jawaban());

        // Tahap tiket tidak berubah: unit tidak berwenang memajukan tahap.
        $this->assertSame(StatusPengaduan::Diproses, $tiket->status);
    }

    public function test_kirim_jawaban_memberi_sinyal_ke_konsol_humas_dan_panel_unit(): void
    {
        $farmasi = MasterUnit::where('kode' => 'IFP-01')->firstOrFail();

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        $permintaan = Request::create(route('unit.disposisi.index', $farmasi));

        $daftar = DaftarPengaduan::dariRequest(
            $permintaan,
            terkunci: $farmasi,
            ruteDaftar: 'unit.disposisi.index',
            ruteTiket: 'unit.disposisi.show',
        );

        $this->assertSame(1, $daftar->jumlahInvestigasi[StatusInvestigasi::SedangInvestigasi->value]);
        $this->assertSame(0, $daftar->jumlahInvestigasi[StatusInvestigasi::MenungguRacikan->value]);

        $this->post(route('unit.disposisi.kirim', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]), [
            'isi' => 'Klarifikasi selesai, menunggu racikan humas.',
        ]);

        $this->assertSame(
            DisposisiUnit::JawabanMasuk,
            $farmasi->fresh()->disposisi,
        );

        $setelah = DaftarPengaduan::dariRequest(
            Request::create(route('unit.disposisi.index', $farmasi)),
            terkunci: $farmasi,
            ruteDaftar: 'unit.disposisi.index',
            ruteTiket: 'unit.disposisi.show',
        );

        $this->assertSame(0, $setelah->jumlahInvestigasi[StatusInvestigasi::SedangInvestigasi->value]);
        $this->assertSame(1, $setelah->jumlahInvestigasi[StatusInvestigasi::MenungguRacikan->value]);
    }

    public function test_jawaban_unit_kosong_ditolak_dengan_pesan_indonesia(): void
    {
        $farmasi = MasterUnit::where('kode' => 'IFP-01')->firstOrFail();

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        $this->post(route('unit.disposisi.kirim', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]), [
            'isi' => '',
        ])->assertSessionHasErrors('isi');

        $this->assertSame(
            'Tuliskan jawaban unit lebih dulu sebelum dikirim.',
            session('errors')->first('isi'),
        );

        $this->assertCount(0, $tiket->pesan()->where('peran', 'unit')->get());
    }

    public function test_tiket_yang_sudah_selesai_tidak_menerima_jawaban_unit(): void
    {
        $farmasi = MasterUnit::where('kode' => 'IFP-01')->firstOrFail();

        $tiket = Pengaduan::factory()->selesai()->create(['master_unit_id' => $farmasi->id]);

        $this->post(route('unit.disposisi.kirim', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]), [
            'isi' => 'Jawaban tambahan setelah tiket ditutup.',
        ])->assertSessionHasErrors('isi');

        $this->assertCount(0, $tiket->pesan()->where('peran', 'unit')->get());
    }

    public function test_workspace_tiket_selesai_tidak_menampilkan_form_jawaban(): void
    {
        $farmasi = MasterUnit::where('kode' => 'IFP-01')->firstOrFail();

        $tiket = Pengaduan::factory()->selesai()->create(['master_unit_id' => $farmasi->id]);

        $tampilan = $this->get(route('unit.disposisi.show', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]))
            ->assertOk()
            ->assertSee('formulir jawaban unit ditutup')
            ->getContent();

        $this->assertStringNotContainsString('name="isi"', $tampilan);
        $this->assertStringNotContainsString(
            route('unit.disposisi.kirim', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]),
            $tampilan,
        );
    }

    public function test_halaman_arsip_hanya_menampilkan_tiket_selesai(): void
    {
        $farmasi = MasterUnit::where('kode' => 'IFP-01')->firstOrFail();

        $arsip = Pengaduan::factory()->selesai()->create(['master_unit_id' => $farmasi->id]);
        $berjalan = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        $tampilan = $this->get(route('unit.disposisi.arsip', $farmasi))
            ->assertOk()
            ->assertSee('Riwayat & Arsip Disposisi')
            ->assertSee($arsip->kode_tiket)
            ->getContent();

        $this->assertStringNotContainsString($berjalan->kode_tiket, $tampilan);
        $this->assertStringNotContainsString('href="#"', $tampilan);
    }

    public function test_halaman_unit_tidak_memiliki_tautan_palsu(): void
    {
        $farmasi = MasterUnit::where('kode' => 'IFP-01')->firstOrFail();

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        foreach ([
            route('unit.dashboard', $farmasi),
            route('unit.disposisi.index', $farmasi),
            route('unit.disposisi.show', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]),
            route('unit.disposisi.arsip', $farmasi),
        ] as $halaman) {
            $this->assertStringNotContainsString(
                'href="#"',
                $this->get($halaman)->assertOk()->getContent(),
                'Halaman '.$halaman.' masih memuat tautan palsu.',
            );
        }
    }

    public function test_menu_unit_memiliki_empat_item_dan_workspace_tanpa_tautan_di_luar_detail(): void
    {
        $farmasi = MasterUnit::where('kode' => 'IFP-01')->firstOrFail();

        $tampilan = $this->get(route('unit.disposisi.index', $farmasi))
            ->assertOk()
            ->getContent();

        foreach (['Beranda Unit', 'Daftar Disposisi Masuk', 'Riwayat &amp; Arsip Disposisi'] as $menu) {
            $this->assertStringContainsString($menu, $tampilan);
        }

        // Di luar halaman detail belum ada tiket yang dituju, jadi menu
        // workspace tampil sebagai label mati, bukan sebagai tautan.
        $this->assertMatchesRegularExpression(
            '/<span[^>]*cursor-not-allowed[^>]*>\s*Workspace &amp; Form Jawaban\s*<\/span>/',
            $tampilan,
        );
    }
}
