<?php

namespace Tests\Feature;

use App\Enums\DisposisiUnit;
use App\Enums\KategoriUnit;
use App\Enums\PeranAksesUnit;
use App\Enums\StatusAksesUnit;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Support\DaftarUnit;
use App\Support\DetailPengaduan;
use App\Support\Sla;
use App\Support\StatistikDashboard;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DaftarUnitAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);
        StatistikDashboard::lupaCache();
    }

    public function test_halaman_master_unit_dapat_diakses_tanpa_login(): void
    {
        $this->get(route('admin.unit.index'))
            ->assertOk()
            ->assertSee('Master Data Unit &amp; Instalasi', false)
            ->assertSee('Instalasi Farmasi Pusat')
            ->assertSee('Sebaran Unit per Kategori Layanan')
            ->assertSee('Daftar Unit Terdaftar');
    }

    public function test_menu_master_unit_kini_mengarah_ke_halamannya(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.unit.index'), false);
    }

    public function test_aksi_yang_belum_punya_layanan_ditampilkan_nonaktif(): void
    {
        // Sinkronisasi SIMRS dan kirim undangan butuh layanan luar yang belum
        // ada, jadi keduanya harus tampil nonaktif dan bukan tautan kosong.
        $tampilan = $this->get(route('admin.unit.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Sinkronkan SIMRS', $tampilan);
        $this->assertStringContainsString('Kirim Undangan', $tampilan);
        $this->assertStringContainsString('cursor-not-allowed', $tampilan);
        $this->assertStringNotContainsString('href="#sinkronkan', $tampilan);
        $this->assertStringNotContainsString('href="#undangan', $tampilan);
    }

    public function test_kartu_ringkasan_menghitung_seluruh_unit(): void
    {
        MasterUnit::factory()->nonaktif()->create(['kode' => 'XXX-99', 'nama' => 'Unit Uji Nonaktif']);

        $daftar = DaftarUnit::dariRequest(request());

        $this->assertCount(4, $daftar->kartu);
        $this->assertSame('Total Unit Terdaftar', $daftar->kartu[0]['label']);
        $this->assertSame(10, $daftar->kartu[0]['nilai']);
        $this->assertSame('9 unit aktif, 1 unit nonaktif', $daftar->kartu[0]['sisi']);
    }

    public function test_unit_terputus_dihitung_sebagai_tidak_terhubung(): void
    {
        // Seeder membuat IRJ-05 dengan koneksi dimatikan, jadi kartu unit
        // terhubung harus satu lebih sedikit dari jumlah unit.
        $daftar = DaftarUnit::dariRequest(request());

        $this->assertSame('Unit Terhubung SIMRS', $daftar->kartu[1]['label']);
        $this->assertSame(8, $daftar->kartu[1]['nilai']);
        $this->assertSame('1 unit terputus', $daftar->kartu[1]['sisi']);
    }

    public function test_saring_terhubung_tidak_menghitung_heartbeat_yang_sudah_basi(): void
    {
        // SIMRS unit ini pernah menyimpan heartbeat, lalu berhenti mengirim.
        // Unit semacam ini harus ikut "terputus", bukan tetap terhitung
        // "terhubung" seperti yang dilakukan saring lama yang tidak pernah
        // memeriksa umur heartbeat.
        MasterUnit::factory()->create([
            'kode' => 'UJI-90',
            'nama' => 'Unit Heartbeat Basi',
            'koneksi_simrs' => true,
            'koneksi_simrs_terakhir' => now()->subMinutes(MasterUnit::BATAS_KONEKSI + 5),
            'disposisi' => DisposisiUnit::Terhubung,
        ]);

        $this->get(route('admin.unit.index', ['koneksi' => 'terhubung']))
            ->assertOk()
            ->assertDontSee('Unit Heartbeat Basi')
            ->assertSee('Instalasi Farmasi Pusat');

        $this->get(route('admin.unit.index', ['koneksi' => 'terputus']))
            ->assertOk()
            ->assertSee('Unit Heartbeat Basi');

        // Kartu ringkasan memakai rumus yang sama, jadi angkanya harus
        // mengikuti: unit basi ikut menaikkan jumlah terputus, dan tidak
        // menaikkan jumlah terhubung.
        $daftar = DaftarUnit::dariRequest(request());

        $this->assertSame(8, $daftar->kartu[1]['nilai']);
        $this->assertSame('2 unit terputus', $daftar->kartu[1]['sisi']);
    }

    public function test_saring_belum_ditugaskan_mengenali_unit_baru(): void
    {
        // Kolom disposisi punya nilai bawaan sehingga tidak pernah kosong,
        // jadi saring lama yang mencari disposisi NULL tidak akan pernah
        // menemukan apa pun. Unit baru harus dikenali dari kenyataan bahwa
        // dia belum pernah menyimpan heartbeat dan belum menerima tiket.
        $this->tambahUnitUji();

        // Notifikasi sukses menyebut nama dan kode unit, jadi buktinya bukan
        // nama unit melainkan keterangan jumlah baris dari paginatornya.
        $this->get(route('admin.unit.index', ['koneksi' => 'belum_ditugaskan']))
            ->assertOk()
            ->assertSee('Menampilkan 1 unit (dari 10 terdaftar)')
            ->assertDontSee('Instalasi Farmasi Pusat')
            ->assertDontSee('Instalasi Gawat Darurat');
    }

    public function test_unit_baru_tidak_langsung_dihitung_terputus(): void
    {
        // Saring kartu lama menganggap setiap unit yang koneksi_simrs-nya
        // false sedang terputus, sehingga unit yang baru didaftarkan saja
        // langsung menaikkan jumlah unit rusak di halaman admin.
        $this->tambahUnitUji();

        $daftar = DaftarUnit::dariRequest(request());

        $this->assertSame(8, $daftar->kartu[1]['nilai']);
        $this->assertSame('1 unit terputus', $daftar->kartu[1]['sisi']);
    }

    /** Tambah satu unit lewat form, seperti yang dilakukan admin sungguhan. */
    private function tambahUnitUji(): void
    {
        $this->post(route('admin.unit.store'), [
            'kode' => 'uji-77',
            'nama' => 'Unit Uji Otomatis',
            'kategori' => KategoriUnit::PenunjangKlinis->value,
            'pic' => 'apt. Uji',
            'jabatan_pic' => 'Supervisor Uji',
            'kontak_wa' => '0812-0000-11',
            'jam_layanan' => '24 Jam',
            'peran_akses' => PeranAksesUnit::StafPelaksana->value,
            'status_akses' => StatusAksesUnit::BelumDiundang->value,
            'aktif' => '1',
        ])
            ->assertSessionHas('sukses');
    }

    public function test_saring_kode_dan_nama_menyaring_tabel(): void
    {
        $this->get(route('admin.unit.index', ['q' => 'Farmasi']))
            ->assertOk()
            ->assertSee('Instalasi Farmasi Pusat')
            ->assertSee('Depo Farmasi Rawat Jalan GBPT')
            ->assertDontSee('Instalasi Bedah Terpadu (GBPT)');

        $this->get(route('admin.unit.index', ['q' => 'IGD-02']))
            ->assertOk()
            ->assertSee('Instalasi Gawat Darurat')
            ->assertDontSee('Instalasi Farmasi Pusat');
    }

    public function test_saring_kategori_hanya_menampilkan_kategori_tersebut(): void
    {
        $this->get(route('admin.unit.index', ['kategori' => KategoriUnit::LayananAdministrasi->value]))
            ->assertOk()
            ->assertSee('Loket Kasir &amp; Administrasi Pasien', false)
            ->assertDontSee('Instalasi Gawat Darurat');
    }

    public function test_saring_nilai_asing_tidak_membuat_halaman_kosong(): void
    {
        // Nilai enum yang tidak dikenal harus dibuang, bukan ikut dipakai
        // sebagai kondisi query yang membuat tabel kosong tanpa penjelasan.
        $this->get(route('admin.unit.index', ['kategori' => 'kategori_palsu', 'koneksi' => 'nope']))
            ->assertOk()
            ->assertSee('Instalasi Farmasi Pusat');
    }

    public function test_beban_aktif_menghitung_pengaduan_yang_belum_selesai(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-AKTIF-01',
            'master_unit_id' => $unit->id,
        ]);

        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-AKTIF-02',
            'master_unit_id' => $unit->id,
        ]);

        $lain = MasterUnit::where('kode', 'IGD-02')->firstOrFail();

        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-LAIN-01',
            'master_unit_id' => $lain->id,
        ]);

        $halamanAdaBeban = $this->get(route('admin.unit.index', ['beban' => 'ada']))
            ->assertOk()
            ->getContent();

        $halamanKosong = $this->get(route('admin.unit.index', ['beban' => 'kosong']))
            ->assertOk()
            ->getContent();

        // Kode tiket tidak ikut tampil di master unit, yang diperiksa adalah
        // unit mana yang lolos saring.
        $this->assertStringContainsString('Instalasi Farmasi Pusat', $halamanAdaBeban);
        $this->assertStringNotContainsString('Instalasi Bedah Terpadu', $halamanAdaBeban);
        $this->assertStringContainsString('Instalasi Bedah Terpadu', $halamanKosong);
    }

    public function test_kepatuhan_sla_unit_mengikuti_data_pengaduan(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        // Status disetel manual karena tanggal selesai juga harus diisi
        // manual supaya zona SLA bisa dihitung tanpa menunggu scheduler.
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-SLA-01',
            'master_unit_id' => $unit->id,
            'status' => StatusPengaduan::Selesai,
            'kasus_berat' => false,
            'created_at' => now()->subDays(10),
            'selesai_at' => now()->subDays(8),
        ]);

        StatistikDashboard::lupaCache();

        $this->get(route('admin.unit.index'))
            ->assertOk()
            ->assertSee('100,0%');
    }

    public function test_tiket_lewat_batas_ditandai_pada_baris_unit(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-LEWAT-01',
            'master_unit_id' => $unit->id,
            'created_at' => now()->subDays(Sla::hariInvestigasi() + 5),
        ]);

        $this->get(route('admin.unit.index'))
            ->assertOk()
            ->assertSee('lewat batas');
    }

    public function test_form_tambah_unit_dapat_diakses(): void
    {
        $this->get(route('admin.unit.create'))
            ->assertOk()
            ->assertSee('Tambah Unit Baru')
            ->assertSee('name="kode"', false)
            ->assertSee('name="kontak_wa"', false)
            ->assertSee('name="status_akses"', false);
    }

    public function test_admin_dapat_menambah_unit_baru(): void
    {
        $this->post(route('admin.unit.store'), [
            'kode' => 'uji-77',
            'nama' => 'Unit Uji Otomatis',
            'kategori' => KategoriUnit::PenunjangKlinis->value,
            'pic' => 'apt. Uji',
            'jabatan_pic' => 'Supervisor Uji',
            'kontak_wa' => '0812-0000-11',
            'jam_layanan' => '24 Jam',
            'peran_akses' => PeranAksesUnit::StafPelaksana->value,
            'status_akses' => StatusAksesUnit::BelumDiundang->value,
            'aktif' => '1',
        ])
            ->assertRedirect(route('admin.unit.index'))
            ->assertSessionHas('sukses');

        // Kode dinormalkan jadi huruf kapital supaya tidak ada unit yang
        // sama dengan beda huruf besar-kecil.
        $unit = MasterUnit::where('kode', 'UJI-77')->firstOrFail();

        $this->assertSame('Unit Uji Otomatis', $unit->nama);
        $this->assertTrue($unit->aktif);
        $this->assertSame(KategoriUnit::PenunjangKlinis, $unit->kategori);

        // Unit baru belum pernah ditugaskan tiket, jadi catatan koneksi
        // SIMRS-nya harus kosong dan bukan ditandai hidup.
        $this->assertFalse($unit->koneksi_simrs);
        $this->assertNull($unit->koneksi_simrs_terakhir);
    }

    public function test_kode_unit_wajib_unik(): void
    {
        $this->post(route('admin.unit.store'), [
            'kode' => 'IFP-01',
            'nama' => 'Unit Kode Ganda',
            'kategori' => KategoriUnit::PenunjangKlinis->value,
            'peran_akses' => PeranAksesUnit::KepalaUnit->value,
            'status_akses' => StatusAksesUnit::BelumDiundang->value,
            'aktif' => '1',
        ])
            ->assertSessionHasErrors('kode');

        $this->assertDatabaseMissing('master_units', ['nama' => 'Unit Kode Ganda']);
    }

    public function test_kontak_wa_tidak_menerima_huruf(): void
    {
        $this->post(route('admin.unit.store'), [
            'kode' => 'UJI-78',
            'nama' => 'Unit Wa Salah',
            'kategori' => KategoriUnit::PenunjangKlinis->value,
            'kontak_wa' => 'bukan-nomor',
            'peran_akses' => PeranAksesUnit::KepalaUnit->value,
            'status_akses' => StatusAksesUnit::BelumDiundang->value,
            'aktif' => '1',
        ])->assertSessionHasErrors('kontak_wa');
    }

    public function test_admin_dapat_mengubah_data_unit(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $this->get(route('admin.unit.edit', ['unit' => 'IFP-01']))
            ->assertOk()
            ->assertSee('Perbarui Master Data Unit')
            ->assertSee('Instalasi Farmasi Pusat');

        $this->post(route('admin.unit.update', ['unit' => 'IFP-01']), [
            'kode' => 'IFP-01',
            'nama' => 'Instalasi Farmasi Pusat (Revisi)',
            'kategori' => KategoriUnit::InstalasiMedis->value,
            'pic' => 'apt. Baru',
            'jabatan_pic' => 'Ka. Unit Baru',
            'kontak_wa' => '',
            'ekstensi' => '4102',
            'jam_layanan' => '24 Jam',
            'peran_akses' => PeranAksesUnit::KepalaUnit->value,
            'status_akses' => StatusAksesUnit::AkunAktif->value,
            'aktif' => '1',
        ])
            ->assertRedirect(route('admin.unit.index'));

        $unit->refresh();

        $this->assertSame('Instalasi Farmasi Pusat (Revisi)', $unit->nama);
        $this->assertSame('apt. Baru', $unit->pic);

        // Kolom kontak kosong harus disimpan sebagai null, bukan string
        // kosong, supaya pencarian "belum punya kontak" tidak keliru.
        $this->assertNull($unit->kontak_wa);
    }

    public function test_jam_layanan_yang_dikosongkan_tetap_bisa_disimpan(): void
    {
        // Jam layanan boleh kosong di form. Kalau kolomnya masih melarang
        // null, penyimpanan akan ditolak dan admin mendapat galat 500.
        $data = [
            'nama' => 'Unit Tanpa Jam Layanan',
            'kategori' => KategoriUnit::PenunjangKlinis->value,
            'peran_akses' => PeranAksesUnit::StafPelaksana->value,
            'status_akses' => StatusAksesUnit::BelumDiundang->value,
            'aktif' => '1',
        ];

        $this->post(route('admin.unit.store'), $data + ['kode' => 'uji-88'])
            ->assertRedirect(route('admin.unit.index'))
            ->assertSessionHas('sukses');

        $this->assertNull(MasterUnit::where('kode', 'UJI-88')->value('jam_layanan'));

        $this->post(route('admin.unit.update', ['unit' => 'IFP-01']), $data + [
            'kode' => 'IFP-01',
            'nama' => 'Instalasi Farmasi Pusat',
            'jam_layanan' => '',
        ])
            ->assertRedirect(route('admin.unit.index'))
            ->assertSessionHas('sukses');

        $this->assertNull(MasterUnit::where('kode', 'IFP-01')->value('jam_layanan'));
    }

    public function test_kode_huruf_kecil_menabrak_kode_yang_sudah_ada(): void
    {
        // IFP-01 sudah ada dari seeder. Kalau kode dinormalkan setelah
        // validasi, "ifp-01" lolos cek keunikan lalu ditolak database,
        // sehingga admin mendapat galat 500, bukan pesan validasi.
        $this->post(route('admin.unit.store'), [
            'kode' => 'ifp-01',
            'nama' => 'Farmasi Duplikat',
            'kategori' => KategoriUnit::PenunjangKlinis->value,
            'peran_akses' => PeranAksesUnit::StafPelaksana->value,
            'status_akses' => StatusAksesUnit::BelumDiundang->value,
            'aktif' => '1',
        ])
            ->assertSessionHasErrors('kode');

        $this->assertDatabaseMissing('master_units', ['kode' => 'IFP-01', 'nama' => 'Farmasi Duplikat']);
        $this->assertSame(1, MasterUnit::where('kode', 'IFP-01')->count());
    }

    public function test_kode_huruf_kecil_disimpan_tetap_kapital(): void
    {
        $this->post(route('admin.unit.store'), [
            'kode' => 'uji-99',
            'nama' => 'Unit Kode Kapital',
            'kategori' => KategoriUnit::PenunjangKlinis->value,
            'peran_akses' => PeranAksesUnit::StafPelaksana->value,
            'status_akses' => StatusAksesUnit::BelumDiundang->value,
            'aktif' => '1',
        ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('master_units', ['kode' => 'UJI-99']);
    }

    public function test_unit_yang_tidak_ada_memberi_jawaban_404(): void
    {
        $this->get(route('admin.unit.edit', ['unit' => 'TID-ADA']))
            ->assertNotFound();

        $this->post(route('admin.unit.status', ['unit' => 'TID-ADA']))
            ->assertNotFound();
    }

    public function test_admin_dapat_menonaktifkan_dan_mengaktifkan_kembali_unit(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $this->post(route('admin.unit.status', ['unit' => 'IFP-01']))
            ->assertRedirect();

        $this->assertFalse($unit->refresh()->aktif);

        $this->post(route('admin.unit.status', ['unit' => 'IFP-01']))
            ->assertRedirect();

        $this->assertTrue($unit->refresh()->aktif);
    }

    public function test_unit_nonaktif_tidak_lagi_muncul_sebagai_pilihan_disposisi(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $this->post(route('admin.unit.status', ['unit' => 'IFP-01']));

        $unit->refresh();

        $this->assertFalse($unit->bisaDitugaskan());

        // Sakelar aktif di master data harus terasa juga di halaman detail
        // tiket, karena di situlah unit tujuan dipilih.
        $pengaduan = Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-DISPOSISI-01',
            'master_unit_id' => null,
        ]);

        $pilihan = DetailPengaduan::dariKode($pengaduan->kode_tiket)->pilihanUnit;

        $this->assertFalse($pilihan->contains('kode', 'IFP-01'));
        $this->assertTrue($pilihan->contains('kode', 'IGD-02'));
    }
}
