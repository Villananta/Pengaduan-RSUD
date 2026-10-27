<?php

namespace App\Notifications;

use App\Mail\EscalasiMail;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Support\TautanNotifikasi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PengaduanEscalasiNudge extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly MasterUnit $unit,
        public readonly int $durasiJam,
        public readonly string $catatanNudge,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): EscalasiMail
    {
        // Alamat penerima diisi manual karena kanal mail hanya mengisinya
        // otomatis untuk MailMessage, bukan untuk Mailable.
        return (new EscalasiMail(
            $this->pengaduan,
            $this->unit,
            'nudge_abaikan',
            $this->durasiJam,
            TautanNotifikasi::untuk($notifiable, $this->pengaduan),
            $this->catatanNudge,
        ))->to($notifiable->routeNotificationFor('mail', $this));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'pengaduan_eskalasi_nudge',
            'title' => 'ESKALASI: Nudge Diabaikan > 24 Jam',
            'message' => "Tiket {$this->pengaduan->kode_tiket} diunit {$this->unit->namaLengkap()} tidak direspons {$this->durasiJam} jam setelah nudge admin. Diteruskan ke admin humas.",
            'kode_tiket' => $this->pengaduan->kode_tiket,
            'unit_id' => $this->unit->id,
            'durasi_jam' => $this->durasiJam,
            'jenis_eskalasi' => 'nudge_abaikan',
            'url' => TautanNotifikasi::untuk($notifiable, $this->pengaduan),
            'pengaduan_id' => $this->pengaduan->id,
        ];
    }
}
