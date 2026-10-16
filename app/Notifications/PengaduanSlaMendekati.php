<?php

namespace App\Notifications;

use App\Mail\SlaMendekatiMail;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PengaduanSlaMendekati extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly MasterUnit $unit,
        public readonly int $sisaHari,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): SlaMendekatiMail
    {
        return new SlaMendekatiMail($this->pengaduan, $this->unit, $this->sisaHari);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'pengaduan_sla_mendekati',
            'title' => 'Peringatan: Batas Investigasi Mendekati',
            'message' => "Tiket {$this->pengaduan->kode_tiket} di unit {$this->unit->namaLengkap()} tersisa {$this->sisaHari} hari kerja sebelum batas investigasi.",
            'kode_tiket' => $this->pengaduan->kode_tiket,
            'unit_id' => $this->unit->id,
            'sisa_hari' => $this->sisaHari,
            'url' => route('unit.disposisi.show', ['unit' => $this->unit->kode, 'kode' => $this->pengaduan->kode_tiket]),
            'pengaduan_id' => $this->pengaduan->id,
        ];
    }
}
