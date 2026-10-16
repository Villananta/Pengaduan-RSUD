<?php

namespace App\Notifications;

use App\Mail\PengaduanDibuatMail;
use App\Models\Pengaduan;
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
        return new PengaduanDibuatMail($this->pengaduan, $this->kodeTiket);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'pengaduan_dibuat',
            'title' => 'Pengaduan Baru Dibuat',
            'message' => "Pengaduan {$this->kodeTiket} telah berhasil dicatat. Simpan kode ini untuk melacak perkembangan.",
            'kode_tiket' => $this->kodeTiket,
            'url' => route('pengaduan.lacak'),
            'pengaduan_id' => $this->pengaduan->id,
        ];
    }
}
