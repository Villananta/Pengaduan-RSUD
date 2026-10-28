<?php

namespace Tests\Feature;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_mengembalikan_200_dan_semua_cek_ok(): void
    {
        $response = $this->getJson(route('health'));

        $response->assertOk()
            ->assertJsonPath('status', 'healthy')
            ->assertJsonPath('checks.database.status', 'ok')
            ->assertJsonPath('checks.cache.status', 'ok')
            ->assertJsonPath('checks.storage.status', 'ok')
            // Environment test memakai cache array dan antrean sync, jadi
            // Redis memang tidak dipakai dan tidak boleh diperiksa.
            ->assertJsonPath('checks.redis.status', 'skipped')
            ->assertJsonPath('checks.redis.reason', 'not_used');
    }

    public function test_health_live_selalu_200(): void
    {
        $this->getJson(route('health.live'))
            ->assertOk()
            ->assertJsonPath('status', 'alive');
    }

    public function test_health_ready_200_saat_dependency_siap(): void
    {
        $this->getJson(route('health.ready'))
            ->assertOk()
            ->assertJsonPath('status', 'ready');
    }

    public function test_health_mengembalikan_503_saat_cache_gagal(): void
    {
        // Pembatas laju juga membaca cache. Kalau middleware itu tetap
        // terpasang, request berhenti di sana sebelum mencapai controller dan
        // yang diuji bukan lagi laporan health check.
        $this->withoutMiddleware(ThrottleRequests::class);

        Cache::shouldReceive('put')->andThrow(new \RuntimeException('cache tidak bisa ditulis'));

        $response = $this->getJson(route('health'));

        $response->assertStatus(503)
            ->assertJsonPath('status', 'unhealthy')
            ->assertJsonPath('checks.cache.status', 'failed');

        $this->assertSame(['status' => 'failed'], $response->json('checks.cache'));
    }

    public function test_health_tidak_membocorkan_detail_internal(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        // Pesan exception sengaja dibuat menyerupai kegagalan koneksi asli:
        // host dan port tidak boleh ikut keluar ke pemanggil.
        Cache::shouldReceive('put')->andThrow(new \RuntimeException('host 10.0.0.5 port 3306 ditolak'));

        $response = $this->getJson(route('health'));

        $response->assertStatus(503);

        $isi = $response->getContent();
        $this->assertStringNotContainsString('10.0.0.5', $isi);
        $this->assertStringNotContainsString('port 3306 ditolak', $isi);

        $data = $response->json();
        $this->assertArrayNotHasKey('version', $data);
        $this->assertArrayNotHasKey('environment', $data);
    }

    public function test_ready_mengembalikan_503_tanpa_pesan_internal(): void
    {
        DB::shouldReceive('connection')->andThrow(new \RuntimeException('db host 10.0.0.5'));

        $response = $this->getJson(route('health.ready'));

        $response->assertStatus(503)
            ->assertJsonPath('status', 'not_ready');

        $this->assertArrayNotHasKey('error', $response->json());
        $this->assertStringNotContainsString('10.0.0.5', $response->getContent());
    }
}
