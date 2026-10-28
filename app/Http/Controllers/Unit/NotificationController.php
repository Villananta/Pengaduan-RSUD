<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\MasterUnit;
use App\Support\NotifikasiPengguna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        $validated = $request->validate([
            'notification_ids' => ['required', 'array', 'min:1'],
            'notification_ids.*' => ['string'],
        ], [
            'notification_ids.required' => 'Tidak ada notifikasi yang dipilih.',
            'notification_ids.array' => 'Daftar notifikasi tidak terbaca.',
            'notification_ids.min' => 'Tidak ada notifikasi yang dipilih.',
        ]);

        $pengguna = $request->user();
        $updated = NotifikasiPengguna::tandaiDibaca($pengguna, $validated['notification_ids']);

        return response()->json([
            'sukses' => true,
            'pesan' => $updated.' notifikasi ditandai sudah dibaca.',
            'dibaca' => $updated,
            'total_belum_dibaca' => NotifikasiPengguna::jumlahBelumDibaca($pengguna),
            'ada_nudge' => NotifikasiPengguna::adaNudgeBelumDibaca($pengguna),
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
        $updated = NotifikasiPengguna::tandaiSemuaDibaca($request->user());

        if (! $request->expectsJson()) {
            return back()->with('sukses', 'Semua notifikasi ('.$updated.') ditandai sudah dibaca.');
        }

        return response()->json([
            'sukses' => true,
            'pesan' => 'Semua notifikasi ('.$updated.') ditandai sudah dibaca.',
            'dibaca' => $updated,
            'total_belum_dibaca' => 0,
            'ada_nudge' => false,
        ]);
    }
}
