<?php

namespace Tests\Feature;

use App\Enums\KategoriPengaduan;
use App\Enums\StatusInvestigasi;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use App\Support\DetailPengaduan;
use App\Support\Sla;
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

        $this->masukSebagaiAdmin();
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
            ->assertSee('Progres Siklus Pengaduan RSUD (Lapis 1 &mdash; Humas Terpadu)')
            ->assertSee('1. Diterima')
            ->assertSee('2. Diproses')
            ->assertSee('3. Perlu Revisi')
            ->assertSee('4. Selesai');
    }

    public function test_tahap_revisi_menyebut_pelapor_bukan_unit(): void
    {
        Pengaduan::factory()->status(StatusPengaduan::Revisi)
            ->create(['kode_tiket' => 'ADUAN-STEP-02']);

        // Tombol yang memindahkan tiket ke tahap ini meminta data ulang dari
        // pelapor, jadi sub-statusnya harus sejalan. Kalau di sini tertulis
        // unit yang butuh info, admin membaca lawan yang salah.
        $this->get(route('admin.pengaduan.show', 'ADUAN-STEP-02'))
            ->assertOk()
            ->assertSee('Menunggu Kelengkapan Pelapor')
            ->assertDontSee('Unit Butuh Info Tambahan');
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
        // Jawaban harus berperan unit. Balasan humas di kolom percakapan
        // tidak menaikkan tiket ke Menunggu Racikan.
        $denganUnit->pesan()->create(['peran' => 'unit', 'isi' => 'Jawaban farmasi sudah masuk.']);

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

        // Tiket tanpa unit memakai Humas sebagai Unit Tujuan, sedangkan tiket
        // yang bertaut tetap menampilkan nama unitnya.
        $this->assertSame('Hubungan Masyarakat', DetailPengaduan::dariKode($tanpaUnit->kode_tiket)->unit['nama']);
        $this->assertSame($unit->namaLengkap(), DetailPengaduan::dariKode($denganUnit->kode_tiket)->unit['nama']);
    }

    public function test_tiket_tanpa_unit_tidak_dihitung_lewat_batas_unit(): void
    {
        // Investigasi yang bisa terlambat hanya milik tiket yang memang
        // didisposisikan ke unit. Tiket lama yang ditangani humas langsung
        // tidak boleh ikut ditandai lewat batas pada panel SLA.
        Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'kode_tiket' => 'ADUAN-HUMAS-02',
            'master_unit_id' => null,
            'created_at' => now()->subWeeks(3),
        ]);

        $this->assertFalse(DetailPengaduan::dariKode('ADUAN-HUMAS-02')->sla['lewatUnit']);
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
            ->assertSee('Keputusan Penanganan Pengaduan')
            ->assertSee('Tahap Pengaduan:')
            ->assertSee('Proses')
            ->getContent();

        // Disposisi unit kini punya endpoint sendiri; pastikan formulirnya
        // menunjuk rute asli dan tidak ada tautan palsu yang tersisa.
        $this->assertStringNotContainsString('href="#', $tampilan);
        $this->assertStringContainsString('action="'.route('admin.pengaduan.disposisi', 'ADUAN-AKSI-01').'"', $tampilan);
    }

    public function test_tiket_yang_tidak_ada_menghasilkan_halaman_not_found(): void
    {
        $this->get(route('admin.pengaduan.show', 'ADUAN-TIDAK-ADA-99'))->assertNotFound();
    }

    public function test_humas_menetapkan_kategori_dan_mencatat_riwayat(): void
    {
        $pengaduan = Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'kode_tiket' => 'ADUAN-KATEGORI-01',
            'kategori' => null,
        ]);

        $this->post(route('admin.pengaduan.kategori', $pengaduan->kode_tiket), [
            'kategori' => KategoriPengaduan::Medis->value,
        ])
            ->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertSessionHas('sukses');

        $pengaduan->refresh();

        $this->assertSame(KategoriPengaduan::Medis, $pengaduan->kategori);

        // Perubahan kategori tidak memindahkan tahap, jadi dari dan ke sama.
        // Penanda is_kategori yang membedakannya dari disposisi biasa.
        $catatan = $pengaduan->riwayatStatus()->reorder('id', 'desc')->first();

        $this->assertTrue($catatan->is_kategori);
        $this->assertSame(StatusPengaduan::Diproses, $catatan->dari);
        $this->assertSame(StatusPengaduan::Diproses, $catatan->ke);
        $this->assertStringContainsString('Pelayanan Medis', $catatan->catatan);
        $this->assertNotNull($catatan->admin_id);
    }

    public function test_kategori_yang_diganti_ulang_menambah_riwayat_baru(): void
    {
        $pengaduan = Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'kode_tiket' => 'ADUAN-KATEGORI-05',
            'kategori' => null,
        ]);

        $this->post(route('admin.pengaduan.kategori', $pengaduan->kode_tiket), [
            'kategori' => KategoriPengaduan::Medis->value,
        ])->assertRedirect();

        $this->post(route('admin.pengaduan.kategori', $pengaduan->kode_tiket), [
            'kategori' => KategoriPengaduan::Fasilitas->value,
        ])->assertRedirect();

        $catatan = $pengaduan->riwayatStatus()->where('is_kategori', true)->get();

        $this->assertCount(2, $catatan);
        $this->assertStringContainsString('Pelayanan Medis', $catatan->first()->catatan);
        $this->assertStringContainsString('Fasilitas & Pelayanan', $catatan->last()->catatan);
    }

    public function test_kategori_tidak_dikenal_ditolak(): void
    {
        $pengaduan = Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-KATEGORI-02',
            'kategori' => null,
        ]);

        $this->from(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->post(route('admin.pengaduan.kategori', $pengaduan->kode_tiket), [
                'kategori' => 'lainnya',
            ])
            ->assertSessionHasErrors('kategori');

        $this->assertNull($pengaduan->fresh()->kategori);
        $this->assertSame(0, $pengaduan->riwayatStatus()->where('is_kategori', true)->count());
    }

    public function test_halaman_detail_menampilkan_kategori_belum_ditentukan(): void
    {
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-KATEGORI-03',
            'kategori' => null,
        ]);

        $tampilan = $this->get(route('admin.pengaduan.show', 'ADUAN-KATEGORI-03'))
            ->assertOk()
            ->assertSee('Kategori Pengaduan')
            ->assertSee('Belum ditentukan')
            ->assertSee('Belum Ditentukan')
            ->getContent();

        $this->assertStringContainsString(
            'action="'.route('admin.pengaduan.kategori', 'ADUAN-KATEGORI-03').'"',
            $tampilan
        );
    }

    public function test_kategori_tidak_mengubah_target_sla(): void
    {
        $pengaduan = Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'kode_tiket' => 'ADUAN-KATEGORI-04',
            'kategori' => null,
        ]);
        $targetAwal = $pengaduan->targetSla();

        $this->post(route('admin.pengaduan.kategori', $pengaduan->kode_tiket), [
            'kategori' => KategoriPengaduan::Medis->value,
        ])->assertRedirect();

        $pengaduan->refresh();

        // Kategori hanya penanda alur, bukan dasar tenggat. Kalau suatu saat
        // ada yang menambahkan perpanjangan khusus per kategori, test ini
        // menangkap janji SLA yang berubah diam-diam ke pelapor.
        $this->assertSame(Sla::hariKerja(), $pengaduan->totalHariKerjaSla());
        $this->assertTrue($pengaduan->targetSla()->equalTo($targetAwal));
    }

    public function test_log_kegiatan_menggabungkan_riwayat_dan_pesan_secara_kronologis(): void
    {
        $this->travelTo('2026-01-05 08:00:00');

        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'kode_tiket' => 'ADUAN-LOG-01',
            'kategori' => null,
        ]);

        $this->travelTo('2026-01-05 08:15:00');
        $tiket->pesan()->create([
            'kanal' => PesanPengaduan::KANAL_PELAPOR,
            'peran' => 'pelapor',
            'isi' => 'Mohon dicek kembali obatnya.',
        ]);

        $this->travelTo('2026-01-05 08:30:00');
        $tiket->riwayatStatus()->create([
            'dari' => StatusPengaduan::Diterima,
            'ke' => StatusPengaduan::Diproses,
            'catatan' => 'Diteruskan ke farmasi.',
        ]);

        $this->travelTo('2026-01-05 08:45:00');
        $tiket->pesan()->create([
            'kanal' => PesanPengaduan::KANAL_UNIT,
            'peran' => 'admin',
            'isi' => 'Mohon telusuri stok obat.',
        ]);

        $kegiatan = DetailPengaduan::dariKode('ADUAN-LOG-01')->kegiatan;

        // Urutan mengikuti waktu kejadian, bukan urutan tabel: pesan pelapor
        // yang masuk di antara dua perpindahan tahap tetap muncul di tengah.
        $this->assertSame(
            ['Tiket pengaduan dibuat', 'Pelapor mengirim pesan', 'Tahap: Diproses', 'Humas mengirim pesan ke unit'],
            array_column($kegiatan, 'judul'),
        );
    }

    public function test_halaman_detail_menampilkan_tab_log_kegiatan(): void
    {
        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'kode_tiket' => 'ADUAN-LOG-02',
            'kategori' => null,
        ]);

        $tiket->riwayatStatus()->create([
            'dari' => StatusPengaduan::Diterima,
            'ke' => StatusPengaduan::Diproses,
            'catatan' => 'Diteruskan ke farmasi.',
        ]);

        $this->get(route('admin.pengaduan.show', 'ADUAN-LOG-02'))
            ->assertOk()
            ->assertSee('Log Kegiatan')
            ->assertSee('Kejadian')
            ->assertSee('Tiket pengaduan dibuat')
            ->assertSee('Tahap: Diproses')
            ->assertSee('Diteruskan ke farmasi.')
            ->assertSee('data-tab="log"', false)
            ->assertSee('data-panel="log"', false)
            // Skrip tab dipasang lewat komponen bersama; tanpa ini tombol
            // tab hanya jadi hiasan karena panelnya tidak pernah bertukar.
            ->assertSee("querySelectorAll('[data-tab]')", false);
    }
}
