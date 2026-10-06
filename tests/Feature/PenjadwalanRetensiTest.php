<?php

namespace Tests\Feature;

use App\Models\Pengaduan;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pembersihan data pribadi yang sudah lewat masa retensi.
 *
 * Command-nya sudah ada sejak awal, tapi tidak pernah dijalankan siapa pun
 * karena tidak masuk jadwal. Kedua tes ini menjaga supaya pembersihan tetap
 * terjadwal dan tetap benar saat dijalankan.
 */
class PenjadwalanRetensiTest extends TestCase
{
    use RefreshDatabase;

    public function test_perintah_retensi_masuk_jadwal_harian(): void
    {
        $perintah = collect($this->app->make(Schedule::class)->events())
            ->map(fn ($event): string => $event->command ?? '')
            ->filter(fn (string $perintah): bool => str_contains($perintah, 'pengaduan:bersihkan'))
            ->all();

        $this->assertNotEmpty($perintah, 'Pembersihan retensi tidak ada di jadwal aplikasi.');
    }

    public function test_pengaduan_lewat_retensi_terhapus_dan_yang_baru_dipertahankan(): void
    {
        Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-TUA-01',
            'created_at' => now()->subDays(config('pengaduan.retensi_hari') + 10),
        ]);
        Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-BARU-01']);

        $this->artisan('pengaduan:bersihkan --force')->assertSuccessful();

        $this->assertDatabaseMissing('pengaduan', ['kode_tiket' => 'ADUAN-TUA-01']);
        $this->assertDatabaseHas('pengaduan', ['kode_tiket' => 'ADUAN-BARU-01']);
    }
}
