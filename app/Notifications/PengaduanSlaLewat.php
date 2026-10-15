<?php

namespace App\Notifications;

use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PengaduanSlaLewat extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly MasterUnit $unit,
        public readonly int $lewatHari,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'pengaduan_sla_lewat',
            'title' => 'PERINGATAN KRITIS: Investigasi Lewat Batas',
            'message' => "Tiket {$this->pengaduan->kode_tiket} di unit {$this->unit->namaLengkap()} sudah lewat {$this->lewatHari} hari kerja dari batas investigasi. Segera proses!",
            'kode_tiket' => $this->pengaduan->kode_tiket,
            'unit_id' => $this->unit->id,
            'lewat_hari' => $this->lewatHari,
            'url' => route('unit.disposisi.show', ['unit' => $this->unit->kode, 'kode' => $this->pengaduan->kode_tiket]),
            'pengaduan_id' => $this->pengaduan->id,
        ];
    }
}
