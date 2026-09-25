<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>@yield('title', 'Buat Aduan') — Sistem Pengaduan RSUD Dr. Soetomo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans bg-brand-light text-ink antialiased">

    {{-- Header --}}
    <header class="fixed inset-x-0 top-0 z-20 bg-white/90 shadow-[0_1px_8px_rgba(0,0,0,0.04)] backdrop-blur-md">
        <div class="mx-auto flex h-20 max-w-[1280px] items-center justify-between px-8">
            <div class="flex items-center gap-2">
                <x-brand-logo class="h-9 w-9" />
                <div class="leading-tight">
                    <p class="text-base font-bold text-brand-800">RSUD Dr. Soetomo</p>
                    <p class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">Sistem Pengaduan Pelayanan</p>
                </div>
            </div>

            <nav class="flex items-center gap-1">
                <a href="{{ route('pengaduan.create') }}" class="rounded-lg bg-brand-700 px-2 py-1 text-sm font-normal text-white">Buat Aduan</a>
                <a href="#" class="rounded-lg px-2 py-1 text-sm font-semibold text-ink-muted">Cek Status Tiket</a>
                <a href="#" class="rounded-lg px-2 py-1 text-sm font-semibold text-ink-muted">Maklumat &amp; Prosedur</a>
            </nav>

            <div class="flex items-center gap-2">
                <div class="pr-1 text-right text-[11px] leading-snug">
                    <p class="font-medium text-ink-muted">Call Center 24 Jam</p>
                    <p class="flex items-center justify-end gap-1 font-bold text-alert">
                        <svg class="h-2.5 w-2.5" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10.5 8.25V10a1 1 0 0 1-1.09 1A12.02 12.02 0 0 1 1 2.59 1 1 0 0 1 2 1.5h1.75a1 1 0 0 1 1 .85c.06.46.16.9.31 1.33a1 1 0 0 1-.23 1.06l-.74.74a9.6 9.6 0 0 0 4.43 4.43l.74-.74a1 1 0 0 1 1.06-.23c.43.15.87.25 1.33.31a1 1 0 0 1 .85 1z" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        (031) 1500995
                    </p>
                </div>

                <a href="#" aria-label="WhatsApp Humas" class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-800/10 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                    <svg class="h-3.5 w-3.5 text-brand-800" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.04 2a9.9 9.9 0 0 0-8.5 14.9L2 22l5.25-1.5A9.9 9.9 0 1 0 12.04 2Zm5.8 14.06c-.24.68-1.4 1.3-1.93 1.34-.52.05-1 .24-.3.28-3.9.56-7.06-2.5-7.28-2.62-.22-.12-1.73-1.82-1.73-3.48 0-1.66.87-2.47 1.18-2.81.3-.34.66-.43.88-.43.22 0 .44 0 .63.01.2.02.47-.08.73.56.27.64.92 2.2 1 2.36.08.15.14.34.03.54-.11.2-.16.33-.32.5-.16.18-.34.4-.49.54-.16.16-.33.34-.14.66.19.32.85 1.4 1.83 2.27 1.26 1.12 2.32 1.47 2.65 1.63.33.16.52.14.72-.08.19-.22.83-.97 1.05-1.3.22-.33.44-.27.74-.16.3.1 1.89.89 2.21 1.05.33.16.54.24.62.38.08.14.08.78-.16 1.52Z"/>
                    </svg>
                </a>

                <a href="#" class="rounded-lg bg-brand-100 px-4 py-1 text-sm font-semibold text-[#3C4B35]">Telepon Humas</a>
            </div>
        </div>
    </header>

    <main class="pt-20">
        @yield('content')
    </main>

    {{-- Help / multi channel section --}}
    <section class="w-full bg-brand-section px-8 py-10">
        <div class="mx-auto flex max-w-[1216px] flex-col gap-6">
            <div class="flex flex-wrap items-end justify-between gap-8">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.6px] text-brand-800">Akses Pengaduan Lainnya</p>
                    <h2 class="mt-1 max-w-[220px] text-2xl font-semibold leading-8 text-ink">Akses Layanan Pengaduan Alternatif</h2>
                </div>
                <p class="max-w-[402px] pb-1 text-[13px] leading-[18px] text-ink-muted">Seluruh kanal terhubung langsung ke Komite Mutu & Kinerja Rumah Sakit, Direksi, dan tim investigasi.</p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                @php
                    $kanal = [
                        [
                            'varian' => 'alert',
                            'judul' => 'Call Center 24 Jam Siaga',
                            'deskripsi' => 'Layanan respon cepat untuk penanganan keluhan darurat dan konfirmasi status ruang perawatan.',
                            'nomor' => '(031) 1500995',
                            'aksi' => 'Telpon Sekarang',
                            'tombol' => 'light',
                        ],
                        [
                            'varian' => 'wa',
                            'judul' => 'WhatsApp Resmi Pengaduan',
                            'deskripsi' => 'Kirimkan foto bukti, kartu antrean, atau konsultasi kronologi secara interaktif via pesan singkat.',
                            'nomor' => '+62 811-3000-995',
                            'aksi' => 'Chat WhatsApp',
                            'tombol' => 'dark',
                        ],
                        [
                            'varian' => 'loket',
                            'judul' => 'Loket Fisik Layanan Humas',
                            'deskripsi' => 'Gedung Pusat Pelayanan & Informasi Terpadu (Lantai 1), Jl. Mayjen Prof. Dr. Moestopo No.6-8, Surabaya.',
                            'nomor' => 'Senin - Jumat: 07.30 - 15.30 WIB',
                            'aksi' => 'Lihat Peta',
                            'tombol' => 'link',
                        ],
                    ];
                @endphp
                @foreach ($kanal as $k)
                    <div class="flex flex-col justify-between gap-4 rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]" style="height: 250px">
                        <div class="flex flex-col gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl
                                {{ $k['varian'] === 'alert' ? 'bg-alert-light' : ($k['varian'] === 'wa' ? 'bg-brand-100' : 'bg-brand-50') }}">
                                <svg class="h-[21px] w-[21px] {{ $k['varian'] === 'alert' ? 'text-alert-dark' : 'text-brand-800' }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    @if ($k['varian'] === 'alert')
                                        <path d="M5 3h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2h-9l-4.5 3V18H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    @elseif ($k['varian'] === 'wa')
                                        <path d="M14.19 13.5c-.21-.38-.42-.38-.84-.62-.42-.24-.42-.4-.63-.13-.21.27-.84 1.19-.9 1.34-.07.15-.44.1-.67-.12-.27-.26-.51-.58-.71-.9a2.62 2.62 0 0 1-.3-.66c-.17-.6.68-.69 1.04-1.19a.58.58 0 0 0-.33-.85l-.23-.52c-.15-.35-.41-.39-.6-.39-.17 0-.38.03-.56.08-.65.2-.98.95-.93 1.68.03.87.32 1.66.84 2.35.34.64.84 1.14 1.39 1.57.52.4 2.06 1.72 2.7 1.86.6.13 1.06-.05 1.29-.23.28-.22.68-1.02.77-1.32.11-.3.06-.43-.1-.44Z" fill="currentColor"/>
                                        <path d="M17.41 2H6.6A4.6 4.6 0 0 0 2 6.6v4.72a4.6 4.6 0 0 0 2.17 3.92 6.37 6.37 0 0 0 1.68 1.55v2.13a1.6 1.6 0 0 0 2.42 1.36l2.15-1.28a4.6 4.6 0 0 0 .45.02 4.6 4.6 0 0 0 4.6-4.6V6.6A4.6 4.6 0 0 0 17.41 2Zm1.41 10.88A2.53 2.53 0 0 1 16.3 15.4c-.52.12-1.64.3-3.27-.63a11.42 11.42 0 0 1-4.3-4.24c-.87-1.51-.66-2.9-.62-3.18.04-.28.16-.55.28-.79.16-.31.35-.58.6-.8A.87.87 0 0 1 9.86 5.4c.27.05.5.02.72.59.22.57.77 1.88.84 2.02.07.14.1.3 0 .49-.1.19-.15.3-.3.47l-.18.2c-.1.11-.2.24-.08.45.84 1.5 1.77 2.44 3.15 3.17.12.07.27.03.37-.06l.43-.5c.08-.1.18-.17.32-.2.13-.03.27 0 .37.08.28.18.87.85 1.03 1.05.16.2.25.35.18.57Z" fill="currentColor"/>
                                    @elseif ($k['varian'] === 'loket')
                                        <path d="M20 10c0 4.99-5.54 10.19-7 11.5C9.54 20.19 4 15 4 10a8 8 0 1 1 16 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.6"/>
                                    @endif
                                </svg>
                            </div>
                            <p class="text-base font-bold text-ink">{{ $k['judul'] }}</p>
                            <p class="text-[13px] leading-[18px] text-ink-muted">{{ $k['deskripsi'] }}</p>
                        </div>

                        <div class="flex flex-col gap-2 pt-2">
                            <p class="text-xl font-bold tracking-[-0.5px] {{ $k['varian'] === 'alert' ? 'text-alert' : 'text-brand-800' }}">{{ $k['nomor'] }}</p>
                            @if ($k['tombol'] === 'light')
                                <a href="#" class="rounded-lg bg-brand-light py-2.5 text-center text-xs font-semibold text-ink">{{ $k['aksi'] }}</a>
                            @elseif ($k['tombol'] === 'dark')
                                <a href="#" class="rounded-lg bg-brand-800 py-2.5 text-center text-xs font-semibold text-white">{{ $k['aksi'] }}</a>
                            @else
                                <a href="#" class="rounded-lg bg-brand-50 py-2.5 text-center text-xs font-semibold text-ink">{{ $k['aksi'] }}</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="relative z-10 w-full bg-white/90 py-10 shadow-[0_1px_8px_rgba(0,0,0,0.02)]">
        <div class="mx-auto flex max-w-[1280px] flex-col gap-10 px-8">
            <div class="grid grid-cols-4 gap-6">
                <div>
                    <div class="flex items-center gap-1">
                        <x-brand-logo class="h-10 w-10" />
                        <p class="whitespace-nowrap text-base font-bold leading-[22px] text-brand-800">RSUD Dr. Soetomo</p>
                    </div>
                    <p class="mt-8 max-w-[264px] text-[13px] leading-[21px] text-ink-muted">Rumah Sakit Umum Daerah kelas A milik Pemerintah Provinsi Jawa Timur yang beroperasi sebagai Badan Layanan Umum Daerah (BLUD) di Kota Surabaya.</p>
                    <p class="mt-6 inline-flex items-center gap-2 rounded-full bg-brand-100 px-3 py-[4px] text-[11px] font-medium tracking-[0.33px] text-brand-600">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2 4 5v6c0 5.25 3.4 10.74 8 11 4.6-.26 8-5.75 8-11V5l-8-3Zm-1.06 14.16-3.1-3.1 1.41-1.41 1.69 1.68 4.24-4.24 1.41 1.42-5.65 5.65Z"/>
                        </svg>
                        Standar Akreditasi Paripurna
                    </p>
                </div>

                <div>
                    <p class="mb-1 text-base font-semibold leading-[22px] text-ink">Kontak Resmi Pengaduan</p>
                    <div class="flex flex-col gap-1">
                        @foreach ([
                            ['ikon' => 'phone', 'teks' => 'Call Center: (031) 1500995'],
                            ['ikon' => 'wa', 'teks' => 'WhatsApp: +62 811-3000-995'],
                            ['ikon' => 'mail', 'teks' => 'pengaduan@rsudrsoetomo.jatimprov.go.id'],
                        ] as $kontak)
                            <p class="flex items-center gap-2 text-[13px] leading-[18px] text-ink-muted">
                                <svg class="h-3.5 w-3.5 shrink-0 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    @if ($kontak['ikon'] === 'phone')
                                        <path d="M5 3h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2h-9l-4.5 3V18H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    @elseif ($kontak['ikon'] === 'wa')
                                        <path d="M20 10c0 4.99-5.54 10.19-7 11.5C9.54 20.19 4 15 4 10a8 8 0 1 1 16 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    @else
                                        <path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="m22 7-10 6L2 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    @endif
                                </svg>
                                {{ $kontak['teks'] }}
                            </p>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="mb-1 text-base font-semibold leading-[22px] text-ink">Lokasi &amp; Alamat Kantor</p>
                    <p class="flex items-start gap-2 text-[13px] leading-[18px] text-ink-muted">
                        <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 10c0 4.99-5.54 10.19-7 11.5C9.54 20.19 4 15 4 10a8 8 0 1 1 16 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.6"/>
                        </svg>
                        Gedung Pusat Pelayanan &amp; Informasi Terpadu (Lantai 1), Jl. Mayjen Prof. Dr. Moestopo No.6-8, Surabaya 60286
                    </p>
                </div>

                <div>
                    <p class="mb-1 text-base font-semibold leading-[22px] text-ink">Jam Operasional Pengaduan</p>
                    <div class="flex flex-col gap-1">
                        <p class="text-[13px] font-bold leading-[18px] text-ink">Pelayanan Tatap Muka:<br>Senin - Kamis: 07.30 - 15.30 WIB<br>Jumat: 07.30 - 14.30 WIB</p>
                        <p class="text-[13px] font-bold leading-[18px] text-ink">Kanal Daring &amp; Call Center:<br>24 Jam Siaga Setiap Hari</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-[260px] border-t border-brand-200/30 pt-4">
                <p class="text-xs font-semibold tracking-[0.24px] text-ink-muted">© {{ now()->year }} RSUD Dr. Soetomo Surabaya. Seluruh hak cipta dilindungi — Sistem Aduan Publik Terintegrasi.</p>
                <div class="flex items-center gap-4 whitespace-nowrap text-xs font-semibold tracking-[0.24px] text-ink-muted">
                    <a href="#">Kebijakan Privasi</a>
                    <a href="#">Syarat &amp; Ketentuan</a>
                    <a href="#">FAQ Aduan</a>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>