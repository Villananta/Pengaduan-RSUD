<?php

namespace App\Notifications;

use App\Mail\NudgeUnitMail;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PengaduanNudgeUnit extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly MasterUnit $unit,
        public readonly string $catatan,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Kirim email jika notifiable punya email
        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): NudgeUnitMail
    {
        // Alamat penerima diisi manual karena kanal mail hanya mengisinya
        // otomatis untuk MailMessage, bukan untuk Mailable.
        return (new NudgeUnitMail($this->pengaduan, $this->unit, $this->catatan))
            ->to($notifiable->routeNotificationFor('mail', $this));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'pengaduan_nudge_unit',
            'title' => 'Follow Up Manual dari Admin Humas',
            'message' => "Admin Humas mengirim follow up untuk tiket {$this->pengaduan->kode_tiket}: {$this->catatan}",
            'kode_tiket' => $this->pengaduan->kode_tiket,
            'unit_id' => $this->unit->id,
            'catatan' => $this->catatan,
            'url' => route('unit.disposisi.show', ['unit' => $this->unit->kode, 'kode' => $this->pengaduan->kode_tiket]),
            'pengaduan_id' => $this->pengaduan->id,
        ];
    }
}
