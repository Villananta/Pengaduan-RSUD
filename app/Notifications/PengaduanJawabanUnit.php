<?php

namespace App\Notifications;

use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PengaduanJawabanUnit extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly MasterUnit $unitPengirim,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
