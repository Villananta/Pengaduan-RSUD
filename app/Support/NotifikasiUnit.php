<?php

namespace App\Support;

use App\Models\MasterUnit;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Lonceng notifikasi untuk petugas unit yang sedang masuk.
 *
 * Notifikasi unit ditujukan ke akun PIC (User), bukan ke MasterUnit:
 * seluruh titik pengiriman di aplikasi mengirimnya ke $unit->pengguna.
 * Karena itu lonceng membaca notifikasi milik akun yang sedang masuk,
 * bukan milik unit — sasaran yang terbaca harus sama dengan sasaran
 * yang dikirim, dan tiap petugas punya tonggak "sudah dibaca" sendiri.
 */
final class NotifikasiUnit
{
    public function __construct(
        public readonly MasterUnit $unit,
        public readonly User $petugas,
    ) {}

    /** Notifikasi belum dibaca milik petugas, yang terbaru di atas. */
    public function notifikasiTerbaru(int $limit = 10): Collection
    {
        return $this->petugas->unreadNotifications()->latest()->limit($limit)->get();
    }

    /** Jumlah notifikasi belum dibaca milik petugas. */
    public function jumlahNotifikasiBelumDibaca(): int
    {
        return $this->petugas->unreadNotifications()->count();
    }

    /** True bila ada follow-up manual dari admin humas yang belum dibuka. */
    public function adaNudgeBelumDibaca(): bool
    {
        return $this->petugas->unreadNotifications()
            ->where('data->type', 'pengaduan_nudge_unit')
            ->exists();
    }
}
