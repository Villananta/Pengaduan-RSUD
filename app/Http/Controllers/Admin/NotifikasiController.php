<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotifikasiController extends Controller
{
    public function daftar(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifikasi = $user->unreadNotifications()
            ->latest()
            ->limit(15)
            ->get()
            ->map(function (DatabaseNotification $notif): array {
                $data = $notif->data ?? [];

                return [
                    'id' => $notif->id,
                    'type' => $data['type'] ?? 'unknown',
                    'title' => $data['title'] ?? 'Notifikasi',
                    'message' => $data['message'] ?? '',
                    'kode_tiket' => $data['kode_tiket'] ?? null,
                    'url' => $data['url'] ?? null,
                    'created_at' => $notif->created_at?->diffForHumans(),
                ];
            });

        return response()->json([
            'sukses' => true,
            'notifications' => $notifikasi,
            'total_belum_dibaca' => $user->unreadNotifications()->count(),
        ]);
    }

    public function tandaiDibaca(Request $request): JsonResponse
    {
        $user = $request->user();
        $ids = (array) $request->input('notification_ids', []);

        if (! empty($ids)) {
            $user->unreadNotifications()
                ->whereIn('id', $ids)
                ->update(['read_at' => now()]);
        }

        return response()->json([
            'sukses' => true,
            'total_belum_dibaca' => $user->unreadNotifications()->count(),
        ]);
    }

    public function tandaiSemuaDibaca(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json([
                'sukses' => true,
                'total_belum_dibaca' => 0,
            ]);
        }

        return back()->with('sukses', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
