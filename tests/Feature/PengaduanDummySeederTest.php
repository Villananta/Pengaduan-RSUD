<?php

namespace Tests\Feature;

use App\Enums\StatusInvestigasi;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use Database\Seeders\MasterUnitSeeder;
use Database\Seeders\PengaduanDummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengaduanDummySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_menyebar_150_pengaduan_secara_merata_ke_semua_fitur(): void
    {
        $this->travelTo('2026-11-06 09:00:00');
        $this->seed(MasterUnitSeeder::class);
        $this->seed(PengaduanDummySeeder::class);

        $this->assertSame(150, Pengaduan::count());

        // Tahap di sisi pelapor dibagi hampir sama besar.
        $this->assertSame([
            'diproses' => 38,
            'diterima' => 38,
            'revisi' => 37,
            'selesai' => 37,
        ], $this->hitungKelompok('status'));

        // Dua kategori masing-masing mengisi setengah dari total tiket.
        $this->assertSame([
            'fasilitas' => 75,
            'medis' => 75,
        ], $this->hitungKelompok('kategori'));

        // Setiap unit aktif mendapat porsi yang sama, sisanya ditangani humas.
        $perUnit = MasterUnit::query()
            ->withCount('pengaduan')
            ->pluck('pengaduan_count', 'kode')
            ->map(fn (int $jumlah): int => $jumlah)
            ->all();

        $this->assertSame(9, count($perUnit));
        $this->assertSame([15], array_values(array_unique($perUnit)));
        $this->assertSame(15, Pengaduan::query()->whereNull('master_unit_id')->count());

        // Keparahan hanya diisi tiket yang sudah ditelaah admin.
        $keparahan = Pengaduan::query()
            ->selectRaw("coalesce(tingkat_keparahan, 'belum') as tingkat, count(*) as jumlah")
            ->groupBy('tingkat')
            ->pluck('jumlah', 'tingkat')
            ->map(fn (int $jumlah): int => $jumlah)
            ->all();

        ksort($keparahan);

        $this->assertSame([
            'belum' => 38,
            'berat' => 37,
            'ringan' => 38,
            'sedang' => 37,
        ], $keparahan);

        // Lima kanal enum ditambah Portal Web (null) mendapat porsi sama.
        $kanal = Pengaduan::query()
            ->selectRaw("coalesce(kanal, 'portal_web') as sumber, count(*) as jumlah")
            ->groupBy('sumber')
            ->pluck('jumlah', 'sumber')
            ->map(fn (int $jumlah): int => $jumlah)
            ->all();

        ksort($kanal);

        $this->assertSame([
            'email' => 25,
            'portal_web' => 25,
            'sms' => 25,
            'tatap_muka' => 25,
            'telepon' => 25,
            'whatsapp' => 25,
        ], $kanal);

        $this->assertSame(12, Pengaduan::query()->where('kasus_berat', true)->count());

        // Keempat tab investigasi terisi, tiga di antaranya hampir seimbang.
        $investigasi = Pengaduan::query()
            ->with('pesan')
            ->get()
            ->countBy(fn (Pengaduan $pengaduan): string => StatusInvestigasi::dariPengaduan(
                $pengaduan,
                $pengaduan->pesan->contains(
                    fn (PesanPengaduan $pesan): bool => $pesan->peran === PesanPengaduan::PERAN_UNIT,
                ),
            )->value)
            ->sortKeys()
            ->all();

        $this->assertSame([
            'jawaban_unit' => 36,
            'langsung_humas' => 15,
            'menunggu_racikan' => 36,
            'sedang_investigasi' => 63,
        ], $investigasi);

        // Ketiga zona SLA muncul dan bisa dicoba dari semua arah filter.
        $zona = Pengaduan::query()
            ->get()
            ->countBy(fn (Pengaduan $pengaduan): string => $pengaduan->zonaSla()->value)
            ->sortKeys()
            ->all();

        $this->assertSame([
            'mendek' => 36,
            'tepat_waktu' => 76,
            'terlambat' => 38,
        ], $zona);
    }

    /**
     * Hitung baris per nilai satu kolom, diurutkan menurut nama kolom.
     *
     * @return array<string, int>
     */
    private function hitungKelompok(string $kolom): array
    {
        $hasil = Pengaduan::query()
            ->selectRaw($kolom.', count(*) as jumlah')
            ->groupBy($kolom)
            ->pluck('jumlah', $kolom)
            ->map(fn (int $jumlah): int => $jumlah)
            ->all();

        ksort($hasil);

        return $hasil;
    }
}
