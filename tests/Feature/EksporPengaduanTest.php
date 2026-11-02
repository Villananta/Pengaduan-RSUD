<?php

namespace Tests\Feature;

use App\Enums\KategoriPengaduan;
use App\Enums\StatusInvestigasi;
use App\Enums\StatusPengaduan;
use App\Enums\TingkatKeparahan;
use App\Exports\PengaduanExport;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Penyaringan berkas ekspor daftar pengaduan.
 *
 * Ekspor menerima parameter yang sama dengan halaman daftar, jadi nilai
 * "belum" di sini harus dibaca sebagai tiket tanpa kategori. Kalau tidak,
 * admin melihat sederet tiket di layar lalu menerima berkas kosong.
 */
class EksporPengaduanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);
    }

    public function test_filter_belum_mengekspor_tiket_tanpa_kategori(): void
    {
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-EKSPOR-ADA',
            'kategori' => KategoriPengaduan::Fasilitas,
        ]);
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-EKSPOR-KOSONG',
            'kategori' => null,
        ]);

        $kode = (new PengaduanExport(kategoriFilter: 'belum'))
            ->collection()
            ->pluck('kode_tiket')
            ->all();

        $this->assertSame(['ADUAN-EKSPOR-KOSONG'], $kode);
    }

    public function test_filter_kategori_biasa_tetap_menyaring_sesuai_enum(): void
    {
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-EKSPOR-MEDIS',
            'kategori' => KategoriPengaduan::Medis,
        ]);
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-EKSPOR-KOSONG',
            'kategori' => null,
        ]);

        $kode = (new PengaduanExport(kategoriFilter: KategoriPengaduan::Medis->value))
            ->collection()
            ->pluck('kode_tiket')
            ->all();

        $this->assertSame(['ADUAN-EKSPOR-MEDIS'], $kode);
    }

    public function test_filter_investigasi_dan_keparahan_ikut_menyaring_berkas(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        // Tanpa unit: ditangani langsung humas dan keparahannya belum diisi.
        Pengaduan::factory()->status(StatusPengaduan::Diterima)->create([
            'kode_tiket' => 'ADUAN-EKSPOR-HUMAS',
            'master_unit_id' => null,
            'tingkat_keparahan' => null,
        ]);

        // Di unit dan belum dijawab: masih dalam investigasi.
        Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'kode_tiket' => 'ADUAN-EKSPOR-INVESTIGASI',
            'master_unit_id' => $farmasi->id,
            'tingkat_keparahan' => TingkatKeparahan::Berat,
        ]);

        // Di unit dan sudah dijawab: menunggu racikan humas.
        $dijawab = Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'kode_tiket' => 'ADUAN-EKSPOR-RACIKAN',
            'master_unit_id' => $farmasi->id,
            'tingkat_keparahan' => TingkatKeparahan::Ringan,
        ]);
        $dijawab->pesan()->create(['peran' => 'unit', 'isi' => 'Hasil pemeriksaan sudah dikirim.']);

        $racikan = (new PengaduanExport(investigasiFilter: StatusInvestigasi::MenungguRacikan->value))
            ->collection()
            ->pluck('kode_tiket')
            ->all();

        $this->assertSame(['ADUAN-EKSPOR-RACIKAN'], $racikan);

        $berat = (new PengaduanExport(keparahanFilter: TingkatKeparahan::Berat->value))
            ->collection()
            ->pluck('kode_tiket')
            ->all();

        $this->assertSame(['ADUAN-EKSPOR-INVESTIGASI'], $berat);

        $belum = (new PengaduanExport(keparahanFilter: 'belum'))
            ->collection()
            ->pluck('kode_tiket')
            ->all();

        $this->assertSame(['ADUAN-EKSPOR-HUMAS'], $belum);

        // Dua dimensi yang dipakai bersamaan harus menyaring keduanya,
        // bukan hanya salah satu.
        $gabungan = (new PengaduanExport(
            investigasiFilter: StatusInvestigasi::LangsungHumas->value,
            keparahanFilter: 'belum',
        ))->collection()->pluck('kode_tiket')->all();

        $this->assertSame(['ADUAN-EKSPOR-HUMAS'], $gabungan);

        $semua = (new PengaduanExport(
            investigasiFilter: 'tidak-ada',
            keparahanFilter: 'tidak-ada',
        ))->collection();

        $this->assertCount(3, $semua);
    }
}
