<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\MasterUnit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

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
        $validated = $request->validate([
            'since' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $since = $validated['since'] ?? null;
        $limit = (int) ($validated['limit'] ?? 20);

        // Database menyimpan created_at dalam zona aplikasi, sedangkan klien
        // mengirim ISO8601 (UTC). Tanpa konversi ini jendela poll melebar
        // beberapa jam dan notifikasi yang sudah tampil ikut terkirim ulang.
        $sinceLokal = $since !== null
            ? Carbon::parse($since)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            : null;

        // Patokan diambil sebelum query supaya notifikasi yang masuk saat
        // query berjalan tetap tertangkap pada poll berikutnya.
        $serverTime = now();

        $query = DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $request->user()->id)
            ->whereNull('read_at')
            ->limit($limit);

        if ($sinceLokal !== null) {
            // Urutan naik saat ada patokan: halaman pertama berisi notifikasi
            // paling lama setelah patokan. Kalau yang diambil justru yang
            // terbaru, notifikasi di tengah jendela tidak pernah terkirim
            // ketika jumlahnya melebihi batas satu poll.
            $query->where('created_at', '>', $sinceLokal)->oldest('created_at');
        } else {
            // Tanpa patokan yang diminta adalah potret terbaru.
            $query->latest('created_at');
        }

        $notifications = $query->get();

        // Hasil penuh hanya berarti "masih ada sisa" pada mode lanjutan;
        // potret terbaru memang sengaja berhenti di batas itu.
        $hasMore = $sinceLokal !== null && $notifications->count() >= $limit;

        // Saat masih ada sisa, poll berikutnya melanjutkan dari notifikasi
        // terakhir halaman ini. Kalau sudah habis, patokan dimundurkan satu
        // detik: created_at berpresisi detik, jadi notifikasi yang lahir di
        // detik yang sama dengan patokan bisa terlewat kalau memakai waktu
        // server apa adanya. Duplikat dari tumpang tindih itu disaring klien.
        $nextSince = $hasMore
            ? $notifications->last()->created_at->toISOString()
            : $serverTime->copy()->startOfSecond()->subSecond()->toISOString();

        $notifications = $notifications->map(function (DatabaseNotification $n) {
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
            'server_time' => $serverTime->toISOString(),
            'next_since' => $nextSince,
            'has_more' => $hasMore,
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
            'total_belum_dibaca' => $request->user()->unreadNotifications()->count(),
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
