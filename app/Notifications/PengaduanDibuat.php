<?php

namespace App\Notifications;

use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class PengaduanDibuat extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly string $kodeTiket,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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