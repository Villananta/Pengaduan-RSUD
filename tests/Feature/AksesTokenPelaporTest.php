<?php

namespace Tests\Feature;

use App\Models\Pengaduan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Token akses yang membuka halaman lacak tanpa verifikasi ulang.
 *
 * Yang disimpan di database adalah hash SHA-256, sedangkan token aslinya
 * hanya ada di cookie pelapor. Tes ini mengunci sifat itu supaya dump
 * database tidak bisa dipakai langsung untuk membuka tiket orang lain.
 */
class AksesTokenPelaporTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_disimpan_sebagai_hash_bukan_nilai_asli(): void
    {
        $pengaduan = Pengaduan::factory()->create();

        $token = $pengaduan->generateAksesToken();

        $tersimpan = $pengaduan->fresh()->akses_token;

        $this->assertSame(hash('sha256', $token), $tersimpan);
        $this->assertNotSame($token, $tersimpan);
        $this->assertSame(64, strlen($tersimpan));
    }

    public function test_cookie_dari_verifikasi_membuka_tiket_tanpa_mengetik_kode_lagi(): void
    {
        $pengaduan = Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-TOKEN-01']);

        $response = $this->post(route('pengaduan.verifikasi'), ['kode' => $pengaduan->kode_tiket]);

        $response->assertRedirect(route('pengaduan.lacak'));

        $cookie = $response->getCookie('pengaduan_token');

        $this->assertNotNull($cookie);

        // Cookie dikirim ulang pada permintaan baru: session dari verifikasi
        // sudah tidak ada, jadi tiket hanya bisa terbuka lewat token ini.
        $this->flushSession();

        $this->withCookie('pengaduan_token', $cookie->getValue())
            ->get(route('pengaduan.lacak'))
            ->assertOk()
            ->assertSee('ADUAN-TOKEN-01');
    }

    public function test_token_yang_sudah_kedaluwarsa_tidak_membuka_tiket(): void
    {
        $pengaduan = Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-TOKEN-02']);

        $token = $pengaduan->generateAksesToken();

        $pengaduan->forceFill(['akses_token_expires_at' => now()->subMinute()])->save();

        $this->assertNull(Pengaduan::findByValidAksesToken($token));

        $this->withCookie('pengaduan_token', $token)
            ->get(route('pengaduan.lacak'))
            ->assertOk()
            ->assertDontSee('ADUAN-TOKEN-02');
    }
}
