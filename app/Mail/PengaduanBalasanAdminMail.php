<?php

namespace App\Mail;

use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PengaduanBalasanAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly string $isiBalasan,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Balasan dari Admin Humas — '.$this->pengaduan->kode_tiket,
            tags: ['balasan', 'admin', 'pelapor'],
        );
    }

    public function content(): Content
    {
        return new Content(
            // 'markdown' bukan 'view': tempat ini yang mendaftarkan komponen
            // mail::layout dan mem-parse isi markdownnya.
            markdown: 'emails.balasan-admin',
            with: [
                'pengaduan' => $this->pengaduan,
                'isiBalasan' => $this->isiBalasan,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
