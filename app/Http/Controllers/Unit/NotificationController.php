<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\MasterUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Tandai notifikasi sebagai sudah dibaca.
     *
     * Bisa dipanggil untuk satu notifikasi (POST /unit/{unit}/notifikasi/{id}/baca)
     * atau massal (POST /unit/{unit}/notifikasi/baca-semua).
     */
    public function tandaiDibaca(Request $request, MasterUnit $unit): JsonResponse
    {
        $notificationIds = $request->input('notification_ids', []);

        if (empty($notificationIds)) {
            return response()->json([
                'sukses' => false,
                'pesan' => 'Tidak ada notifikasi yang dipilih.',
            ], 422);
        }

        $updated = DatabaseNotification::query()
            ->where('notifiable_type', MasterUnit::class)
            ->where('notifiable_id', $unit->id)
            ->whereIn('id', $notificationIds)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'sukses' => true,
            'pesan' => $updated.' notifikasi ditandai sudah dibaca.',
            'dibaca' => $updated,
        ]);
    }

    /**
     * Tandai semua notifikasi unit sebagai sudah dibaca.
     */
    public function tandaiSemuaDibaca(MasterUnit $unit): JsonResponse
    {
        $updated = $unit->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'sukses' => true,
            'pesan' => 'Semua notifikasi ('.$updated.') ditandai sudah dibaca.',
            'dibaca' => $updated,
        ]);
    }

    /**
     * Ambil daftar notifikasi untuk dropdown (AJAX).
     */
    public function daftar(MasterUnit $unit, Request $request): JsonResponse
    {
        $tipe = $request->query('tipe');
        $limit = $request->integer('limit', 10);

        $query = $unit->unreadNotifications();

        if ($tipe !== null) {
            $query->where('data->type', $tipe);
        }

        $notifikasi = $query->latest()->limit($limit)->get()->map(function (DatabaseNotification $n) {
            return [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'unknown',
                'title' => $n->data['title'] ?? 'Notifikasi',
                'message' => $n->data['message'] ?? '',
                'kode_tiket' => $n->data['kode_tiket'] ?? null,
                'url' => $n->data['url'] ?? null,
                'waktu' => $n->created_at->translatedFormat('d M Y, H:i'),
                'waktu_relatif' => $n->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'sukses' => true,
            'notifikasi' => $notifikasi,
            'total_belum_dibaca' => $unit->unreadNotificationCount(),
        ]);
    }
}
