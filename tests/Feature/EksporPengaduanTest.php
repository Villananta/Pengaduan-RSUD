<?php

namespace Tests\Feature;

use App\Enums\KategoriPengaduan;
use App\Exports\PengaduanExport;
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
}
