<?php

namespace App\Notifications;

use App\Mail\PengaduanKasusBeratMail;
use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PengaduanKasusBerat extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly bool $aktif,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): PengaduanKasusBeratMail
    {
        return new PengaduanKasusBeratMail($this->pengaduan, $this->aktif);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'pengaduan_kasus_berat',
            'title' => $this->aktif ? 'Tiket Ditandai Kasus Berat' : 'Penandaan Kasus Berat Dilepas',
            'message' => $this->aktif
                ? "Tiket {$this->pengaduan->kode_tiket} ditandai kasus berat. Target penyelesaian diperpanjang."
                : "Penandaan kasus berat pada tiket {$this->pengaduan->kode_tiket} dilepas. Target kembali standar.",
            'kode_tiket' => $this->pengaduan->kode_tiket,
            'kasus_berat' => $this->aktif,
            'url' => route('admin.pengaduan.show', $this->pengaduan->kode_tiket),
            'pengaduan_id' => $this->pengaduan->id,
        ];
    }
}
