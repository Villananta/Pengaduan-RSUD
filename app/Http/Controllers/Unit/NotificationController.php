<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\MasterUnit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Lonceng notifikasi pada konsol unit.
 *
 * Notifikasi unit ditujukan ke akun PIC, jadi seluruh pengambilan dan
 * penandaan dibaca menyaring milik $request->user() — parameter {unit}
 * pada URL hanya menjaga konteks halaman, bukan menjadi sasaran data.
 */
class NotificationController extends Controller
{
    /**
     * Tandai notifikasi sebagai sudah dibaca.
     *
     * Bisa dipanggil untuk satu notifikasi (POST /unit/{unit}/notifikasi/baca)
     * atau massal lewat tandaiSemuaDibaca. Saringan akun memastikan
     * notifikasi petugas lain tidak ikut terhapus meski ID-nya ditebak.
     */
    public function tandaiDibaca(Request $request, ?MasterUnit $unit = null): JsonResponse
    {
        $notificationIds = $request->input('notification_ids', []);

        if (empty($notificationIds)) {
            return response()->json([
                'sukses' => false,
                'pesan' => 'Tidak ada notifikasi yang dipilih.',
            ], 422);
        }

        $updated = DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $request->user()->id)
            ->whereIn('id', $notificationIds)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'sukses' => true,
            'pesan' => $updated.' notifikasi ditandai sudah dibaca.',
            'dibaca' => $updated,
            'total_belum_dibaca' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Tandai semua notifikasi milik petugas sebagai sudah dibaca.
     *
     * Bentuk biasa (tanpa AJAX) dibalas redirect supaya tombol di
     * dropdown tetap bisa dipakai tanpa JavaScript, bukan memperlihatkan
     * halaman JSON mentah kepada petugas.
     */
    public function tandaiSemuaDibaca(Request $request, ?MasterUnit $unit = null): JsonResponse|RedirectResponse
    {
        $updated = $request->user()->unreadNotifications()->update(['read_at' => now()]);

        if (! $request->expectsJson()) {
            return back()->with('sukses', 'Semua notifikasi ('.$updated.') ditandai sudah dibaca.');
        }

        return response()->json([
            'sukses' => true,
            'pesan' => 'Semua notifikasi ('.$updated.') ditandai sudah dibaca.',
            'dibaca' => $updated,
        ]);
    }

    /**
     * Ambil daftar notifikasi untuk dropdown (AJAX).
     */
    public function daftar(?MasterUnit $unit, Request $request): JsonResponse
    {
        $tipe = $request->query('tipe');
        $limit = $request->integer('limit', 10);

        $query = $request->user()->unreadNotifications();

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
            'total_belum_dibaca' => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
