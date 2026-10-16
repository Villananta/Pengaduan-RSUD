<?php

namespace App\Mail;

use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PengaduanKasusBeratMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly bool $aktif,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->aktif
            ? 'Tiket Ditandai Kasus Berat — '.$this->pengaduan->kode_tiket
            : 'Penandaan Kasus Berat Dilepas — '.$this->pengaduan->kode_tiket;

        return new Envelope(
            subject: $subject,
            tags: ['kasus_berat', $this->aktif ? 'aktif' : 'lepas'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.kasus-berat',
            with: [
                'pengaduan' => $this->pengaduan,
                'aktif' => $this->aktif,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}