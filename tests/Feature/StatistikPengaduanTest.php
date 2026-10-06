<?php

namespace Tests\Feature;

use App\Enums\StatusPengaduan;
use App\Models\Pengaduan;
use App\Support\StatistikPengaduan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatistikPengaduanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        StatistikPengaduan::lupaCache();
    }

    public function test_rata_rata_resolusi_publik_dihitung_dengan_hari_kerja_bukan_hari_kalender(): void
    {
        // Jumat 2 Oktober 2026 sampai Senin 5 Oktober 2026. Jaraknya tiga
        // hari kalender, tapi hanya satu hari kerja karena akhir pekan
        // tidak dihitung. Angka yang tampil wajib menyebut hari kerja.
        Pengaduan::factory()
            ->selesai(Carbon::parse('2026-10-05 14:00:00'))
            ->create(['created_at' => Carbon::parse('2026-10-02 09:00:00')]);

        $this->assertSame(
            'Penyelesaian Rata-rata 1,0 Hari Kerja',
            StatistikPengaduan::ringkasRataRata()
        );
    }

    public function test_kepatuhan_sla_publik_membagi_dengan_tiket_yang_punya_waktu_selesai(): void
    {
        // Tiket pertama selesai dua hari lalu, jadi masih di dalam batas
        // dua belas hari kerja dan dihitung tepat waktu.
        Pengaduan::factory()
            ->selesai(now())
            ->create(['created_at' => now()->subDays(2)]);

        // Tiket kedua sudah berstatus Selesai, tapi belum punya waktu selesai
        // sehingga tidak pernah ikut dihitung. Angkanya tidak boleh ikut
        // menjadi pembagi, karena tidak ada data yang bisa dinilai.
        Pengaduan::factory()->create(['status' => StatusPengaduan::Selesai]);

        $this->assertSame('100,0%', $this->metrik('Kepatuhan SLA')['nilai']);
    }

    public function test_keterangan_rata_rata_resolusi_hanya_menghitung_tiket_terhitung(): void
    {
        Pengaduan::factory()
            ->selesai(now())
            ->create(['created_at' => now()->subDays(2)]);

        Pengaduan::factory()->create(['status' => StatusPengaduan::Selesai]);

        $this->assertSame('Dari 1 pengaduan selesai', $this->metrik('Rata-rata Resolusi')['sub']);
    }

    public function test_kepatuhan_sla_belum_bisa_dinilai_kalaunya_semua_tiket_selesai_kurang_waktu_selesai(): void
    {
        Pengaduan::factory()->create(['status' => StatusPengaduan::Selesai]);

        $kepatuhan = $this->metrik('Kepatuhan SLA');

        // Tanpa data yang bisa dinilai, kepatuhan harus ditulis "Belum ada
        // data", bukan 0,0% yang terlihat seperti hasil penilaian nyata.
        $this->assertSame('Belum ada data', $kepatuhan['nilai']);
        $this->assertFalse($kepatuhan['tersedia']);
    }

    /** Ambil satu kartu metrik berdasarkan labelnya. */
    private function metrik(string $label): array
    {
        $ditemukan = collect(StatistikPengaduan::metrik())->firstWhere('label', $label);

        $this->assertNotNull($ditemukan, "Kartu metrik {$label} tidak ditemukan.");

        return $ditemukan;
    }
}
