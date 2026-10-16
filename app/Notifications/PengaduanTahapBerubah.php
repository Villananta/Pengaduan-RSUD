<?php

namespace App\Notifications;

use App\Mail\PengaduanTahapBerubahMail;
use App\Enums\StatusPengaduan;
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
        return new PengaduanTahapBerubahMail($this->pengaduan, $this->dari, $this->ke, $this->urlUntuk($notifiable));
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
            $unit = $notifiable->unit()->first();

            return $unit ? route('unit.disposisi.show', ['unit' => $unit->kode, 'kode' => $this->pengaduan->kode_tiket]) : route('unit.pilih');
        }

        return route('pengaduan.lacak');
    }
}
