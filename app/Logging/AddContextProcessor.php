<?php

namespace App\Logging;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

/**
 * Processor untuk menambah konteks request ke log JSON.
 *
 * Konteks yang ditambahkan:
 * - request_id (uuid unik per request)
 * - user_id & user_role (jika authenticated)
 * - method, url, ip
 * - user_agent (truncated)
 * - trace_id (untuk distributed tracing)
 *
 * Processor ini hanya aktif saat request HTTP (bukan CLI/queue).
 */
class AddContextProcessor
{
    private ?Request $request = null;

    public function __construct()
    {
        // Hanya bind request jika sedang di HTTP context
        if (App::runningInConsole()) {
            return;
        }

        try {
            $this->request = request();
        } catch (\RuntimeException) {
            // Request tidak tersedia (misalnya saat boot awal)
        }
    }

    /**
     * @param  array  $record  Monolog record
     */
    public function __invoke(array $record): array
    {
        $context = $record['context'] ?? [];

        // Tambahkan request context jika tersedia
        if ($this->request !== null) {
            $context = array_merge([
                'request_id' => $this->request->header('X-Request-ID') ?? (Str::uuid()->toString()),
                'method' => $this->request->method(),
                'url' => $this->request->fullUrl(),
                'ip' => $this->request->ip(),
                'user_agent' => Str::limit($this->request->userAgent() ?? 'unknown', 100),
                'trace_id' => $this->request->header('X-B3-TraceId') ?? $this->request->header('X-Request-ID'),
            ], $context);

            // User context (jika authenticated)
            if (auth()->check()) {
                $user = auth()->user();
                $context['user'] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->peran?->value ?? 'unknown',
                ];
            }

            // Kode tiket jika ada di route/params
            $kodeTiket = $this->request->route('kode')
                ?? $this->request->input('kode_tiket')
                ?? $this->request->input('kode');
            if ($kodeTiket) {
                $context['kode_tiket'] = strtoupper($kodeTiket);
            }

            // Unit context untuk konsol unit
            $unitKode = $this->request->route('unit');
            if ($unitKode) {
                $context['unit_kode'] = $unitKode;
            }
        }

        // Tambahkan timestamp ISO8601 dengan microseconds
        $context['logged_at'] = now()->toIso8601String();

        // Hapus objek yang tidak serializable (Exception, Closure, dll)
        $context = $this->sanitizeContext($context);

        $record['context'] = $context;

        return $record;
    }

    /**
     * Hapus nilai yang tidak bisa di-JSON-encode.
     */
    private function sanitizeContext(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_object($value)) {
                if (method_exists($value, '__toString')) {
                    $context[$key] = (string) $value;
                } elseif ($value instanceof \Throwable) {
                    $context[$key] = [
                        'class' => get_class($value),
                        'message' => $value->getMessage(),
                        'file' => $value->getFile(),
                        'line' => $value->getLine(),
                        'trace' => $value->getTraceAsString(),
                    ];
                } else {
                    $context[$key] = get_class($value);
                }
            } elseif (is_resource($value)) {
                $context[$key] = 'resource('.get_resource_type($value).')';
            } elseif (is_array($value)) {
                $context[$key] = $this->sanitizeContext($value);
            }
        }

        return $context;
    }
}
