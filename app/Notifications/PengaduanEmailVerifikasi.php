<?php

namespace App\Notifications;

use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PengaduanEmailVerifikasi extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly string $otp,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifikasi Email - Pengaduan RSUD Dr. Soetomo')
            ->greeting('Halo ' . $this->pengaduan->nama_lengkap . ',')
            ->line('Terima kasih telah mengirim pengaduan dengan nomor tiket **' . $this->pengaduan->kode_tiket . '**.')
            ->line('Untuk memastikan email Anda valid dan dapat menerima notifikasi perkembangan pengaduan, silakan verifikasi email ini dengan kode OTP di bawah:')
            ->line('**Kode OTP: ' . $this->otp . '**')
            ->line('Kode ini berlaku selama **15 menit**. Jangan bagikan kode ini kepada siapapun.')
            ->action('Verifikasi Email', url('/verifikasi-email/' . $this->pengaduan->kode_tiket . '?otp=' . $this->otp))
            ->line('Jika Anda tidak mengirim pengaduan ini, abaikan email ini.')
            ->salutation('Hormat kami,<br>Tim Humas & Pengaduan RSUD Dr. Soetomo');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'email_verifikasi',
            'kode_tiket' => $this->pengaduan->kode_tiket,
            'otp' => $this->otp,
        ];
    }
}