<?php

namespace App\Support;

use App\Models\Pengaduan;

/**
 * Tautan bawaan untuk notifikasi database dan email.
 *
 * Satu kelas notifikasi bisa dikirim ke dua peran sekaligus — penandaan
 * kasus berat sampai ke admin humas dan PIC unit, nudge manual juga
 * sama — sehingga alamatnya tidak boleh ditulis tetap ke satu konsol.
 * Penerima yang membuka tautan ke konsol yang bukan miliknya langsung
 * ditolak 403, jadi tautannya dipilih dari peran penerima, bukan dari
 * jenis notifikasinya.
 */
final class TautanNotifikasi
{
    /** Tautan lembar tiket yang sesuai dengan peran penerima notifikasi. */
    public static function untuk(object $notifiable, Pengaduan $pengaduan): string
    {
        if (method_exists($notifiable, 'berperanAdmin') && $notifiable->berperanAdmin()) {
            return route('admin.pengaduan.show', $pengaduan->kode_tiket);
        }

        if (method_exists($notifiable, 'berperanUnit') && $notifiable->berperanUnit()) {
            // Tautan tidak pernah jatuh ke pemilih unit; petugas tanpa unit
            // cukup mendarat di halaman penjelasan bahwa akunnya belum ditautkan.
            $unit = $notifiable->unit()->first();

            return $unit
                ? route('unit.disposisi.show', ['unit' => $unit->kode, 'kode' => $pengaduan->kode_tiket])
                : route('unit.beranda');
        }

        return route('pengaduan.lacak');
    }
}
