<?php

namespace App\Notifications;

use App\Mail\PengaduanBalasanAdminMail;
use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class PengaduanBalasanAdmin extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly string $isiBalasan,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): PengaduanBalasanAdminMail
    {
        return new PengaduanBalasanAdminMail($this->pengaduan, $this->isiBalasan);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'pengaduan_balasan_admin',
            'title' => 'Balasan Baru dari Admin Humas',
            'message' => "Admin Humas telah membalas tiket {$this->pengaduan->kode_tiket}: ".Str::limit($this->isiBalasan, 100),
            'kode_tiket' => $this->pengaduan->kode_tiket,
            'url' => route('pengaduan.lacak'),
            'pengaduan_id' => $this->pengaduan->id,
        ];
    }
}
