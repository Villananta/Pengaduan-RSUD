<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthCheckController extends Controller
{
    /**
     * Health check endpoint untuk load balancer / monitoring.
     *
     * Response:
     * - 200: healthy (semua dependency OK)
     * - 503: unhealthy (database/redis/cache gagal)
     */
    public function index(): JsonResponse
    {
        $checks = [];
        $overall = 'healthy';

        // 1. Database connectivity
        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => 'ok', 'latency_ms' => $this->measureLatency(fn () => DB::select('SELECT 1'))];
        } catch (\Throwable $e) {
            $checks['database'] = ['status' => 'failed', 'error' => $e->getMessage()];
            $overall = 'unhealthy';
        }

        // 2. Cache (Redis/Database)
        try {
            $cacheKey = 'health_check_'.Str::random(8);
            $latency = $this->measureLatency(function () use ($cacheKey) {
                Cache::put($cacheKey, 'ok', 10);
                Cache::get($cacheKey);
                Cache::forget($cacheKey);
            });
            $checks['cache'] = ['status' => 'ok', 'latency_ms' => $latency];
        } catch (\Throwable $e) {
            $checks['cache'] = ['status' => 'failed', 'error' => $e->getMessage()];
            $overall = 'unhealthy';
        }

        // 3. Redis (jika dikonfigurasi)
        try {
            if (class_exists(Redis::class)) {
                $latency = $this->measureLatency(fn () => Redis::ping());
                $checks['redis'] = ['status' => 'ok', 'latency_ms' => $latency];
            } else {
                $checks['redis'] = ['status' => 'skipped', 'reason' => 'not_configured'];
            }
        } catch (\Throwable $e) {
            $checks['redis'] = ['status' => 'failed', 'error' => $e->getMessage()];
            // Redis failure tidak selalu unhealthy (bisa pakai database cache)
            // Tapi set degraded
            if ($overall === 'healthy') {
                $overall = 'degraded';
            }
        }

        // 4. Storage writable
        try {
            $testFile = storage_path('health_check_'.Str::random(8).'.tmp');
            file_put_contents($testFile, 'ok');
            unlink($testFile);
            $checks['storage'] = ['status' => 'ok'];
        } catch (\Throwable $e) {
            $checks['storage'] = ['status' => 'failed', 'error' => $e->getMessage()];
            $overall = 'unhealthy';
        }

        // 4. Queue worker check (opsional - cek apakah queue worker hidup via Redis)
        // Skip untuk sekarang, bisa ditambah nanti

        $response = [
            'status' => $overall,
            'timestamp' => now()->toIso8601String(),
            'version' => app()->version(),
            'environment' => app()->environment(),
            'checks' => $checks,
        ];

        $statusCode = $overall === 'healthy' ? 200 : ($overall === 'degraded' ? 200 : 503);

        return response()->json($response, $statusCode);
    }

    /**
     * Simple liveness probe (untuk Kubernetes livenessProbe).
     *
     * Selalu return 200 kalau aplikasi boot dengan benar.
     */
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Readiness probe (untuk Kubernetes readinessProbe).
     *
     * Return 200 kalau siap serve traffic, 503 kalau tidak.
     */
    public function ready(): JsonResponse
    {
        try {
            // Quick checks only
            DB::connection()->getPdo();
            Cache::get('health_check_ready');

            return response()->json([
                'status' => 'ready',
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'not_ready',
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    }

    private function measureLatency(callable $callback): float
    {
        $start = microtime(true);
        $callback();

        return round((microtime(true) - $start) * 1000, 2);
    }
}
