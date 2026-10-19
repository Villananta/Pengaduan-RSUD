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

class SlaLewatMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly MasterUnit $unit,
        public readonly int $lewatHari,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'PERINGATAN KRITIS: Investigasi Lewat Batas — '.$this->pengaduan->kode_tiket,
            tags: ['sla', 'lewat', 'kritis', 'unit', $this->unit->kode],
        );
    }

    public function content(): Content
    {
        return new Content(
            // 'markdown' bukan 'view': tempat ini yang mendaftarkan komponen
            // mail::layout dan mem-parse isi markdownnya.
            markdown: 'emails.sla-lewat',
            with: [
                'pengaduan' => $this->pengaduan,
                'unit' => $this->unit,
                'lewatHari' => $this->lewatHari,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
