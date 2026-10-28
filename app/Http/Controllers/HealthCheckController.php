<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * Probe kesehatan aplikasi untuk load balancer dan monitoring.
 *
 * Response sengaja hanya memuat status per dependency. Endpoint ini publik,
 * jadi pesan exception yang biasa memuat host, port, atau nama database
 * dicatat ke log lewat report(), bukan dikirim ke pemanggil. Versi framework
 * dan nama environment juga tidak ikut karena keduanya memudahkan penyerang
 * memetakan target.
 */
class HealthCheckController extends Controller
{
    /**
     * Laporan lengkap: 200 kalau semua dependency hidup, 503 kalau salah satu
     * yang wajib tidak bisa dipakai.
     */
    public function index(): JsonResponse
    {
        $checks = [];
        $sehat = true;

        // Basis data: satu-satunya dependency yang dilalui semua alur.
        try {
            $checks['database'] = [
                'status' => 'ok',
                'latency_ms' => $this->ukurLatensi(fn () => DB::select('SELECT 1')),
            ];
        } catch (\Throwable $e) {
            report($e);
            $checks['database'] = ['status' => 'failed'];
            $sehat = false;
        }

        // Cache: menulis lalu membaca satu kunci sudah cukup membuktikan
        // driver yang sedang dipakai benar-benar bisa disentuh.
        try {
            $kunci = 'health_check_'.Str::random(8);
            $checks['cache'] = [
                'status' => 'ok',
                'latency_ms' => $this->ukurLatensi(function () use ($kunci) {
                    Cache::put($kunci, 'ok', 10);
                    Cache::get($kunci);
                    Cache::forget($kunci);
                }),
            ];
        } catch (\Throwable $e) {
            report($e);
            $checks['cache'] = ['status' => 'failed'];
            $sehat = false;
        }

        // Redis hanya relevan kalau ada driver inti yang memakainya.
        // Sebelumnya blok ini selalu jalan dan selalu gagal di mesin tanpa
        // ekstensi phpredis, padahal aplikasi sama sekali tidak menyentuhnya.
        if ($this->redisDipakai()) {
            try {
                $checks['redis'] = [
                    'status' => 'ok',
                    'latency_ms' => $this->ukurLatensi(fn () => Redis::ping()),
                ];
            } catch (\Throwable $e) {
                report($e);
                $checks['redis'] = ['status' => 'failed'];
                $sehat = false;
            }
        } else {
            $checks['redis'] = ['status' => 'skipped', 'reason' => 'not_used'];
        }

        // Storage: view cache dan log ditulis di sini, jadi direktori yang
        // tidak bisa ditulisi akan menggagalkan aplikasi tanpa error jelas.
        try {
            $berkasUji = storage_path('health_check_'.Str::random(8).'.tmp');
            file_put_contents($berkasUji, 'ok');
            unlink($berkasUji);
            $checks['storage'] = ['status' => 'ok'];
        } catch (\Throwable $e) {
            report($e);
            $checks['storage'] = ['status' => 'failed'];
            $sehat = false;
        }

        return response()->json([
            'status' => $sehat ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $sehat ? 200 : 503);
    }

    /**
     * Liveness probe (Kubernetes livenessProbe).
     *
     * Selalu 200 selama aplikasi berhasil boot; dependency diperiksa di
     * endpoint lain supaya proses tidak di-restart hanya karena database
     * sedang lambat.
     */
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Readiness probe (Kubernetes readinessProbe).
     *
     * Hanya menyentuh database dan cache karena keduanya harus siap sebelum
     * trafik dialihkan ke instance ini.
     */
    public function ready(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            Cache::get('health_check_ready');

            return response()->json([
                'status' => 'ready',
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            // Sama seperti index(): alasan sebenarnya masuk log, response
            // hanya memberi tahu bahwa instance belum siap.
            report($e);

            return response()->json([
                'status' => 'not_ready',
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    }

    /**
     * Redis dianggap bagian dari sistem hanya kalau cache, antrean, sesi,
     * atau broadcast memakai driver redis.
     */
    private function redisDipakai(): bool
    {
        $driver = [
            config('cache.default'),
            config('queue.default'),
            config('session.driver'),
            config('broadcasting.default'),
        ];

        return in_array('redis', $driver, true);
    }

    private function ukurLatensi(callable $callback): float
    {
        $awal = microtime(true);
        $callback();

        return round((microtime(true) - $awal) * 1000, 2);
    }
}
