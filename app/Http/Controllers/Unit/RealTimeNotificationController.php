<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\MasterUnit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Ujung polling notifikasi konsol unit.
 *
 * Halaman konsol menarik notifikasi baru lewat rute ini setiap beberapa
 * detik, lalu menyimpan waktu server sebagai patokan poll berikutnya.
 * Sasaran datanya akun petugas yang sedang masuk — sama dengan lonceng
 * di header, bukan MasterUnit.
 */
class RealTimeNotificationController extends Controller
{
    /**
     * Daftar notifikasi belum dibaca milik petugas (ujung polling).
     */
    public function index(MasterUnit $unit, Request $request): JsonResponse
    {
        $since = $request->query('since'); // Patokan waktu berformat ISO8601
        $limit = $request->integer('limit', 20);

        $query = DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $request->user()->id)
            ->whereNull('read_at')
            ->latest('created_at')
            ->limit($limit);

        if ($since !== null) {
            $query->where('created_at', '>', $since);
        }

        $notifications = $query->get()->map(function (DatabaseNotification $n) {
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
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $request->user()->id)
                ->whereNull('read_at')
                ->count(),
            'server_time' => now()->toISOString(),
        ]);
    }

    /**
     * Tandai notifikasi terpilih sebagai sudah dibaca.
     */
    public function tandaiDibaca(MasterUnit $unit, Request $request): JsonResponse
    {
        $ids = $request->input('notification_ids', []);

        if (empty($ids)) {
            return response()->json([
                'sukses' => false,
                'pesan' => 'Tidak ada notifikasi yang dipilih.',
            ], 422);
        }

        $updated = DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $request->user()->id)
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
     * Tandai semua notifikasi milik petugas sebagai sudah dibaca.
     */
    public function tandaiSemuaDibaca(MasterUnit $unit, Request $request): JsonResponse
    {
        $updated = $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'sukses' => true,
            'pesan' => 'Semua notifikasi ('.$updated.') ditandai sudah dibaca.',
            'dibaca' => $updated,
        ]);
    }
}
