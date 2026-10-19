<?php

namespace App\Notifications;

use App\Mail\EscalasiMail;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PengaduanEscalasiSla extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly MasterUnit $unit,
        public readonly int $lewatHari,
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
            'sla_lewat',
            $this->lewatHari * 24, // konversi hari ke jam
        ))->to($notifiable->routeNotificationFor('mail', $this));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'pengaduan_eskalasi_sla',
            'title' => 'ESKALASI KRITIS: SLA Lewat Batas',
            'message' => "Tiket {$this->pengaduan->kode_tiket} diunit {$this->unit->namaLengkap()} sudah lewat {$this->lewatHari} hari kerja batas investigasi. Diedukasikan ke atasan.",
            'kode_tiket' => $this->pengaduan->kode_tiket,
            'unit_id' => $this->unit->id,
            'lewat_hari' => $this->lewatHari,
            'jenis_eskalasi' => 'sla_lewat',
            'url' => route('unit.disposisi.show', ['unit' => $this->unit->kode, 'kode' => $this->pengaduan->kode_tiket]),
            'pengaduan_id' => $this->pengaduan->id,
        ];
    }
}
