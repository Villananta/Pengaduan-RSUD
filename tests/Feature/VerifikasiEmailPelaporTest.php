<?php

namespace Tests\Feature;

use App\Models\Pengaduan;
use App\Notifications\PengaduanEmailVerifikasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VerifikasiEmailPelaporTest extends TestCase
{
    use RefreshDatabase;

    public function test_kirim_ulang_otp_berhasil(): void
    {
        Notification::fake();
        $pengaduan = Pengaduan::factory()->create(['email' => 'pelapor@example.com']);

        $response = $this->post(route('pengaduan.verifikasi-email.resend', $pengaduan->kode_tiket));
        $response->assertRedirect();
        Notification::assertSentOnDemand(
            PengaduanEmailVerifikasi::class,
            function ($notification, $channels, $notifiable) use ($pengaduan) {
                return ($notifiable->routes['mail'] ?? null) === $pengaduan->email;
            }
        );
    }

    public function test_kirim_ulang_otp_json_mengembalikan_sukses(): void
    {
        Notification::fake();
        $pengaduan = Pengaduan::factory()->create();

        $response = $this->postJson(route('pengaduan.verifikasi-email.resend', $pengaduan->kode_tiket));
        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_halaman_verifikasi_tidak_memakai_otp_dari_query(): void
    {
        $pengaduan = Pengaduan::factory()->create();
        $otp = $pengaduan->generateEmailOtp();

        $response = $this->get(route('pengaduan.verifikasi-email', [
            'kode' => $pengaduan->kode_tiket,
            'otp' => $otp,
        ]));

        $response->assertOk()->assertViewIs('pengaduan.verifikasi-email');
        $this->assertFalse($pengaduan->fresh()->isEmailVerified());
    }

    public function test_otp_yang_benar_lewat_post_memverifikasi_email(): void
    {
        $pengaduan = Pengaduan::factory()->create();
        $otp = $pengaduan->generateEmailOtp();

        $response = $this->post(route('pengaduan.verifikasi-email.post', $pengaduan->kode_tiket), [
            'otp' => $otp,
        ]);

        $response->assertRedirect(route('pengaduan.lacak'));
        $this->assertTrue($pengaduan->fresh()->isEmailVerified());
    }

    public function test_tebakan_otp_dibatasi_walau_kode_yang_dikirim_benar(): void
    {
        $pengaduan = Pengaduan::factory()->create();
        $otp = $pengaduan->generateEmailOtp();
        $url = route('pengaduan.verifikasi-email.post', $pengaduan->kode_tiket);

        foreach (range(1, 5) as $percobaan) {
            $this->post($url, ['otp' => '000000'])->assertSessionHasErrors('otp');
        }

        $response = $this->post($url, ['otp' => $otp]);

        $response->assertSessionHasErrors([
            'otp' => 'Terlalu banyak percobaan kode OTP. Coba lagi dalam 15 menit.',
        ]);
        $this->assertFalse($pengaduan->fresh()->isEmailVerified());
    }
}
