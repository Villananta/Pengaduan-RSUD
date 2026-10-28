<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\NotifikasiPengguna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Lonceng notifikasi pada konsol admin humas.
 *
 * Isinya hanya notifikasi milik akun yang sedang masuk, sama seperti
 * lonceng unit. Aturan tandai-baca dipinjam dari NotifikasiPengguna
 * supaya kedua konsol tidak berbeda perilaku.
 */
class NotifikasiController extends Controller
{
    /** Daftar notifikasi belum dibaca untuk isi dropdown. */
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
            'total_belum_dibaca' => NotifikasiPengguna::jumlahBelumDibaca($user),
        ]);
    }

    /** Tandai sebagian notifikasi sebagai sudah dibaca. */
    public function tandaiDibaca(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notification_ids' => ['required', 'array', 'min:1'],
            'notification_ids.*' => ['string'],
        ], [
            'notification_ids.required' => 'Tidak ada notifikasi yang dipilih.',
            'notification_ids.array' => 'Daftar notifikasi tidak terbaca.',
            'notification_ids.min' => 'Tidak ada notifikasi yang dipilih.',
        ]);

        $user = $request->user();
        $updated = NotifikasiPengguna::tandaiDibaca($user, $validated['notification_ids']);

        return response()->json([
            'sukses' => true,
            'pesan' => $updated.' notifikasi ditandai sudah dibaca.',
            'dibaca' => $updated,
            'total_belum_dibaca' => NotifikasiPengguna::jumlahBelumDibaca($user),
        ]);
    }

    /** Tandai semua notifikasi milik admin sebagai sudah dibaca. */
    public function tandaiSemuaDibaca(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $updated = NotifikasiPengguna::tandaiSemuaDibaca($user);

        if ($request->expectsJson()) {
            return response()->json([
                'sukses' => true,
                'pesan' => 'Semua notifikasi ('.$updated.') ditandai sudah dibaca.',
                'dibaca' => $updated,
                'total_belum_dibaca' => 0,
            ]);
        }

        return back()->with('sukses', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
