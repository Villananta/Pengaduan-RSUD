<?php

namespace App\Notifications;

use App\Mail\PengaduanDibuatMail;
use App\Models\Pengaduan;
use App\Support\TautanNotifikasi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PengaduanDibuat extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly string $kodeTiket,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): PengaduanDibuatMail
    {
        // Alamat penerima diisi manual karena kanal mail hanya mengisinya
        // otomatis untuk MailMessage, bukan untuk Mailable.
        return (new PengaduanDibuatMail($this->pengaduan, $this->kodeTiket))
            ->to($notifiable->routeNotificationFor('mail', $this));
    }

    public function toArray(object $notifiable): array
    {
        // Penerima notifikasi ini selalu admin humas, bukan pelapor, jadi
        // pesannya berbicara sebagai pengingat tugas dan bukan instruksi
        // menyimpan kode tiket.
        return [
            'type' => 'pengaduan_dibuat',
            'title' => 'Pengaduan Baru Masuk',
            'message' => "Pengaduan {$this->kodeTiket} baru masuk dan menunggu telaah admin humas.",
            'kode_tiket' => $this->kodeTiket,
            'url' => TautanNotifikasi::untuk($notifiable, $this->pengaduan),
            'pengaduan_id' => $this->pengaduan->id,
        ];
    }
}
