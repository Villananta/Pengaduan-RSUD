<?php

return [
    // Tahap penanganan pengaduan ada di enum App\Enums\StatusPengaduan.

    // Parameter standar pelayanan. Semua angka SLA pada halaman publik
    // diturunkan dari nilai di sini agar tidak ada dua sumber kebenaran.
    'sla' => [
        'hari_kerja' => 12,
        'respons_awal_jam' => 24,
        'peringatan_hari_kerja' => 2,
    ],

    // Data pribadi pengaduan dihapus setelah melewati masa retensi ini.
    'retensi_hari' => 730,

    // Tujuan bucket penyimpan berkas unggahan.
    'disk_lampiran' => 'public',

    // Unit / instalasi yang boleh dipilih pelapor.
    'units' => [
        'Instalasi Rawat Jalan (Poliklinik)',
        'Instalasi Rawat Inap',
        'Instalasi Gawat Darurat (IGD)',
        'Instalasi Farmasi',
        'Instalasi Radiologi',
        'Instalasi Laboratorium Patologi',
        'Instalasi Kamar Operasi (OK)',
        'Instalasi Rekam Medis',
        'Unit Kasir / Admisi',
        'Unit Pelayanan Gizi (Dapur)',
        'Unit Ruang Ibu & Anak',
        'Layanan Humas & Informasi',
    ],

    // Tahapan prosedur internal, batas dihitung dalam hari kerja.
    'prosedur' => [
        [
            'hari' => 'Hari ke-1 s.d 2',
            'batas' => 2,
            'judul' => 'Verifikasi Administratif',
            'ket' => 'Verifikasi administratif data identitas pelapor dan validitas NRM di SIMRS.',
        ],
        [
            'hari' => 'Hari ke-3 s.d 5',
            'batas' => 5,
            'judul' => 'Investigasi Internal',
            'ket' => 'Investigasi lapangan, telaah berkas resep, dan koordinasi dengan Kepala Instalasi.',
        ],
        [
            'hari' => 'Hari ke-6 s.d 9',
            'batas' => 9,
            'judul' => 'Klarifikasi',
            'ket' => 'Penyusunan rekomendasi perbaikan dan telaah etik/disiplin pelayanan bila terbukti pelanggaran.',
        ],
        [
            'hari' => 'Hari ke-10 s.d 12',
            'batas' => 12,
            'judul' => 'Jawaban Resmi',
            'ket' => 'Penerbitan surat tanggapan resmi Direktur / Komite Etik dan pengisian survei kepuasan.',
        ],
    ],

    // Empat tahap Standar Pelayanan Minimal (SPM) penanganan pengaduan.
    'spm' => [
        [
            'nilai' => '≤ 24 Jam',
            'badge' => 'Tahap 1',
            'judul' => 'Respons Awal Humas',
            'ket' => 'Pemberian kode tiket unik, verifikasi kelengkapan dokumen pengadu, dan penerbitan tanda terima pada halaman lacak tiket.',
            'catatan' => 'Batas Respons Awal',
            'ringkasan' => 'Respons Awal',
            'sla' => 'Hari ke-1 (≤ 24 Jam)',
            'ikon' => 'pesan',
            'warna' => 'info',
            'porsi' => 15,
        ],
        [
            'nilai' => 'Hari ke-5',
            'badge' => 'Tahap 2',
            'judul' => 'Klarifikasi Unit Terkait',
            'ket' => 'Pemeriksaan internal bersama Kepala Ruangan, Dokter Penanggung Jawab Pasien, Farmasi, atau instalasi terkait kejadian.',
            'catatan' => 'Batas Klarifikasi',
            'ringkasan' => 'Investigasi',
            'sla' => 'Hari ke-3 s.d 5',
            'ikon' => 'jam',
            'warna' => 'brand-600',
            'porsi' => 35,
        ],
        [
            'nilai' => '12 Hari Kerja',
            'badge' => 'Tahap 3',
            'judul' => 'Resolusi Kasus Reguler',
            'ket' => 'Penerbitan surat rekomendasi resmi Direksi, forum mediasi tertutup, dan penyelesaian ganti administrasi layanan.',
            'catatan' => 'Batas Resolusi',
            'ringkasan' => 'Penyelesaian',
            'sla' => 'Hari ke-6 s.d 12',
            'ikon' => 'dokumen',
            'warna' => 'brand',
            'porsi' => 50,
        ],
        [
            'nilai' => 'Ekstensi',
            'badge' => 'Tahap 4',
            'judul' => 'Audit Kasus Medis Berat',
            'ket' => 'Investigasi Komite Medik & Komite Etik Hukum, dengan pemberitahuan tertulis berkala selambatnya hari ke-10.',
            'catatan' => 'Kasus Kompleks',
            'ringkasan' => 'Audit Medis',
            'sla' => 'Di atas hari ke-12',
            'ikon' => 'peringatan',
            'warna' => 'alert',
            'porsi' => null,
        ],
    ],
];
