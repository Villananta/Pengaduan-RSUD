<?php

namespace App\Notifications;

use App\Mail\PengaduanDisposisiMail;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PengaduanDisposisi extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly MasterUnit $unitTujuan,
        public readonly ?string $catatan,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): PengaduanDisposisiMail
    {
        // Alamat penerima diisi manual karena kanal mail hanya mengisinya
        // otomatis untuk MailMessage, bukan untuk Mailable.
        return (new PengaduanDisposisiMail($this->pengaduan, $this->unitTujuan, $this->catatan))
            ->to($notifiable->routeNotificationFor('mail', $this));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'pengaduan_disposisi',
            'title' => 'Tiket Didisposisikan ke Unit Anda',
            'message' => "Tiket {$this->pengaduan->kode_tiket} didisposisikan ke {$this->unitTujuan->namaLengkap()}."
                .($this->catatan ? " Catatan: {$this->catatan}" : ''),
            'kode_tiket' => $this->pengaduan->kode_tiket,
            'unit_id' => $this->unitTujuan->id,
            'url' => route('unit.disposisi.show', ['unit' => $this->unitTujuan->kode, 'kode' => $this->pengaduan->kode_tiket]),
            'pengaduan_id' => $this->pengaduan->id,
        ];
    }
}
