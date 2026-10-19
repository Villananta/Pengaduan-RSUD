<?php

namespace App\Mail;

use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PengaduanDibuatMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly string $kodeTiket,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pengaduan Baru Dibuat — '.$this->kodeTiket,
            tags: ['pengaduan', 'baru', 'admin'],
        );
    }

    public function content(): Content
    {
        return new Content(
            // 'markdown' bukan 'view': tempat ini yang mendaftarkan komponen
            // mail::layout dan mem-parse isi markdownnya.
            markdown: 'emails.pengaduan-dibuat',
            with: [
                'pengaduan' => $this->pengaduan,
                'kodeTiket' => $this->kodeTiket,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
