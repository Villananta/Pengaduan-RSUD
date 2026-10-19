<?php

namespace App\Notifications;

use App\Mail\PengaduanJawabanUnitMail;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PengaduanJawabanUnit extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly MasterUnit $unitPengirim,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): PengaduanJawabanUnitMail
    {
        // Alamat penerima diisi manual karena kanal mail hanya mengisinya
        // otomatis untuk MailMessage, bukan untuk Mailable.
        return (new PengaduanJawabanUnitMail($this->pengaduan, $this->unitPengirim))
            ->to($notifiable->routeNotificationFor('mail', $this));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'pengaduan_jawaban_unit',
            'title' => 'Jawaban Unit Masuk',
            'message' => "Unit {$this->unitPengirim->namaLengkap()} telah mengirim jawaban untuk tiket {$this->pengaduan->kode_tiket}.",
            'kode_tiket' => $this->pengaduan->kode_tiket,
            'unit_id' => $this->unitPengirim->id,
            'url' => route('admin.pengaduan.show', $this->pengaduan->kode_tiket),
            'pengaduan_id' => $this->pengaduan->id,
        ];
    }
}
