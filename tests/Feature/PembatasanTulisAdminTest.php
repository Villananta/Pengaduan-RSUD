<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Laju endpoint tulis konsol humas dibatasi.
 *
 * Konsol masih terbuka tanpa login, jadi tanpa batas ini siapa pun bisa
 * membanjiri tiket, pesan, atau unggahan hanya dengan memanggil rutenya
 * berulang kali.
 */
class PembatasanTulisAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_koordinasi_admin_dibatasi_lajunya(): void
    {
        // Kode tiket sengaja tidak ada supaya tiap percobaan berhenti di 404
        // tanpa membuat data baru, tapi tetap terhitung oleh pembatas laju.
        $url = route('admin.pengaduan.koordinasi', 'TIKET-TIDAK-ADA');

        for ($i = 0; $i < 60; $i++) {
            $this->post($url, ['isi' => 'pesan uji']);
        }

        $this->post($url, ['isi' => 'pesan uji'])->assertStatus(429);
    }
}
