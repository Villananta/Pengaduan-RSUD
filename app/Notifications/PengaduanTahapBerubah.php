<?php

namespace App\Notifications;

use App\Enums\StatusPengaduan;
use App\Mail\PengaduanTahapBerubahMail;
use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PengaduanTahapBerubah extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly StatusPengaduan $dari,
        public readonly StatusPengaduan $ke,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): PengaduanTahapBerubahMail
    {
        // Alamat penerima diisi manual karena kanal mail hanya mengisinya
        // otomatis untuk MailMessage, bukan untuk Mailable.
        return (new PengaduanTahapBerubahMail($this->pengaduan, $this->dari, $this->ke, $this->urlUntuk($notifiable)))
            ->to($notifiable->routeNotificationFor('mail', $this));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'pengaduan_tahap_berubah',
            'title' => 'Status Tiket Berubah',
            'message' => "Tiket {$this->pengaduan->kode_tiket} berubah dari {$this->dari->label()} menjadi {$this->ke->label()}.",
            'kode_tiket' => $this->pengaduan->kode_tiket,
            'tahap_lama' => $this->dari->value,
            'tahap_baru' => $this->ke->value,
            'url' => $this->urlUntuk($notifiable),
            'pengaduan_id' => $this->pengaduan->id,
        ];
    }

    private function urlUntuk(object $notifiable): string
    {
        // Cek apakah notifiable adalah User (admin/unit) atau punya session pelapor
        if (method_exists($notifiable, 'berperanAdmin') && $notifiable->berperanAdmin()) {
            return route('admin.pengaduan.show', $this->pengaduan->kode_tiket);
        }
        if (method_exists($notifiable, 'berperanUnit') && $notifiable->berperanUnit()) {
            // Tautan tidak pernah jatuh ke pemilih unit; petugas tanpa unit
            // cukup mendarat di halaman penjelasan bahwa akunnya belum ditautkan.
            $unit = $notifiable->unit()->first();

            return $unit ? route('unit.disposisi.show', ['unit' => $unit->kode, 'kode' => $this->pengaduan->kode_tiket]) : route('unit.beranda');
        }

        return route('pengaduan.lacak');
    }
}
