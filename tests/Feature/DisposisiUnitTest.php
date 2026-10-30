<?php

namespace Tests\Feature;

use App\Enums\DisposisiUnit;
use App\Enums\StatusInvestigasi;
use App\Enums\StatusPengaduan;
use App\Enums\TingkatKeparahan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use App\Support\DaftarPengaduan;
use App\Support\DetailPengaduan;
use App\Support\StatistikDashboard;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Halaman disposisi sisi unit: daftar masuk, workspace, dan arsip.
 *
 * Yang diuji bukan hanya tampilannya, tetapi juga penguncian unit: satu
 * unit tidak boleh melihat tiket unit lain, dan akun PIC hanya boleh
 * menembak URL unit yang menempel padanya. Jawaban unit harus benar-benar
 * meninggalkan jejak (pesan berperan unit, disposisi unit, hitungan humas).
 */
class DisposisiUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);

        $this->masukSebagaiPicUntuk(MasterUnit::where('kode', 'IFP-01')->firstOrFail());
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

    public function test_pic_tidak_bisa_membuka_mauhpun_menjawab_disposisi_unit_lain(): void
    {
        $radiologi = MasterUnit::where('kode', 'IRS-03')->firstOrFail();

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $radiologi->id]);

        // URL unit lain yang diketik langsung tetap ditolak, termasuk
        // jalur tulisnya, supaya jawaban tidak bisa dikirim ke unit orang
        // lain lewat pengakuan yang menyamar sebagai PIC unit tersebut.
        $this->get(route('unit.disposisi.index', $radiologi))->assertForbidden();

        $this->post(route('unit.disposisi.kirim', ['unit' => $radiologi, 'kode' => $tiket->kode_tiket]), [
            'isi' => 'Jawaban yang seharusnya tidak pernah sampai ke unit ini.',
        ])->assertForbidden();

        $this->assertNull($tiket->fresh()->pesan()->where('peran', 'unit')->first());
    }

    public function test_workspace_unit_menampilkan_form_jawaban_dan_instruksi_disposisi(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        $tiket->riwayatStatus()->create([
            'dari' => StatusPengaduan::Diterima,
            'ke' => StatusPengaduan::Diproses,
            'catatan' => 'Tolong telusuri catatan pemberian obat pada tiket ini.',
        ]);

        $tiket->pesan()->create([
            'kanal' => PesanPengaduan::KANAL_UNIT,
            'peran' => 'unit',
            'isi' => 'Stok obat sudah diperiksa, tidak ada selisih.',
        ]);

        $tampilan = $this->get(route('unit.disposisi.show', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]))
            ->assertOk()
            ->assertSee('Arahan Disposisi dari Humas')
            ->assertSee('Tolong telusuri catatan pemberian obat pada tiket ini.')
            ->assertSee('Koordinasi dengan Humas')
            ->assertSee('Stok obat sudah diperiksa, tidak ada selisih.')
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

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        $this->get(route('unit.disposisi.show', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]))
            ->assertOk()
            ->assertSee('Humas belum menulis catatan khusus untuk tiket ini.');
    }

    public function test_tiket_yang_belum_diproses_humas_belum_masuk_antrean_unit(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        // master_unit_id ikut terisi begitu pelapor memilih unit, tapi Humas
        // belum menekan Proses. Tiket seperti ini belum jadi beban unit.
        $belum = Pengaduan::factory()->status(StatusPengaduan::Diterima)
            ->create(['master_unit_id' => $farmasi->id]);

        $sudah = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        $tampilan = $this->get(route('unit.disposisi.index', $farmasi))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString($belum->kode_tiket, $tampilan);
        $this->assertStringContainsString($sudah->kode_tiket, $tampilan);

        $this->get(route('unit.disposisi.show', ['unit' => $farmasi, 'kode' => $belum->kode_tiket]))
            ->assertNotFound();

        $this->post(route('unit.disposisi.kirim', ['unit' => $farmasi, 'kode' => $belum->kode_tiket]), [
            'isi' => 'Jawaban untuk tiket yang belum diproses Humas.',
        ])->assertNotFound();
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
        $this->assertTrue($jawaban->dariUnit());
        $this->assertTrue($jawaban->jalurUnit());

        // Tahap tiket tidak berubah: unit tidak berwenang memajukan tahap.
        $this->assertSame(StatusPengaduan::Diproses, $tiket->status);
    }

    public function test_kirim_jawaban_memberi_sinyal_ke_konsol_humas_dan_panel_unit(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $farmasi->update(['disposisi' => DisposisiUnit::Terhubung]);

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

        // Jawaban unit hanya mengubah status investigasi tiketnya. Kolom
        // disposisi unit tidak ikut ditulis, karena unit bisa memegang
        // beberapa tiket dengan status berbeda sekaligus.
        $this->assertSame(DisposisiUnit::Terhubung, $farmasi->fresh()->disposisi);

        $setelah = DaftarPengaduan::dariRequest(
            Request::create(route('unit.disposisi.index', $farmasi)),
            terkunci: $farmasi,
            ruteDaftar: 'unit.disposisi.index',
            ruteTiket: 'unit.disposisi.show',
        );

        $this->assertSame(0, $setelah->jumlahInvestigasi[StatusInvestigasi::SedangInvestigasi->value]);
        $this->assertSame(1, $setelah->jumlahInvestigasi[StatusInvestigasi::MenungguRacikan->value]);
    }

    public function test_jawaban_unit_menambah_angka_telaah_dan_membuang_cache(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        // Angka dihangatkan lebih dulu supaya nilai lamanya masuk cache.
        $sebelum = StatistikDashboard::ringkasanKritis()['telaah'];

        $this->post(route('unit.disposisi.kirim', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]), [
            'isi' => 'Stok obat sudah diperiksa, tidak ada selisih.',
        ])->assertRedirect();

        $this->assertSame($sebelum + 1, StatistikDashboard::ringkasanKritis()['telaah']);
    }

    public function test_penandaan_kasus_berat_bukan_arahan_disposisi_untuk_unit(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        $tiket->riwayatStatus()->create([
            'dari' => StatusPengaduan::Diterima,
            'ke' => StatusPengaduan::Diproses,
            'catatan' => 'Tolong telusuri catatan pemberian obat pada tiket ini.',
        ]);

        // Penandaan kasus berat menulis baris riwayat dengan tahap asal dan
        // tujuan yang sama, jadi baris itu tidak boleh menutupi arahan
        // disposisi humas yang sebenarnya.
        $tiket->tandaiKasusBerat(true);

        $instruksi = DetailPengaduan::dariKode($tiket->kode_tiket)->instruksiDisposisi();

        $this->assertSame(
            'Tolong telusuri catatan pemberian obat pada tiket ini.',
            $instruksi['catatan'],
        );
    }

    public function test_chat_humas_dan_unit_berjalan_dua_arah(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        // Unit membalas lewat halamannya sendiri...
        $this->post(route('unit.disposisi.kirim', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]), [
            'isi' => 'Hasil telaah: tidak ditemukan selisih stok.',
        ])->assertRedirect();

        // ...lalu humas membalas lewat kanal koordinasi di konsolnya. Langkah
        // ini berganti peran dulu karena konsol humas kini tertutup bagi PIC.
        $this->masukSebagaiAdmin();

        $this->post(route('admin.pengaduan.koordinasi', $tiket->kode_tiket), [
            'isi' => 'Terima kasih, mohon lampirkan berita acara pemeriksaannya.',
        ])->assertRedirect(route('admin.pengaduan.show', $tiket->kode_tiket));

        $tiket->refresh();

        $dariUnit = $tiket->pesan()->where('peran', 'unit')->first();
        $dariHumas = $tiket->pesan()
            ->where('peran', 'admin')
            ->where('kanal', PesanPengaduan::KANAL_UNIT)
            ->first();

        // Kedua arah berdiam di jalur koordinasi, terpisah dari chat pelapor.
        $this->assertTrue($dariUnit?->jalurUnit());
        $this->assertNotNull($dariHumas);
        $this->assertTrue($dariHumas->jalurUnit());

        // Keduanya tampil pada Tab Koordinasi Unit di detail konsol humas.
        $tampilan = $this->get(route('admin.pengaduan.show', $tiket->kode_tiket))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Hasil telaah: tidak ditemukan selisih stok.', $tampilan);
        $this->assertStringContainsString('Terima kasih, mohon lampirkan berita acara pemeriksaannya.', $tampilan);
    }

    public function test_jawaban_unit_kosong_ditolak_dengan_pesan_indonesia(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

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
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $tiket = Pengaduan::factory()->selesai()->create(['master_unit_id' => $farmasi->id]);

        $this->post(route('unit.disposisi.kirim', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]), [
            'isi' => 'Jawaban tambahan setelah tiket ditutup.',
        ])->assertSessionHasErrors('isi');

        $this->assertCount(0, $tiket->pesan()->where('peran', 'unit')->get());
    }

    public function test_workspace_tiket_selesai_tidak_menampilkan_form_jawaban(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

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
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

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

    public function test_tiket_tanpa_kategori_tetap_tampil_di_halaman_unit(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        // Tiket dari portal publik masuk tanpa kategori. Unit tetap harus
        // bisa membaca tiketnya, jadi kolom kategori memakai teks pengganti
        // dan tidak boleh memanggil label() pada nilai null.
        $berjalan = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id, 'kategori' => null]);

        $selesai = Pengaduan::factory()->selesai()
            ->create(['master_unit_id' => $farmasi->id, 'kategori' => null]);

        $this->get(route('unit.disposisi.index', $farmasi))
            ->assertOk()
            ->assertSee($berjalan->kode_tiket)
            ->assertSee('Belum Ditentukan');

        $this->get(route('unit.disposisi.show', ['unit' => $farmasi, 'kode' => $berjalan->kode_tiket]))
            ->assertOk()
            ->assertSee('Belum Ditentukan');

        $this->get(route('unit.disposisi.arsip', $farmasi))
            ->assertOk()
            ->assertSee($selesai->kode_tiket)
            ->assertSee('Belum Ditentukan');
    }

    public function test_halaman_unit_tidak_memiliki_tautan_palsu(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

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

    public function test_chip_semua_disposisi_tetap_total_unit_walau_tab_aktif(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        Pengaduan::factory()->selesai()
            ->create(['master_unit_id' => $farmasi->id]);

        $daftar = function (array $query) use ($farmasi): DaftarPengaduan {
            return DaftarPengaduan::dariRequest(
                Request::create(route('unit.disposisi.index', $farmasi), 'GET', $query),
                terkunci: $farmasi,
                ruteDaftar: 'unit.disposisi.index',
                ruteTiket: 'unit.disposisi.show',
            );
        };

        // Chip "Semua Disposisi" adalah jalan keluar dari tab, jadi angkanya
        // tidak ikut menyempit saat satu tab sedang dibuka.
        $semua = $daftar([]);
        $tab = $daftar(['investigasi' => StatusInvestigasi::SedangInvestigasi->value]);

        $this->assertSame(2, $semua->jumlahInvestigasi['semua']);
        $this->assertSame(2, $tab->jumlahInvestigasi['semua']);
        $this->assertSame(1, $tab->jumlahInvestigasi[StatusInvestigasi::SedangInvestigasi->value]);
        $this->assertSame(2, $tab->ringkas['total']);
    }

    public function test_menu_unit_memiliki_tiga_item_tanpa_workspace(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $tampilan = $this->get(route('unit.disposisi.index', $farmasi))
            ->assertOk()
            ->getContent();

        foreach (['Beranda Unit', 'Daftar Disposisi Masuk', 'Riwayat &amp; Arsip Disposisi'] as $menu) {
            $this->assertStringContainsString($menu, $tampilan);
        }

        // Begitu pengguna berada di dalam dashboard unit, menu "Pilih Unit"
        // dihapus dari topbar supaya tidak ada pintu keluar unit yang
        // memancing orang pindah unit tanpa sengaja.
        $this->assertStringNotContainsString('Pilih Unit', $tampilan);

        // Workspace tiket tidak berdiri sendiri di topbar; halamannya dibuka
        // dari tombol detail pada beranda unit dan daftar disposisi, sama
        // seperti konsol admin.
        $this->assertStringNotContainsString('Workspace &amp; Form Jawaban', $tampilan);
    }

    public function test_unit_nonaktif_tidak_dapat_membuka_disposisi(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        $farmasi->update(['aktif' => false]);

        // Sama seperti dashboard, seluruh rute disposisi harus menolak unit
        // yang sudah dinonaktifkan, bukan sekadar menghilangkannya dari daftar.
        $this->get(route('unit.disposisi.index', $farmasi))->assertNotFound();
        $this->get(route('unit.disposisi.arsip', $farmasi))->assertNotFound();
        $this->get(route('unit.disposisi.show', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]))
            ->assertNotFound();
        $this->post(route('unit.disposisi.kirim', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]), [
            'isi' => 'Jawaban dari unit yang sudah dinonaktifkan.',
        ])->assertNotFound();

        $this->assertCount(0, $tiket->pesan()->where('peran', 'unit')->get());
    }

    public function test_kirim_jawaban_unit_dibatasi_lajunya(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        $url = route('unit.disposisi.kirim', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]);

        // Batasnya 20 permintaan per menit; permintaan ke-21 harus ditolak.
        for ($i = 0; $i < 20; $i++) {
            $this->post($url, ['isi' => 'Jawaban ke-'.$i])->assertRedirect();
        }

        $this->post($url, ['isi' => 'Jawaban ke-21'])->assertStatus(429);
    }

    public function test_log_unit_menyembunyikan_percakapan_pelapor_dan_perubahan_keparahan(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id, 'kategori' => null]);

        $tiket->riwayatStatus()->create([
            'dari' => StatusPengaduan::Diterima,
            'ke' => StatusPengaduan::Diproses,
            'catatan' => 'Mohon telusuri stok obat.',
        ]);

        $tiket->aturTingkatKeparahan(TingkatKeparahan::Berat);

        $tiket->pesan()->create([
            'kanal' => PesanPengaduan::KANAL_PELAPOR,
            'peran' => 'pelapor',
            'isi' => 'Pesan pelapor yang tidak boleh dibaca unit.',
        ]);

        $tiket->pesan()->create([
            'kanal' => PesanPengaduan::KANAL_PELAPOR,
            'peran' => 'admin',
            'isi' => 'Balasan humas yang tidak boleh dibaca unit.',
        ]);

        $tiket->pesan()->create([
            'kanal' => PesanPengaduan::KANAL_UNIT,
            'peran' => 'admin',
            'isi' => 'Arahan humas yang memang untuk unit.',
        ]);

        $jenis = array_column(
            DetailPengaduan::dariKode($tiket->kode_tiket, $farmasi)->kegiatanUnit(),
            'jenis',
        );

        $this->assertContains('Tiket Masuk', $jenis);
        $this->assertContains('Pindah Tahap', $jenis);
        $this->assertContains('Arahan ke Unit', $jenis);
        $this->assertNotContains('Pesan Pelapor', $jenis);
        $this->assertNotContains('Balasan Humas', $jenis);
        $this->assertNotContains('Keparahan', $jenis);

        // Halaman unit membaca daftar tersaring yang sama: lognya tampil,
        // tapi isi percakapan pelapor tidak ikut bocor ke layar unit.
        $this->get(route('unit.disposisi.show', ['unit' => $farmasi, 'kode' => $tiket->kode_tiket]))
            ->assertOk()
            ->assertSee('Log Kegiatan')
            ->assertSee('Tiket pengaduan dibuat')
            ->assertSee('Arahan humas yang memang untuk unit.')
            ->assertDontSee('Pesan pelapor yang tidak boleh dibaca unit.')
            ->assertDontSee('Balasan humas yang tidak boleh dibaca unit.')
            ->assertDontSee('Tingkat keparahan diperbarui');
    }
}
