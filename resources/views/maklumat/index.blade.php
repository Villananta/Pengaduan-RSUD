@extends('layouts.guest')

@section('title', 'Maklumat & Prosedur')

@section('content')

@php
    // Tahap SLA diambil dari config, sama seperti halaman lacak tiket.
    $spm = config('pengaduan.spm');

    // Gaya warna tiap tahap SLA, seluruh token warna ada di resources/css/app.css.
    $warnaSpm = [
        'info' => [
            'bar' => 'bg-info',
            'badge' => 'bg-info-light text-info',
            'angka' => 'text-info',
            'catatan' => 'text-info',
        ],
        'brand-600' => [
            'bar' => 'bg-brand-600',
            'badge' => 'bg-brand-100 text-brand-deep',
            'angka' => 'text-brand-600',
            'catatan' => 'text-brand-600',
        ],
        'brand' => [
            'bar' => 'bg-brand-800',
            'badge' => 'bg-chat-accent text-brand-950',
            'angka' => 'text-brand-800',
            'catatan' => 'text-brand-800',
        ],
        'alert' => [
            'bar' => 'bg-alert',
            'badge' => 'bg-alert-light text-alert-dark',
            'angka' => 'text-alert',
            'catatan' => 'text-alert',
        ],
    ];

    // Piagam maklumat resmi bertanda tangan.
    $piagam = [
        'kutipan' => 'Dengan ini kami menyatakan sanggup menyelenggarakan pelayanan kesehatan yang bermutu, cepat, transparan, dan tidak diskriminatif sesuai dengan standar pelayanan yang telah ditetapkan, serta siap menerima sanksi sesuai perundang-undangan yang berlaku apabila gagal pemenuhinya.',
        'meta' => [
            ['ikon' => 'kalender', 'teks' => 'Berlaku sejak 1 Oktober 2026'],
            ['ikon' => 'medali', 'teks' => 'Masa berlaku s.d. 31 Desember 2027'],
            ['ikon' => 'perisai', 'teks' => 'Menggantikan Maklumat Sebelumnya'],
        ],
        'pihak' => [
            'foto' => 'images/maklumat/dokter-laki.avif',
            'nama' => 'Prof. Dr. dr. Cita Rosita Sigit Prakoeswa, Sp.DVE (K), FINSDV, FAADV',
            'jabatan' => 'Direktur Utama RSUD Dr. Soetomo',
            'periode' => 'Periode 2026 - 2029',
        ],
    ];

    // Landasan hukum operasional pengaduan.
    $landasanHukum = [
        [
            'gaya' => 'solid',
            'ikon' => 'timbangan',
            'badge' => 'Landasan Utama',
            'judul' => 'UU No. 25 Tahun 2009',
            'sub' => 'tentang Pelayanan Publik',
            'ket' => 'Menjamin hak setiap warga negara memperoleh pelayanan berkualitas, mewajibkan penyusunan maklumat standar operasional, penyediaan sarana pengaduan terintegrasi, dan kewajiban ganti rugi bila terjadi maladministrasi terbukti.',
            'sumber' => 'Mewajibkan Penyusunan Maklumat Standar',
        ],
        [
            'gaya' => 'soft',
            'ikon' => 'dokumen',
            'judul' => 'UU No. 17 Tahun 2023',
            'sub' => 'tentang Kesehatan',
            'ket' => 'Pembaruan komprehensif perlindungan hukum pasien, transparansi rekam medis, kepatuhan etik profesi medis, serta penyelesaian sengketa melalui jalur mediasi komite etik rumah sakit.',
            'sumber' => 'Jaminan Hukum Pasien',
        ],
        [
            'gaya' => 'netral',
            'ikon' => 'buku',
            'judul' => 'Kepmenkes No. 129/2008',
            'sub' => 'Standar Pelayanan Minimal RS',
            'ket' => 'Ketentuan baku Standar Pelayanan Minimal (SPM) rumah sakit daerah yang mencakup rasio kecepatan penanganan keluhan, waktu tanggap gawat darurat, dan kepuasan pelanggan di atas 85%.',
            'sumber' => 'Acuan Kepatuhan',
        ],
    ];

    // Delapan hak pasien dan pelapor.
    $hak = [
        ['judul' => 'Pelayanan Bermutu', 'ket' => 'Mendapatkan pelayanan kesehatan yang bermutu, aman, dan sesuai standar pelayanan rumah sakit.'],
        ['judul' => 'Keterbukaan Informasi Medis', 'ket' => 'Mendapatkan penjelasan atas diagnosis, tindakan, serta salinan rekam medis sesuai ketentuan yang berlaku.'],
        ['judul' => 'Persetujuan Tindakan Medis', 'ket' => 'Memberikan persetujuan atas tindakan medis, kecuali dalam keadaan gawat darurat yang menuntut penanganan segera.'],
        ['judul' => 'Privasi & Kerahasiaan Data', 'ket' => 'Data kesehatan bersifat rahasia dan hanya dapat diakses oleh pihak yang berwenang.'],
        ['judul' => 'Hak Mengajukan Keberatan', 'ket' => 'Mengajukan keberatan atas hasil tindakan atau keputusan medis yang dianggap tidak sesuai.'],
        ['judul' => 'Waktu Tanggap Sesuai SPM', 'ket' => 'Memperoleh respons awal dan penanganan sesuai standar pelayanan minimal rumah sakit.'],
        ['judul' => 'Akses Pengaduan Tanpa Biaya', 'ket' => 'Menggunakan seluruh kanal pengaduan rumah sakit tanpa pungut biaya maupun syarat tambahan.'],
        ['judul' => 'Pendampingian Mediasi', 'ket' => 'Mendampingi proses mediasi melalui komite etik atau tim mediasi rumah sakit.'],
    ];

    // Lima kewajiban pengadu.
    $kewajiban = [
        ['judul' => 'Menyertakan Bukti & Kronologi', 'ket' => 'Menyampaikan foto, dokumen, atau kronologi kejadian agar proses verifikasi dapat berjalan cepat.'],
        ['judul' => 'Memberikan Keterangan yang Benar', 'ket' => 'Menyampaikan informasi yang jujur, lengkap, dan dapat dipertanggungjawabkan kepada petugas.'],
        ['judul' => 'Bersedia Dihubungi & Dimediasi', 'ket' => 'Tersedia untuk klarifikasi lanjutan atau mengikuti proses mediasi bersama pihak rumah sakit.'],
        ['judul' => 'Tidak Menyalahgunakan Kanal', 'ket' => 'Tidak menggunakan kanal pengaduan untuk tujuan yang tidak berkaitan dengan pelayanan rumah sakit.'],
        ['judul' => 'Menjaga Kerahasiaan Medis', 'ket' => 'Berhati-hati dalam menyebarkan informasi medis yang bersifat pribadi milik pihak lain.'],
    ];

    // Dokumen resmi yang dapat diunduh oleh pelapor.
    $dokumen = [
        [
            'gaya' => 'solid',
            'ikon' => 'buku',
            'judul' => 'Buku Pedoman Tata Kelola Pengaduan',
            'ket' => 'Petunjuk teknis alur keluhan, wewenang komite medik, dan sanksi pelanggaran SOP.',
            'ukuran' => 'PDF · 2,4 MB',
            'isi' => '128 halaman',
            'aksi' => 'Unduh Pedoman',
            'tombol' => 'gelap',
        ],
        [
            'gaya' => 'soft',
            'ikon' => 'formulir',
            'judul' => 'Formulir Mediasi Pasien & Kuasa',
            'ket' => 'Blangko pernyataan keberatan, surat kuasa mediasi, dan resume klaim pendampingan.',
            'ukuran' => 'PDF · 840 KB',
            'isi' => '6 lembar kerja',
            'aksi' => 'Unduh Formulir',
            'tombol' => 'terang',
        ],
    ];
