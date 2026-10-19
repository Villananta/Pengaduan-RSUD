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
}
