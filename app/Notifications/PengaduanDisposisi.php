<?php

namespace App\Notifications;

use App\Models\Pengaduan;
use App\Models\MasterUnit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PengaduanDisposisi extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly MasterUnit $unitTujuan,
        public readonly ?string $catatan,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'pengaduan_disposisi',
            'title' => 'Tiket Didisposisikan ke Unit Anda',
            'message' => "Tiket {$this->pengaduan->kode_tiket} didisposisikan ke {$this->unitTujuan->namaLengkap()}."
                . ($this->catatan ? " Catatan: {$this->catatan}" : ''),
            'kode_tiket' => $this->pengaduan->kode_tiket,
            'unit_id' => $this->unitTujuan->id,
            'url' => route('unit.disposisi.show', ['unit' => $this->unitTujuan->kode, 'kode' => $this->pengaduan->kode_tiket]),
            'pengaduan_id' => $this->pengaduan->id,
        ];
    }
}