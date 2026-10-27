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

class EscalasiMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly MasterUnit $unit,
        public readonly string $jenisEscalasi, // 'nudge_abaikan' | 'sla_lewat'
        public readonly int $durasiJam, // berapa jam sudah lewat
        public readonly string $tautan, // konsol sesuai peran penerima
        public readonly ?string $catatanNudge = null,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->jenisEscalasi) {
            'nudge_abaikan' => 'ESKALASI: Nudge Diabaikan > 24 Jam — '.$this->pengaduan->kode_tiket,
            'sla_lewat' => 'ESKALASI KRITIS: SLA Lewat Batas — '.$this->pengaduan->kode_tiket,
            default => 'ESKALASI: '.$this->pengaduan->kode_tiket,
        };

        return new Envelope(
            subject: $subject,
            tags: ['eskalasi', $this->jenisEscalasi, 'unit', $this->unit->kode],
        );
    }

    public function content(): Content
    {
        return new Content(
            // 'markdown' bukan 'view': tempat ini yang mendaftarkan komponen
            // mail::layout dan mem-parse isi markdownnya.
            markdown: 'emails.escalasi',
            with: [
                'pengaduan' => $this->pengaduan,
                'unit' => $this->unit,
                'jenisEscalasi' => $this->jenisEscalasi,
                'durasiJam' => $this->durasiJam,
                'tautan' => $this->tautan,
                'catatanNudge' => $this->catatanNudge,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
