<?php

return [
    // Tahap penanganan pengaduan ada di enum App\Enums\StatusPengaduan.

    // Tahapan prosedur 12 hari kerja.
    'prosedur' => [
        ['hari' => 'Hari ke-1 s.d 2', 'judul' => 'Verifikasi Administratif', 'ket' => 'Verifikasi administratif data identitas pelapor dan validitas NRM di SIMRS.'],
        ['hari' => 'Hari ke-3 s.d 5', 'judul' => 'Investigasi Internal', 'ket' => 'Investigasi lapangan, telaah berkas resep, dan koordinasi dengan Kepala Instalasi.'],
        ['hari' => 'Hari ke-6 s.d 9', 'judul' => 'Klarifikasi', 'ket' => 'Penyusunan rekomendasi perbaikan dan telaah etik/disiplin pelayanan bila terbukti pelanggaran.'],
        ['hari' => 'Hari ke-10 s.d 12', 'judul' => 'Jawaban Resmi', 'ket' => 'Penerbitan surat tanggapan resmi Direktur / Komite Etik dan pengisian survei kepuasan.'],
    ],
];
