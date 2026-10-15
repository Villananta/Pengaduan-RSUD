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

class NudgeUnitMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly MasterUnit $unit,
        public readonly string $catatan,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Follow Up Manual dari Admin Humas — '.$this->pengaduan->kode_tiket,
            tags: ['nudge', 'unit', $this->unit->kode],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nudge-unit',
            with: [
                'pengaduan' => $this->pengaduan,
                'unit' => $this->unit,
                'catatan' => $this->catatan,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
