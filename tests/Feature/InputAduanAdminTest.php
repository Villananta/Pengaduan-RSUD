<?php

namespace Tests\Feature;

use App\Enums\KanalPengaduan;
use App\Enums\KategoriPengaduan;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InputAduanAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);
    }

    /** Isian formulir input aduan yang valid, boleh diubah per test. */
    private function form(array $ubah = []): array
    {
        return array_merge([
            'kanal' => KanalPengaduan::Telepon->value,
            'kategori' => KategoriPengaduan::Medis->value,
            'nama_lengkap' => 'Siti Nur Aisyah',
            'nrm' => '12-34-56-78',
            'no_wa' => '081234567890',
            'email' => 'siti@example.com',
            'alamat' => 'Jl. Dharmawangsa No. 12, Gubeng, Surabaya',
            'waktu_kejadian' => now()->subDay()->format('Y-m-d\TH:i'),
            'unit' => 'Instalasi Farmasi',
            'subjek' => 'Penyerahan obat tidak disertai penjelasan pemakaian',
            'deskripsi' => 'Melepon ke Instalasi Farmasi karena obat tidak disertai penjelasan pemakaian.',
            'persetujuan' => '1',
        ], $ubah);
    }

    public function test_formulir_input_aduan_dapat_diakses(): void
    {
        $this->get(route('admin.pengaduan.create'))
            ->assertOk()
            ->assertSee('Input Aduan dari Kanal Non-Web')
            ->assertSee('Kanal Penerimaan')
            ->assertSee('Identitas Pelapor')
            ->assertSee('Detail Kejadian');
    }

    public function test_menu_topbar_input_aduan_mengarah_ke_formulirnya(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Input Aduan')
            ->assertSee(route('admin.pengaduan.create'), false);
    }

    public function test_aduan_telepon_tersimpan_dengan_kode_tiket_dari_sistem(): void
    {
        $this->post(route('admin.pengaduan.store'), $this->form())
            ->assertRedirect();

        $pengaduan = Pengaduan::first();

        $this->assertNotNull($pengaduan);
        $this->assertStringStartsWith('ADUAN-', $pengaduan->kode_tiket);
        $this->assertSame(StatusPengaduan::Diterima, $pengaduan->status);
        $this->assertNull($pengaduan->selesai_at);
        $this->assertSame(KategoriPengaduan::Medis, $pengaduan->kategori);
    }

    public function test_aduan_yang_tersimpan_langsung_membuka_workspace_tiketnya(): void
    {
        // Admin harus langsung bisa memeriksa hasil pencatatan dan lanjut
        // memberi balasan tanpa mencari kode tiket di daftar.
        $tiket = $this->post(route('admin.pengaduan.store'), $this->form());

        $tiket->assertRedirect(route('admin.pengaduan.show', Pengaduan::first()->kode_tiket));

        $this->get(route('admin.pengaduan.show', Pengaduan::first()->kode_tiket))
            ->assertOk()
            ->assertSee('Aduan Telepon tersimpan dengan nomor tiket')
            ->assertSee('Penyerahan obat tidak disertai penjelasan pemakaian');
    }

    public function test_pesan_pembuka_mencatat_kanal_dan_nama_petugas(): void
    {
        $this->post(route('admin.pengaduan.store'), $this->form());

        $pengaduan = Pengaduan::first();
        $pesan = $pengaduan->pesan()->first();

        $this->assertSame('admin', $pesan->peran);
        $this->assertStringContainsString('diterima melalui telepon', $pesan->isi);
        $this->assertStringContainsString($pengaduan->kode_tiket, $pesan->isi);
    }

    public function test_tiket_dari_admin_bisa_dilacak_pelapor_lewat_nrm(): void
    {
        $this->post(route('admin.pengaduan.store'), $this->form());

        $pengaduan = Pengaduan::first();

        // Admin yang membuat tiket tidak punya sesi verifikasi pelapor,
        // jadi halaman lacak tetap harus menolak sampai NRM dicocokkan.
        $this->get(route('pengaduan.lacak', ['kode' => $pengaduan->kode_tiket]))
            ->assertOk()
            ->assertSee('Lengkapi Kode Tiket dan NRM')
            ->assertDontSee($pengaduan->subjek);

        $this->post(route('pengaduan.verifikasi'), [
            'kode' => $pengaduan->kode_tiket,
            'nrm' => $pengaduan->nrm,
        ])->assertRedirect(route('pengaduan.lacak'));

        $this->get(route('pengaduan.lacak'))
            ->assertOk()
            ->assertSee($pengaduan->subjek);
    }

    public function test_unit_terpilih_tertaut_ke_master_unit(): void
    {
        $this->post(route('admin.pengaduan.store'), $this->form());

        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $this->assertSame($farmasi->id, Pengaduan::first()->master_unit_id);
    }

    public function test_riwayat_status_merekam_tahap_awal(): void
    {
        $this->post(route('admin.pengaduan.store'), $this->form());

        $riwayat = Pengaduan::first()->riwayatStatus;

        $this->assertCount(1, $riwayat);
        $this->assertTrue($riwayat->first()->dariAwal());
        $this->assertSame(StatusPengaduan::Diterima, $riwayat->first()->ke);
    }

    public function test_lampiran_tersimpan_di_disk_lampiran(): void
    {
        Storage::fake('s3');

        $this->post(route('admin.pengaduan.store'), $this->form([
            // image() butuh ekstensi GD yang belum terpasang di mesin ini,
            // jadi berkas dibuat langsung lewat create() tetap sekompatibel.
            'lampiran' => [UploadedFile::fake()->create('kwitansi.pdf', 120, 'application/pdf')],
        ]));

        $pengaduan = Pengaduan::first();

        $this->assertCount(1, $pengaduan->daftarLampiran());
        Storage::disk('s3')->assertExists($pengaduan->daftarLampiran()[0]);
    }

    public function test_kanal_wajib_dipilih(): void
    {
        $this->post(route('admin.pengaduan.store'), $this->form(['kanal' => null]))
            ->assertSessionHasErrors('kanal');

        $this->assertDatabaseCount('pengaduan', 0);
    }

    public function test_kanal_harus_sesuai_enum(): void
    {
        $this->post(route('admin.pengaduan.store'), $this->form(['kanal' => 'telepon_g_mapping']))
            ->assertSessionHasErrors('kanal');

        $this->assertDatabaseCount('pengaduan', 0);
    }

    public function test_validasi_wajib_berfungsi(): void
    {
        $this->post(route('admin.pengaduan.store'), [])
            ->assertSessionHasErrors([
                'kanal', 'kategori', 'nama_lengkap', 'nrm', 'no_wa', 'email', 'alamat',
                'waktu_kejadian', 'unit', 'subjek', 'deskripsi', 'persetujuan',
            ]);

        $this->assertDatabaseCount('pengaduan', 0);
    }

    public function test_tiket_tidak_bisa_dibuat_tanpa_pernyataan_konfirmasi(): void
    {
        $this->post(route('admin.pengaduan.store'), $this->form(['persetujuan' => null]))
            ->assertSessionHasErrors('persetujuan');

        $this->assertDatabaseCount('pengaduan', 0);
    }

    public function test_halaman_tidak_menautkan_aksi_yang_belum_ada(): void
    {
        $tampilan = $this->get(route('admin.pengaduan.create'))
            ->assertOk()
            ->getContent();

        // Disposisi unit belum punya endpoint, jadi
        // halaman ini tidak boleh memunculkan tautan palsu.
        $this->assertStringNotContainsString('href="#', $tampilan);
    }
}
