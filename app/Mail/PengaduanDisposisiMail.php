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

class PengaduanDisposisiMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly MasterUnit $unitTujuan,
        public readonly ?string $catatan,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Disposisi Tiket: '.$this->pengaduan->kode_tiket.' → '.$this->unitTujuan->kode,
            tags: ['disposisi', 'unit', $this->unitTujuan->kode],
        );
    }

    public function content(): Content
    {
        return new Content(
            // 'markdown' bukan 'view': tempat ini yang mendaftarkan komponen
            // mail::layout dan mem-parse isi markdownnya.
            markdown: 'emails.disposisi',
            with: [
                'pengaduan' => $this->pengaduan,
                'unitTujuan' => $this->unitTujuan,
                'catatan' => $this->catatan,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