@endphp

<!-- Banner Ambient dan Ringkasan Maklumat -->
<section class="relative isolate overflow-hidden bg-brand-section px-8 py-10">
    <span class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-chat-accent/20 blur-2xl" aria-hidden="true"></span>
    <span class="pointer-events-none absolute -bottom-24 -left-20 h-80 w-80 rounded-full bg-brand-100/30 blur-xl" aria-hidden="true"></span>

    <div class="relative flex w-full flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-8">
            <div class="flex max-w-[780px] flex-col gap-1">
                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-brand-100 px-3 py-1 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                    <svg class="h-3 w-3 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2 3 7v10l9 5 9-5V7l-9-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M12 7.5 15.5 9.5 12 11.5 8.5 9.5 12 7.5Z" fill="currentColor"/>
                    </svg>
                    <span class="text-xs font-semibold tracking-[0.24px] text-brand-deep">RSUD Dr. Soetomo Surabaya</span>
                </span>

                <h1 class="text-[32px] font-bold leading-10 tracking-[-0.8px] text-ink">Maklumat Pelayanan &amp; Standar SOP</h1>
                <p class="text-base leading-[26px] text-ink-muted">Hak, kewajiban, landasan hukum, dan standar waktu penyelesaian pengaduan yang berlaku di rumah sakit ini.</p>
            </div>
        </div>

        <!-- Piagam Maklumat Resmi -->
        <div class="relative isolate overflow-hidden rounded-xl bg-white p-8 shadow-[0_20px_25px_-5px_rgba(0,0,0,0.1),0_8px_10px_-6px_rgba(0,0,0,0.1)]">
            <span class="absolute inset-y-0 left-0 w-2 bg-brand-800" aria-hidden="true"></span>
            <svg class="pointer-events-none absolute -right-12 -top-12 h-[240px] w-[240px] text-brand-800 opacity-[0.05]" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M12 2 3 7v10l9 5 9-5V7l-9-5Z" fill="currentColor"/>
            </svg>

            <div class="relative flex flex-col gap-8 lg:flex-row lg:items-stretch">
                <div class="flex min-w-0 flex-1 flex-col gap-6 lg:justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="h-[20px] w-[20px] shrink-0 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2 3 7v10l9 5 9-5V7l-9-5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                            <path d="m8.5 12 2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <p class="text-[15px] font-bold uppercase tracking-[0.7px] text-brand-800">Piagam Maklumat Resmi</p>
                    </div>

                    <blockquote class="text-[26px] font-medium italic leading-[44px] text-ink">
                        &ldquo;{{ $piagam['kutipan'] }}&rdquo;
                    </blockquote>

                    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-x-6">
                        @foreach ($piagam['meta'] as $meta)
                            <li class="flex items-center gap-1.5 text-[13px] leading-[18px] text-ink-muted">
                                <svg class="h-4 w-4 shrink-0 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    @if ($meta['ikon'] === 'kalender')
                                        <rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.8"/>
                                        <path d="M4 10h16M9 3v4M15 3v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    @elseif ($meta['ikon'] === 'medali')
                                        <circle cx="12" cy="9" r="5" stroke="currentColor" stroke-width="1.8"/>
                                        <path d="m8.5 13.5-1 7 4.5-2.5 4.5 2.5-1-7" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    @else
                                        <path d="M12 3 5 6v5c0 4.2 2.9 8.4 7 9.9 4.1-1.5 7-5.7 7-9.9V6l-7-3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    @endif
                                </svg>
                                {{ $meta['teks'] }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Penandatanganan Piagam oleh Pimpinan Rumah Sakit -->
                <div class="flex w-full shrink-0 flex-col items-center justify-center gap-2 rounded-xl bg-brand-section p-6 text-center lg:w-[420px]">
                    <img
                        src="{{ asset($piagam['pihak']['foto']) }}"
                        alt="Foto {{ $piagam['pihak']['nama'] }}, {{ $piagam['pihak']['jabatan'] }}"
                        width="452"
                        height="542"
                        loading="lazy"
                        decoding="async"
                        class="h-48 w-[160px] rounded-lg object-cover object-top"
                    >

                    <p class="mt-2 text-base font-bold leading-[22px] text-ink">{{ $piagam['pihak']['nama'] }}</p>
                    <p class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">{{ $piagam['pihak']['jabatan'] }}</p>

                    <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-brand-100 px-3 py-1.5 text-[11px] font-semibold tracking-[0.33px] text-brand-800">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8 3v3M16 3v3M4 9h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <rect x="4" y="5" width="16" height="16" rx="2" stroke="currentColor" stroke-width="1.8"/>
                        </svg>
                        {{ $piagam['pihak']['periode'] }}
                    </span>
                </div>
                <!-- End of Penandatanganan Piagam -->
            </div>
        </div>
        <!-- End of Piagam Maklumat Resmi -->
    </div>
</section>
<!-- End of Banner Ambient dan Ringkasan Maklumat -->

<!-- Landasan Hukum dan Regulasi -->
<section class="w-full px-8 py-10">
    <div class="flex w-full flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-8">
            <div class="flex max-w-[616px] flex-col">
                <p class="text-sm font-semibold uppercase tracking-[0.7px] text-brand-800">Landasan Hukum</p>
                <h2 class="text-[32px] font-bold leading-10 tracking-[-0.32px] text-ink">Landasan Hukum &amp; Regulasi Operasional</h2>
            </div>
            <p class="max-w-[448px] pb-1 text-sm leading-[22px] text-ink-muted">Setiap ketentuan pada halaman ini diturunkan dari peraturan yang berlaku, sehingga komitmen pelayanan dapat diukur dan dipertanggungjawabkan.</p>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-12">
            @foreach ($landasanHukum as $bento)
                <article @class([
                    'flex flex-col justify-between gap-8 rounded-xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]',
                    'md:col-span-2 lg:col-span-6' => $loop->first,
                    'lg:col-span-3' => ! $loop->first,
                ])>
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between gap-4">
                            <span @class([
                                'flex h-12 w-12 shrink-0 items-center justify-center rounded-lg',
                                'bg-brand-700 text-white shadow-[inset_0_2px_4px_rgba(0,0,0,0.05)]' => $bento['gaya'] === 'solid',
                                'bg-brand-100 text-brand-deep' => $bento['gaya'] === 'soft',
                                'bg-chat-admin text-brand-800' => $bento['gaya'] === 'netral',
                            ])>
                                <svg class="h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    @if ($bento['ikon'] === 'timbangan')
                                        <path d="M12 4v16M7 20h10M5 8h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        <path d="M5 8 2 15h6L5 8Zm14 0-3 7h6l-3-7Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                    @elseif ($bento['ikon'] === 'dokumen')
                                        <path d="M6 3h8l4 4v14H6V3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                        <path d="M14 3v4h4M9 13h6M9 17h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                    @else
                                        <path d="M4 5a2 2 0 0 1 2-2h3l2 2h7a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                    @endif
                                </svg>
                            </span>

                            @if (! empty($bento['badge']))
                                <span class="rounded-full bg-chat-admin px-3 py-1 text-xs font-semibold tracking-[0.24px] text-ink">{{ $bento['badge'] }}</span>
                            @endif
                        </div>

                        <h3 @class([
                            'font-bold',
                            'text-xl leading-7' => $loop->first,
                            'text-lg' => ! $loop->first,
                        ])>{{ $bento['judul'] }}</h3>
                        <p class="text-xs font-semibold tracking-[0.24px] text-brand-600">{{ $bento['sub'] }}</p>
                        <p class="text-[13px] leading-[21px] text-ink-muted">{{ $bento['ket'] }}</p>
                    </div>

                    <p class="flex items-center gap-2 pt-4 text-xs font-semibold tracking-[0.24px] text-brand-800">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 4h14v16H5V4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                            <path d="M8.5 9.5 11 12l4.5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        {{ $bento['sumber'] }}
                    </p>
                </article>
            @endforeach

            <!-- Banner Pergub Jatim -->
            <div class="relative isolate flex flex-col justify-between gap-6 overflow-hidden rounded-xl bg-brand-800 p-6 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.1),0_2px_4px_-2px_rgba(0,0,0,0.1)] md:col-span-2 lg:col-span-12 lg:flex-row lg:items-center">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-white/10">
                        <svg class="h-[22px] w-[22px] text-brand-250" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 3h12v18H6V3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                            <path d="M9 7h6M9 11h6M9 15h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                    </span>

                    <div class="flex flex-col gap-0.5">
                        <h3 class="text-xl font-bold leading-7 text-white">Pergub Jatim &amp; Perkada Turunan</h3>
                        <p class="max-w-[672px] text-sm leading-[22px] text-brand-150">
                            Peraturan Gubernur Jawa Timur beserta peraturan turunan yang mengikat rumah sakit daerah, termasuk tata kelola pelayanan, pengawasan mutu, dan penanganan keluhan masyarakat.
                        </p>
                    </div>
                </div>

                <span class="shrink-0 self-start rounded-lg bg-white/15 px-3 py-1.5 text-xs font-semibold tracking-[0.24px] text-brand-250 lg:self-auto">Dokumen Regulasi</span>
            </div>
            <!-- End of Banner Pergub Jatim -->
        </div>
    </div>
</section>
<!-- End of Landasan Hukum dan Regulasi -->

<!-- Hak dan Kewajiban Pasien serta Pengadu -->
<section class="w-full bg-brand-50 px-8 py-10">
    <div class="flex w-full flex-col items-center gap-10">
        <div class="flex max-w-[672px] flex-col items-center text-center">
            <p class="text-sm font-semibold uppercase tracking-[0.7px] text-brand-800">Hak &amp; Kewajiban</p>
            <h2 class="text-[32px] font-bold leading-10 tracking-[-0.32px] text-ink">Hak &amp; Kewajiban Pasien &amp; Pengadu</h2>
            <p class="mt-1 text-sm leading-[22px] text-ink-muted">Setiap pasien, keluarga, maupun pelapor berhak memperoleh pelayanan yang layak, sekaligus berkewajiban menjaga ketertiban proses pengaduan.</p>
        </div>

        <div class="grid w-full grid-cols-1 items-start gap-6 lg:grid-cols-12">
            <!-- Delapan Hak Pasien dan Pelapor -->
            <div class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.1),0_2px_4px_-2px_rgba(0,0,0,0.1)] lg:col-span-7">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-2">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-chat-accent">
                            <svg class="h-4 w-4 text-brand-950" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 4v16M4 12h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M4 8 8 4M20 8l-4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-xl font-bold leading-7 text-ink">Hak Pasien &amp; Pelapor</h3>
                            <p class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">Dijamin oleh UU 25/2009 dan UU 17/2023</p>
                        </div>
                    </div>

                    <span class="rounded-full bg-brand-800/10 px-2.5 py-1 text-xs font-bold tracking-[0.24px] text-brand-800">{{ count($hak) }} Hak</span>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ($hak as $h)
                        <div class="flex items-start gap-3 rounded-lg bg-brand-section p-2">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-800 text-[11px] font-medium tracking-[0.33px] text-white">{{ $loop->iteration }}</span>
                            <div class="flex flex-col gap-0.5">
                                <p class="text-base font-semibold leading-[22px] text-ink">{{ $h['judul'] }}</p>
                                <p class="text-[13px] leading-[18px] text-ink-muted">{{ $h['ket'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <!-- End of Delapan Hak Pasien dan Pelapor -->

            <!-- Lima Kewajiban Pengadu -->
            <div class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.1),0_2px_4px_-2px_rgba(0,0,0,0.1)] lg:col-span-5">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-2">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-100">
                            <svg class="h-[18px] w-[18px] text-brand-deep" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 5h16v14H4V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                <path d="M8 9h8M8 13h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-xl font-bold leading-7 text-ink">Kewajiban Pengadu</h3>
                            <p class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">Agar verifikasi berjalan tertib dan adil</p>
                        </div>
                    </div>

                    <span class="rounded-full bg-brand-100 px-2.5 py-1 text-xs font-bold tracking-[0.24px] text-brand-deep">{{ count($kewajiban) }} Kewajiban</span>
                </div>

                <div class="flex flex-col gap-2">
                    @foreach ($kewajiban as $k)
                        <div class="flex items-start gap-3 rounded-lg bg-brand-section p-4">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-600 text-[11px] font-medium tracking-[0.33px] text-white">{{ $loop->iteration }}</span>
                            <div class="flex flex-col gap-0.5">
                                <p class="text-base font-semibold leading-[22px] text-ink">{{ $k['judul'] }}</p>
                                <p class="text-[13px] leading-[18px] text-ink-muted">{{ $k['ket'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <!-- End of Lima Kewajiban Pengadu -->
        </div>
    </div>
</section>
<!-- End of Hak dan Kewajiban Pasien serta Pengadu -->

<!-- Standar Pelayanan Minimal -->
<section class="w-full px-8 py-10">
    <div class="flex w-full flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-8">
            <div class="flex max-w-[698px] flex-col">
                <p class="text-sm font-semibold uppercase tracking-[0.7px] text-brand-800">Standar Pelayanan Minimal</p>
                <h2 class="text-[32px] font-bold leading-10 tracking-[-0.32px] text-ink">Standar Pelayanan Minimal (SPM) Penanganan</h2>
            </div>
            <p class="flex items-center gap-2 text-xs font-semibold tracking-[0.24px] text-brand-800">
                <span class="h-3 w-3 rounded-full bg-brand-800"></span>
                Batas Waktu Berlaku Resmi
            </p>
        </div>

        <!-- Empat Kartu Tahap -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">

            @foreach ($spm as $tahap)

                @php $warna = $warnaSpm[$tahap['warna']]; @endphp

                <article class="relative isolate flex flex-col justify-between gap-4 overflow-hidden rounded-xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                    <span @class(['absolute inset-x-0 top-0 h-1', $warna['bar']]) aria-hidden="true"></span>

                    <div class="flex flex-col gap-1">
                        <div class="flex items-center justify-between gap-3">
                            <span @class(['rounded-full px-2.5 py-1 text-xs font-bold tracking-[0.24px]', $warna['badge']])>{{ $tahap['badge'] }}</span>

                            <svg @class(['h-5 w-5 shrink-0', $warna['angka']]) viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                @if ($tahap['ikon'] === 'pesan')
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                @elseif ($tahap['ikon'] === 'jam')
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                @elseif ($tahap['ikon'] === 'dokumen')
                                    <path d="M6 3h8l4 4v14H6V3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                    <path d="M14 3v4h4M9 17l2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                @else
                                    <path d="M12 4 2.5 20h19L12 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M12 10v4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    <circle cx="12" cy="17" r=".9" fill="currentColor"/>
                                @endif
                            </svg>
                        </div>

                        <p @class(['mt-1 text-[40px] font-bold leading-12 tracking-[-1px]', $warna['angka']])>{{ $tahap['nilai'] }}</p>
                        <h3 class="text-base font-bold leading-[22px] text-ink">{{ $tahap['judul'] }}</h3>
                        <p class="text-[13px] leading-[18px] text-ink-muted">{{ $tahap['ket'] }}</p>
                    </div>

                    <p @class(['flex items-center gap-1 pt-4 text-[11px] font-semibold tracking-[0.33px]', $warna['catatan']])>
                        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                            <path d="m8.5 12 2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        {{ $tahap['catatan'] }}
                    </p>
                </article>
            @endforeach

        </div>
        <!-- End of Empat Kartu Tahap -->

        <!-- Visualisasi Garis Waktu SLA -->
        <div class="flex w-full flex-col gap-4 rounded-xl bg-brand-section p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
            <div class="flex flex-wrap items-center justify-between gap-6">
                <div class="flex max-w-[579px] flex-col">
                    <h4 class="text-base font-bold leading-[22px] text-ink">Alur Waktu Penyelesaian Pengaduan</h4>
                    <p class="text-[13px] leading-[18px] text-ink-muted">Garis waktu dihitung sejak tiket diterbitkan dan dikonfirmasi pelapor.</p>
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    @foreach (array_slice($spm, 0, 3) as $tahap)
                        <p class="flex items-center gap-1.5 text-[11px] font-medium tracking-[0.33px] text-ink-muted">
                            <span @class(['h-3 w-3 rounded-full', $warnaSpm[$tahap['warna']]['bar']])></span>
                            {{ $tahap['ringkasan'] }}
                        </p>
                    @endforeach
                </div>
            </div>

            <div class="flex w-full min-w-[640px] flex-col gap-2 overflow-x-auto py-1">
                <div class="flex h-8 items-stretch rounded-full bg-chat-admin shadow-[inset_0_2px_4px_rgba(0,0,0,0.05)]">
                    @foreach (array_slice($spm, 0, 3) as $tahap)
                        <div @class(['flex items-center justify-center px-2 text-[11px] font-bold tracking-[0.33px] text-white', $warnaSpm[$tahap['warna']]['bar']]) style="width: {{ $tahap['porsi'] }}%">
                            {{ $tahap['badge'] }} · {{ $tahap['ringkasan'] }}
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-between gap-4 px-1">
                    @foreach ($spm as $tahap)
                        <p class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">{{ $tahap['sla'] }}</p>
                    @endforeach
                </div>
            </div>
        </div>
        <!-- End of Visualisasi Garis Waktu SLA -->
    </div>
</section>
<!-- End of Standar Pelayanan Minimal -->

<!-- Unduh Dokumen SOP dan Formulir Resmi -->
<section class="w-full px-8 pb-10">
    <div class="flex w-full flex-col gap-8 rounded-xl bg-white p-8 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.1),0_2px_4px_-2px_rgba(0,0,0,0.1)] lg:flex-row lg:items-start">
        <div class="flex flex-1 flex-col gap-3">
            <span class="inline-flex w-fit items-center gap-2 rounded-full bg-chat-admin px-3 py-1">
                <svg class="h-3 w-3 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 3h8l4 4v14H6V3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                    <path d="M14 3v4h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
                <span class="text-xs font-semibold tracking-[0.24px] text-brand-800">Dokumen Regulasi Resmi</span>
            </span>

            <h2 class="text-2xl font-bold leading-8 tracking-[-0.32px] text-ink">Pedoman &amp; Formulir Pengaduan</h2>
            <p class="max-w-[507px] text-sm leading-[23px] text-ink-muted">Unduh pedoman tata kelola pengaduan dan formulir mediasi resmi yang berlaku di RSUD Dr. Soetomo. Dokumen ini menjadi acuan bersama bagi petugas, pasien, dan pelapor dalam setiap penanganan pengaduan.</p>

            <div class="flex flex-wrap items-center gap-4 pt-1 text-xs font-semibold tracking-[0.24px] text-ink">
                <p class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 3v3M16 3v3M4 9h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        <rect x="4" y="5" width="16" height="16" rx="2" stroke="currentColor" stroke-width="1.6"/>
                    </svg>
                    Berlaku sejak Oktober 2026
                </p>
                <p class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 5h16v14H4V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        <path d="m8.5 12 2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Ditandatangani Direktur
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row lg:shrink-0">
            @foreach ($dokumen as $d)
                <article class="flex w-full flex-col justify-between gap-4 rounded-xl bg-brand-section p-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)] sm:w-[270px]">
                    <div class="flex flex-col gap-1 pb-4">
                        <span @class([
                            'flex h-12 w-12 shrink-0 items-center justify-center rounded-lg',
                            'bg-brand-700 text-white shadow-[0_1px_2px_rgba(0,0,0,0.05)]' => $d['gaya'] === 'solid',
                            'bg-brand-100 text-brand-deep shadow-[0_1px_2px_rgba(0,0,0,0.05)]' => $d['gaya'] === 'soft',
                        ])>
                            <svg class="h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                @if ($d['ikon'] === 'buku')
                                    <path d="M4 5a2 2 0 0 1 2-2h4v16H6a2 2 0 0 0-2 2V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                    <path d="M20 5a2 2 0 0 0-2-2h-4v16h4a2 2 0 0 1 2 2V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                @else
                                    <path d="M6 3h8l4 4v14H6V3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                    <path d="M14 3v4h4M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                @endif
                            </svg>
                        </span>

                        <h3 class="mt-1 text-base font-bold leading-[22px] text-ink">{{ $d['judul'] }}</h3>
                        <p class="text-[13px] leading-[18px] text-ink-muted">{{ $d['ket'] }}</p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <p class="flex flex-wrap items-center gap-2 text-[11px] font-medium tracking-[0.33px] text-ink-muted">
                            <span class="rounded bg-brand-200 px-2 py-0.5 text-ink-muted">{{ $d['ukuran'] }}</span>
                            {{ $d['isi'] }}
                        </p>

                        <!-- Berkas resmi belum diunggah, jadi tombolnya dibuat non-tautan
                             supaya tidak menyesatkan pelapor yang mengekliknya. -->
                        <span
                            @class([
                                'flex items-center justify-center gap-2 rounded-lg py-2.5 text-sm font-semibold text-white shadow-[0_1px_2px_rgba(0,0,0,0.05)] cursor-not-allowed',
                                'bg-brand-800' => $d['tombol'] === 'gelap',
                                'bg-brand-100 text-brand-deep' => $d['tombol'] === 'terang',
                            ])
                        >
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 4v11m0 0 4-4m-4 4-4-4M5 19h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            {{ $d['aksi'] }}
                        </span>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
<!-- End of Unduh Dokumen SOP dan Formulir Resmi -->

@endsection
