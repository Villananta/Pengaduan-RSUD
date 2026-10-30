<?php

namespace Tests\Feature;

use App\Enums\KategoriPengaduan;
use App\Enums\StatusInvestigasi;
use App\Enums\StatusPengaduan;
use App\Enums\TingkatKeparahan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\User;
use App\Support\DaftarPengaduan;
use App\Support\StatistikDashboard;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DaftarPengaduanAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);
        StatistikDashboard::lupaCache();

        $this->admin = $this->masukSebagaiAdmin();
    }

    public function test_halaman_daftar_pengaduan_butuh_login(): void
    {
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-DAFTAR-01',
            'nama_lengkap' => 'Siti Aminah',
            'nrm' => '12-34-56-78',
        ]);

        auth()->logout();

        $this->get(route('admin.pengaduan.index'))->assertRedirect(route('masuk'));

        $this->actingAs($this->admin);

        $this->get(route('admin.pengaduan.index'))
            ->assertOk()
            ->assertSee('Daftar Pengaduan Pasien &amp; Disposisi Medis', false)
            ->assertSee('ADUAN-DAFTAR-01')
            ->assertSee('Siti Aminah')
            ->assertSee('12345678')
            ->assertSee('Lapis 1: Status Utama')
            ->assertSee('Lapis 2: Status di Unit');
    }

    public function test_label_menunggu_unit_memakai_warna_teks_yang_terbaca(): void
    {
        Pengaduan::factory()->status(StatusPengaduan::Diterima)->create(['kode_tiket' => 'ADUAN-DAFTAR-02']);

        $tampilan = $this->get(route('admin.pengaduan.index'))
            ->assertOk()
            ->assertSee('Menunggu Unit')
            ->getContent();

        // Warna teks harus kontras dengan latar. Sekali pakai secondary container
        // (mint) yang sama dengan badgeAdmin Diterima, agar konsisten & terbaca.
        $this->assertStringContainsString('rounded-xl bg-secondary-container text-on-secondary-container', $tampilan);
        $this->assertStringNotContainsString('rounded-xl bg-tertiary-container text-on-tertiary-container', $tampilan);
    }

    public function test_halaman_kosong_menampilkan_pesan_bukan_tabel_kosong(): void
    {
        $this->get(route('admin.pengaduan.index'))
            ->assertOk()
            ->assertSee('Belum ada pengaduan yang masuk ke portal');
    }

    public function test_pencarian_yang_tidak_bergambar_menampilkan_kondisi_kosong(): void
    {
        Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-ADA-01']);

        $this->get(route('admin.pengaduan.index', ['q' => 'tidak-ada-sama-sekali']))
            ->assertOk()
            ->assertSee('Tidak ada pengaduan yang cocok')
            ->assertDontSee('ADUAN-ADA-01');
    }

    public function test_filter_status_utama_membatasi_hasil_dan_menandai_satu_tab_aktif(): void
    {
        Pengaduan::factory()->status(StatusPengaduan::Diterima)->create(['kode_tiket' => 'ADUAN-DITERIMA-01']);
        Pengaduan::factory()->status(StatusPengaduan::Diproses)->create(['kode_tiket' => 'ADUAN-DIPROSES-01']);

        $tampilan = $this->get(route('admin.pengaduan.index', ['status' => StatusPengaduan::Diproses->value]))
            ->assertOk()
            ->assertSee('ADUAN-DIPROSES-01')
            ->assertDontSee('ADUAN-DITERIMA-01')
            ->getContent();

        // Hanya tab Lapis 1 yang dipilih dan tab "Semua Unit" Lapis 2 yang
        // boleh memakai aria-current; navigasi utama memakai aria-current="page".
        $this->assertSame(2, substr_count($tampilan, 'aria-current="true"'));
    }

    public function test_filter_status_investigasi_memisahkan_tiap_tahap_unit(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-HUMAS-01',
            'master_unit_id' => null,
        ]);

        $sudahDibalas = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['kode_tiket' => 'ADUAN-RACIKAN-01', 'master_unit_id' => $unit->id]);
        $sudahDibalas->pesan()->create(['peran' => 'unit', 'isi' => 'Jawaban farmasi sudah masuk.']);

        Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['kode_tiket' => 'ADUAN-INVESTIGASI-01', 'master_unit_id' => $unit->id]);

        $daftar = fn (StatusInvestigasi $investigasi): string => $this->get(
            route('admin.pengaduan.index', ['investigasi' => $investigasi->value])
        )->assertOk()->getContent();

        $langsungHumas = $daftar(StatusInvestigasi::LangsungHumas);
        $this->assertStringContainsString('ADUAN-HUMAS-01', $langsungHumas);
        $this->assertStringNotContainsString('ADUAN-RACIKAN-01', $langsungHumas);
        $this->assertStringNotContainsString('ADUAN-INVESTIGASI-01', $langsungHumas);

        $racikan = $daftar(StatusInvestigasi::MenungguRacikan);
        $this->assertStringContainsString('ADUAN-RACIKAN-01', $racikan);
        $this->assertStringNotContainsString('ADUAN-HUMAS-01', $racikan);
        $this->assertStringNotContainsString('ADUAN-INVESTIGASI-01', $racikan);

        $sedangInvestigasi = $daftar(StatusInvestigasi::SedangInvestigasi);
        $this->assertStringContainsString('ADUAN-INVESTIGASI-01', $sedangInvestigasi);
        $this->assertStringNotContainsString('ADUAN-RACIKAN-01', $sedangInvestigasi);
    }

    public function test_pencarian_mencakup_tiket_nama_dan_nrm(): void
    {
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-CARI-01',
            'nama_lengkap' => 'Budi Santoso',
            'nrm' => '98-76-54-32',
        ]);
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-LAIN-01',
            // Nama dipatok supaya tidak pernah cocok dengan kata kunci
            // "Budi" — nama acak Faker id_ID bisa kebetulan memuat kata itu.
            'nama_lengkap' => 'Dewi Lestari',
        ]);

        $this->get(route('admin.pengaduan.index', ['q' => 'Budi']))
            ->assertOk()
            ->assertSee('ADUAN-CARI-01')
            ->assertDontSee('ADUAN-LAIN-01');

        $this->get(route('admin.pengaduan.index', ['q' => '98-76-54-32']))
            ->assertOk()
            ->assertSee('ADUAN-CARI-01')
            ->assertDontSee('ADUAN-LAIN-01');
    }

    public function test_filter_unit_dan_kategori_membatasi_hasil(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-FARMASI-01',
            'master_unit_id' => $unit->id,
            'kategori' => KategoriPengaduan::Medis,
        ]);
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-PELAYANAN-01',
            'master_unit_id' => null,
            'kategori' => KategoriPengaduan::Fasilitas,
        ]);

        $this->get(route('admin.pengaduan.index', ['unit' => 'IFP-01']))
            ->assertOk()
            ->assertSee('ADUAN-FARMASI-01')
            ->assertDontSee('ADUAN-PELAYANAN-01');

        $this->get(route('admin.pengaduan.index', ['kategori' => KategoriPengaduan::Fasilitas->value]))
            ->assertOk()
            ->assertSee('ADUAN-PELAYANAN-01')
            ->assertDontSee('ADUAN-FARMASI-01');
    }

    public function test_filter_kategori_belum_ditentukan_menampilkan_tiket_tanpa_kategori(): void
    {
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-KATEGORI-ADA',
            'kategori' => KategoriPengaduan::Medis,
        ]);
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-KATEGORI-KOSONG',
            'kategori' => null,
        ]);

        $this->get(route('admin.pengaduan.index', ['kategori' => 'belum']))
            ->assertOk()
            ->assertSee('ADUAN-KATEGORI-KOSONG')
            ->assertSee('Belum Ditentukan')
            ->assertDontSee('ADUAN-KATEGORI-ADA');
    }

    public function test_tautan_ekspor_membawa_filter_yang_sedang_aktif(): void
    {
        Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-EKSPOR-01']);

        // Halaman daftar memakai q untuk pencarian, sedangkan ekspor membaca
        // cari. Kalau pemetaan ini lepas, admin mengunduh berkas yang isinya
        // tidak sama dengan tabel yang sedang dilihatnya.
        $this->get(route('admin.pengaduan.index', [
            'kategori' => 'belum',
            'q' => 'ADUAN-EKSPOR',
        ]))
            ->assertOk()
            ->assertSee(
                route('admin.pengaduan.ekspor.excel', ['kategori' => 'belum', 'cari' => 'ADUAN-EKSPOR']),
            );
    }

    public function test_filter_tidak_dikenal_diabaikan(): void
    {
        Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-AMAN-01']);

        $this->get(route('admin.pengaduan.index', [
            'status' => "selesai' or 1=1 --",
            'investigasi' => 'tidak-ada',
            'unit' => 'TIDAK-ADA',
            'kategori' => 'tidak-ada',
        ]))
            ->assertOk()
            ->assertSee('ADUAN-AMAN-01');
    }

    public function test_chip_keparahan_belum_menghitung_tiket_tanpa_tingkat_keparahan(): void
    {
        Pengaduan::factory()->count(2)->create(['tingkat_keparahan' => null]);
        Pengaduan::factory()->create(['tingkat_keparahan' => TingkatKeparahan::Berat]);

        $daftar = DaftarPengaduan::dariRequest(Request::create(route('admin.pengaduan.index')));

        $this->assertSame(2, $daftar->jumlahKeparahan['belum']);
        $this->assertSame(1, $daftar->jumlahKeparahan[TingkatKeparahan::Berat->value]);
        $this->assertSame(0, $daftar->jumlahKeparahan[TingkatKeparahan::Ringan->value]);
    }

    public function test_ukuran_halaman_tidak_dikenal_kembali_ke_default(): void
    {
        Pengaduan::factory()->count(2)->create();

        $tampilan = $this->get(route('admin.pengaduan.index', ['per_halaman' => 1000]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="10" selected', $tampilan);
    }

    public function test_ukuran_halaman_yang_terdaftar_dipakai(): void
    {
        Pengaduan::factory()->count(2)->create();

        $tampilan = $this->get(route('admin.pengaduan.index', ['per_halaman' => 25]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="25" selected', $tampilan);
    }

    public function test_hanya_aksi_buka_detail_yang_memakai_tautan(): void
    {
        Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-AKSI-01']);

        $tampilan = $this->get(route('admin.pengaduan.index'))
            ->assertOk()
            ->assertSee('Buka Detail', false)
            ->getContent();

        // Halaman detail sudah ada, jadi "Buka Detail" boleh tautan.
        // Nudging belum dibangun, jadi sisanya tetap tombol mati dan tidak
        // boleh muncul tautan palsu.
        $this->assertStringContainsString(
            'href="'.route('admin.pengaduan.show', 'ADUAN-AKSI-01').'"',
            $tampilan
        );
        $this->assertStringNotContainsString('href="#', $tampilan);
    }

    public function test_jumlah_balasan_unit_tidak_menghitung_pesan_pembuka_dan_pelapor(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        // Tiket aktif yang sudah dibalas unit, jadi kolom Lapis 2-nya
        // Menunggu Racikan dan kalimat penghitungnya ikut tampil.
        $pengaduan = Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'kode_tiket' => 'ADUAN-HITUNG-01',
            'master_unit_id' => $unit->id,
            'unit' => $unit->nama,
        ]);

        // Empat pesan tersimpan, tapi hanya dua yang benar-benar balasan unit.
        // Pesan pembuka dibuat otomatis oleh sistem dan balasan pelapor bukan
        // jawaban unit, jadi keduanya tidak boleh menambah hitungan. Balasan
        // humas juga tidak dihitung walaupun perannya admin.
        $pengaduan->pesan()->create([
            'peran' => 'pembuka',
            'isi' => 'Laporan Anda sudah kami terima dan sedang kami periksa.',
        ]);
        $pengaduan->pesan()->create([
            'peran' => 'unit',
            'isi' => 'Instalasi sudah memeriksa catatan pemberian obat.',
        ]);
        $pengaduan->pesan()->create([
            'peran' => 'pelapor',
            'isi' => 'Saya lampirkan foto resep yang saya terima.',
        ]);
        $pengaduan->pesan()->create([
            'peran' => 'unit',
            'isi' => 'Rekam medis sudah disesuaikan, terima kasih.',
        ]);

        $tampilan = $this->get(route('admin.pengaduan.index'))
            ->assertOk()
            ->assertSee('ADUAN-HITUNG-01')
            ->assertSee('Menunggu Racikan')
            ->getContent();

        $this->assertStringContainsString('2 balasan unit masuk, siap diracik humas', $tampilan);
        $this->assertStringNotContainsString('4 balasan unit masuk', $tampilan);
    }

    /**
     * Tiket Revisi memang menunggu pelapor, tapi sistem tidak pernah
     * menghentikan penghitungan harinya.
     *
     * Kalimat lama menjanjikan "Timer dijeda" sementara kolom posisi di
     * layar yang sama tetap menghitung mundur, jadi pelabelannya harus
     * jujur: ditahan menunggu pelapor, bukan dihentikan.
     */
    public function test_tiket_revisi_tidak_disebut_timer_dijeda(): void
    {
        Pengaduan::factory()->status(StatusPengaduan::Revisi)->create([
            'kode_tiket' => 'ADUAN-REVISI-01',
            'created_at' => now()->subWeekdays(14)->setTime(8, 0),
        ]);

        $tampilan = $this->get(route('admin.pengaduan.index'))
            ->assertOk()
            ->assertSee('ditahan menunggu pelapor')
            ->getContent();

        $this->assertStringNotContainsString('Timer dijeda', $tampilan);
    }

    public function test_chip_menyempit_mengikuti_filter_dimensi_lain(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $daftar = function (array $query): DaftarPengaduan {
            return DaftarPengaduan::dariRequest(
                Request::create(route('admin.pengaduan.index'), 'GET', $query),
            );
        };

        // Tiga tiket dengan kombinasi tahap dan keparahan yang berbeda, supaya
        // angka tiap chip tidak sama dengan angka dasarnya.
        foreach ([
            ['ADUAN-CHIP-01', StatusPengaduan::Diterima, null],
            ['ADUAN-CHIP-02', StatusPengaduan::Diterima, TingkatKeparahan::Berat],
            ['ADUAN-CHIP-03', StatusPengaduan::Diproses, null],
        ] as [$kode, $tahap, $tingkat]) {
            Pengaduan::factory()->status($tahap)->create([
                'kode_tiket' => $kode,
                'master_unit_id' => $unit->id,
                'unit' => $unit->nama,
                'tingkat_keparahan' => $tingkat,
            ]);
        }

        // Tab investigasi aktif: chip keparahan harus memakai angka hasil
        // saring, bukan angka seluruh daftar. Sebelumnya "Belum Ditetapkan"
        // menawarkan angka besar lalu menghasilkan tabel kosong saat diklik.
        $investigasi = $daftar(['investigasi' => StatusInvestigasi::SedangInvestigasi->value]);

        $this->assertSame(2, $investigasi->jumlahKeparahan['belum']);
        $this->assertSame(3, $investigasi->jumlahKeparahan['semua']);
        $this->assertSame(
            $investigasi->jumlahKeparahan['belum'],
            $daftar([
                'investigasi' => StatusInvestigasi::SedangInvestigasi->value,
                'keparahan' => 'belum',
            ])->daftar->total(),
        );

        // Sebaliknya, filter keparahan harus menyempitkan chip tahap humas.
        $keparahan = $daftar(['keparahan' => 'belum']);

        $this->assertSame(1, $keparahan->jumlahTahap[StatusPengaduan::Diterima->value]);
        $this->assertSame(1, $keparahan->jumlahTahap[StatusPengaduan::Diproses->value]);
        $this->assertSame(0, $keparahan->jumlahTahap[StatusPengaduan::Selesai->value]);
        $this->assertSame(2, $keparahan->jumlahTahap['semua']);
        $this->assertSame(
            $keparahan->jumlahTahap[StatusPengaduan::Diterima->value],
            $daftar(['keparahan' => 'belum', 'status' => StatusPengaduan::Diterima->value])->daftar->total(),
        );

        // Strip konteks tetap menggambarkan seluruh daftar, bukan hasil saring.
        $this->assertSame(3, $keparahan->ringkas['total']);

        // Halaman dengan dua dimensi aktif tetap harus bisa dirender.
        $this->get(route('admin.pengaduan.index', [
            'investigasi' => StatusInvestigasi::SedangInvestigasi->value,
            'keparahan' => 'belum',
        ]))
            ->assertOk()
            ->assertSee('ADUAN-CHIP-01')
            ->assertSee('ADUAN-CHIP-03')
            ->assertDontSee('ADUAN-CHIP-02');
    }

    public function test_skrip_aksi_massal_tidak_memakai_assignment_pada_optional_chaining(): void
    {
        Pengaduan::factory()->create();

        $tampilan = $this->get(route('admin.pengaduan.index'))
            ->assertOk()
            ->getContent();

        // bulkCount?.textContent = count adalah SyntaxError, dan satu
        // SyntaxError mematikan seluruh blok script aksi massal tanpa jejak
        // apa pun di sisi server.
        $this->assertStringNotContainsString('bulkCount?.textContent =', $tampilan);
        $this->assertStringContainsString('bulkCount && (bulkCount.textContent = count)', $tampilan);
    }
}
