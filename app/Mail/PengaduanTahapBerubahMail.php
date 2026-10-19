<?php

namespace App\Mail;

use App\Enums\StatusPengaduan;
use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PengaduanTahapBerubahMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly StatusPengaduan $dari,
        public readonly StatusPengaduan $ke,
        public readonly string $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Status Tiket Berubah: '.$this->pengaduan->kode_tiket.' ('.$this->dari->label().' → '.$this->ke->label().')',
            tags: ['tahap', 'perubahan', $this->dari->value, $this->ke->value],
        );
    }

    public function content(): Content
    {
        return new Content(
            // 'markdown' bukan 'view': tempat ini yang mendaftarkan komponen
            // mail::layout dan mem-parse isi markdownnya.
            markdown: 'emails.tahap-berubah',
            with: [
                'pengaduan' => $this->pengaduan,
                'dari' => $this->dari,
                'ke' => $this->ke,
                'url' => $this->url,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
