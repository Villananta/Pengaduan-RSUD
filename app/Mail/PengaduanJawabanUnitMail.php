<?php

namespace App\Mail;

use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PengaduanJawabanUnitMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly MasterUnit $unitPengirim,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Jawaban Unit Masuk — '.$this->pengaduan->kode_tiket,
            tags: ['jawaban', 'unit', $this->unitPengirim->kode],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.jawaban-unit',
            with: [
                'pengaduan' => $this->pengaduan,
                'unitPengirim' => $this->unitPengirim,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}