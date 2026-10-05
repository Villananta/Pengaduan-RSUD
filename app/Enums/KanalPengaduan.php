<?php

namespace App\Enums;

/**
 * Kanal selain formulir web tempat pengaduan masuk ke Instalasi Humas.
 *
 * Pengaduan telepon, SMS, atau yang diterima langsung di loket tetap harus
 * punya tiket resmi supaya bisa dilacak dan ditagih ke unit. Kanal ini
 * dicatat pada pesan pembuka tiket karena tabel pengaduan tidak menyimpan
 * asal keluhan; kalau someday butuh disaring per kanal, barisnya tinggal
 * dipindahkan ke kolom tersendiri.
 */
enum KanalPengaduan: string
{
    case Telepon = 'telepon';
    case Whatsapp = 'whatsapp';
    case TatapMuka = 'tatap_muka';
    case Email = 'email';
    case Sms = 'sms';

    // Nama kanal untuk pilihan pada formulir input aduan.
    public function label(): string
    {
        return match ($this) {
            self::Telepon => 'Telepon',
            self::Whatsapp => 'WhatsApp',
            self::TatapMuka => 'Datang Langsung',
            self::Email => 'Email',
            self::Sms => 'SMS',
        };
    }

    // Kalimat kanal untuk pesan pembuka tiket.
    public function frasa(): string
    {
        return match ($this) {
            self::Telepon => 'telepon',
            self::Whatsapp => 'WhatsApp',
            self::TatapMuka => 'ruang humas',
            self::Email => 'email',
            self::Sms => 'SMS',
        };
    }

    // Ikon Material Symbols untuk penanda kanal.
    public function ikon(): string
    {
        return match ($this) {
            self::Telepon, self::Sms => 'call',
            self::Whatsapp => 'chat',
            self::TatapMuka => 'person_pin',
            self::Email => 'mail',
        };
    }
}
