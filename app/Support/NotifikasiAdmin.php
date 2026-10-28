<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Lonceng notifikasi pada konsol admin humas.
 *
 * Sama seperti NotifikasiUnit, isinya hanya notifikasi milik akun yang
 * sedang masuk: admin dan PIC unit punya tonggak "sudah dibaca" sendiri,
 * jadi lonceng admin tidak boleh membaca notifikasi milik petugas lain.
 *
 * Kelas ini dipasang lewat view composer pada layout admin, sehingga
 * template tidak perlu menyusun query sendiri di tengah halaman.
 */
final class NotifikasiAdmin
{
    public function __construct(
        public readonly User $admin,
    ) {}

    /**
     * Notifikasi belum dibaca yang terbaru, untuk isi dropdown.
     *
     * Dibuat pendek karena dropdown hanya menampilkan beberapa baris;
     * selebihnya dibuka lewat tautan "lihat semua" di halaman notifikasi.
     */
    public function notifikasiTerbaru(int $limit = 15): Collection
    {
        return $this->admin->unreadNotifications()->latest()->limit($limit)->get();
    }

    /** Jumlah notifikasi belum dibaca, dipakai lencana pada ikon lonceng. */
    public function jumlahNotifikasiBelumDibaca(): int
    {
        return NotifikasiPengguna::jumlahBelumDibaca($this->admin);
    }
}
