<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Penandaan baca untuk lonceng notifikasi milik satu akun.
 *
 * Konsol unit dan konsol admin memakai aturan yang sama: yang boleh
 * disentuh hanya notifikasi milik akun yang sedang masuk. Logikanya
 * ditaruh di sini supaya kedua konsol tidak perlahan berbeda perilaku
 * ketika aturan baca berubah.
 */
final class NotifikasiPengguna
{
    /**
     * Tandai sebagian notifikasi sebagai sudah dibaca.
     *
     * ID milik akun lain sengaja tidak ditolak dengan error: jumlah yang
     * benar-benar berubah dikembalikan, dan pemanggil memakai angka itu
     * untuk menyegarkan lencana.
     *
     * @param  array<int, string>  $ids
     */
    public static function tandaiDibaca(User $pengguna, array $ids): int
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $pengguna->id)
            ->whereIn('id', $ids)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /** Tandai seluruh notifikasi milik akun sebagai sudah dibaca. */
    public static function tandaiSemuaDibaca(User $pengguna): int
    {
        return $pengguna->unreadNotifications()->update(['read_at' => now()]);
    }

    /** Jumlah notifikasi yang masih belum dibaca. */
    public static function jumlahBelumDibaca(User $pengguna): int
    {
        return $pengguna->unreadNotifications()->count();
    }

    /**
     * Apakah masih ada follow up manual dari admin humas yang belum dibuka.
     *
     * Hanya tipe nudge yang dihitung: notifikasi lain bisa saja berasal
     * dari sistem dan tidak perlu titik penanda tersendiri di lonceng.
     */
    public static function adaNudgeBelumDibaca(User $pengguna): bool
    {
        return $pengguna->unreadNotifications()
            ->where('data->type', 'pengaduan_nudge_unit')
            ->exists();
    }
}
