<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\MasterUnit;
use App\Models\Notification as DatabaseNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RealTimeNotificationController extends Controller
{
    /**
     * Get unread notifications for the current unit (polling endpoint).
     *
     * Used by frontend to poll for new notifications in real-time.
     */
    public function index(MasterUnit $unit, Request $request): JsonResponse
    {
        $since = $request->query('since'); // ISO8601 timestamp
        $limit = $request->integer('limit', 20);

        $query = DatabaseNotification::query()
            ->where('notifiable_type', MasterUnit::class)
            ->where('notifiable_id', $unit->id)
            ->whereNull('read_at')
            ->latest('created_at')
            ->limit($limit);

        if ($since !== null) {
            $query->where('created_at', '>', $since);
        }

        $notifications = $query->get()->map(function ($n) {
            $data = $n->data;

            return [
                'id' => $n->id,
                'type' => $data['type'] ?? 'unknown',
                'title' => $data['title'] ?? 'Notifikasi',
                'message' => $data['message'] ?? '',
                'kode_tiket' => $data['kode_tiket'] ?? null,
                'url' => $data['url'] ?? null,
                'created_at' => $n->created_at->toISOString(),
                'waktu_relatif' => $n->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'sukses' => true,
            'notifications' => $notifications,
            'total_belum_dibaca' => DatabaseNotification::query()
                ->where('notifiable_type', MasterUnit::class)
                ->where('notifiable_id', $unit->id)
                ->whereNull('read_at')
                ->count(),
            'server_time' => now()->toISOString(),
        ]);
    }

    /**
     * Mark specific notifications as read.
     */
    public function markAsRead(MasterUnit $unit, Request $request): JsonResponse
    {
        $ids = $request->input('notification_ids', []);

        if (empty($ids)) {
            return response()->json([
                'sukses' => false,
                'pesan' => 'Tidak ada notifikasi yang dipilih.',
            ], 422);
        }

        $updated = DatabaseNotification::query()
            ->where('notifiable_type', MasterUnit::class)
            ->where('notifiable_id', $unit->id)
            ->whereIn('id', $ids)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'sukses' => true,
            'pesan' => $updated.' notifikasi ditandai sudah dibaca.',
            'dibaca' => $updated,
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(MasterUnit $unit): JsonResponse
    {
        $updated = DatabaseNotification::query()
            ->where('notifiable_type', MasterUnit::class)
            ->where('notifiable_id', $unit->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'sukses' => true,
            'pesan' => 'Semua notifikasi ('.$updated.') ditandai sudah dibaca.',
            'dibaca' => $updated,
        ]);
    }
}
