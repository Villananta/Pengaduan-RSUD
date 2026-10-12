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

    public function test_jatah_laju_formulir_publik_tidak_dimakan_konsol_humas(): void
    {
        // Lima kiriman ke konsol humas sudah cukup menutup formulir publik
        // ketika keduanya masih berbagi kunci pembatas yang sama. Kejadian
        // ini nyata: pelapor yang juga memakai konsol jadi tidak bisa mengirim
        // aduan sama sekali walau formulirnya belum pernah ia sentuh.
        $urlKoordinasi = route('admin.pengaduan.koordinasi', 'TIKET-TIDAK-ADA');

        for ($i = 0; $i < 5; $i++) {
            $this->post($urlKoordinasi, ['isi' => 'pesan uji']);
        }

        // Isian sengaja tidak lengkap: yang diperiksa cuma pembatas lajunya.
        // Formulir yang lolos pembatas akan berhenti di validasi (302), bukan
        // di 429, jadi status itulah yang menandakan jatahnya masih utuh.
        $this->post(route('pengaduan.store'), ['kategori' => 'fasilitas'])
            ->assertStatus(302);
    }

    public function test_jatah_laju_antar_tombol_konsol_humas_terpisah(): void
    {
        $kode = 'TIKET-TIDAK-ADA';

        for ($i = 0; $i < 60; $i++) {
            $this->post(route('admin.pengaduan.koordinasi', $kode), ['isi' => 'pesan uji']);
        }

        // Kantung koordinasi sudah penuh, tapi tombol balas belum pernah
        // dipakai. Permintaannya harus tetap sampai ke controller -- di sini
        // berhenti di 404 karena kode tiketnya ngawur -- bukan ikut tertahan
        // jadi 429 di gerbang.
        $this->post(route('admin.pengaduan.balas', $kode), ['isi' => 'pesan uji'])
            ->assertStatus(404);
    }
}
