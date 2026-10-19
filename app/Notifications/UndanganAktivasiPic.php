<?php

namespace App\Notifications;

use App\Models\MasterUnit;
use App\Support\UndanganPic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email undangan aktivasi akun PIC unit.
 *
 * Tautan di dalamnya memakai token yang tersimpan di master_units, jadi
 * masa berlakunya dikendalikan kolom undangan_berlaku — email yang sudah
 * terlanjur terkirim tidak bisa dipakai lagi setelah lewat batas.
 */
class UndanganAktivasiPic extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly MasterUnit $unit,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pic = $this->unit->pic ?? 'PIC '.$this->unit->nama;

        return (new MailMessage)
            ->subject('Undangan Aktivasi Akun PIC - '.$this->unit->namaLengkap())
            ->greeting('Halo '.$pic.',')
            ->line('Anda diundang mengaktifkan akun PIC untuk **'.$this->unit->namaLengkap().'** di Sistem Pengaduan RSUD Dr. Soetomo.')
            ->line('Akun ini dipakai membuka konsol disposisi unit dan melihat pengaduan yang ditugaskan ke unit Anda.')
            ->line('Tautan berlaku selama **'.UndanganPic::UMUR_JAM.' jam** sejak dikirim. Jangan bagikan tautan ini kepada siapapun.')
            ->action('Aktivasi Akun', route('aktivasi.undangan', ['token' => $this->unit->undangan_token]))
            ->line('Kalau Anda tidak merasa diminta menjadi PIC unit ini, abaikan email ini.')
            ->salutation('Hormat kami,<br>Tim Humas & Pengaduan RSUD Dr. Soetomo');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'undangan_aktivasi_pic',
            'unit' => $this->unit->kode,
        ];
    }
}
