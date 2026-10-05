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

    <!-- Header -->
    <header class="fixed inset-x-0 top-0 z-20 bg-white/90 shadow-[0_1px_8px_rgba(0,0,0,0.04)] backdrop-blur-md">
        <div class="flex h-20 w-full items-center justify-between px-8">
            <div class="flex items-center gap-2">
                <x-brand-logo class="h-9 w-9" />
                <div class="leading-tight">
                    <p class="text-base font-bold text-brand-800">RSUD Dr. Soetomo</p>
                    <p class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">Sistem Pengaduan Pelayanan</p>
                </div>
            </div>

            {{-- Menu utama disimpan di array supaya penanda halaman aktif
                 cukup dibaca dari route, bukan ditulis ulang tiap item. --}}
            @php
                $menu = [
                    ['label' => 'Buat Aduan', 'url' => route('pengaduan.create'), 'aktif' => request()->routeIs('pengaduan.create', 'pengaduan.sukses')],
                    ['label' => 'Cek Status Tiket', 'url' => route('pengaduan.lacak'), 'aktif' => request()->routeIs('pengaduan.lacak')],
                    ['label' => 'Prosedur Pengaduan', 'url' => route('maklumat.index'), 'aktif' => request()->routeIs('maklumat.index')],
                ];
            @endphp

            <nav class="flex items-center gap-1">

                @foreach ($menu as $item)
                    <a
                        href="{{ $item['url'] }}"
                        @class([
                            'rounded-lg px-2 py-1 text-sm font-semibold',
                            'bg-brand-700 text-white' => $item['aktif'],
                            'text-ink-muted' => ! $item['aktif'],
                        ])
                    >{{ $item['label'] }}</a>
                @endforeach

            </nav>
        </div>
    </header>
    <!-- End of Header -->

    <!-- Konten Halaman -->
    <main class="pt-20">

        @yield('content')

    </main>
    <!-- End of Konten Halaman -->

    <!-- Footer -->
    <footer class="relative z-10 w-full bg-white/90 py-10 shadow-[0_1px_8px_rgba(0,0,0,0.02)]">
        <div class="flex w-full flex-col gap-10 px-8">
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
                    <!-- Tautan hukum belum punya halaman tujuan, jadi ditulis sebagai
                     teks non-tautan supaya tidak ada jebakan klik kosong. -->
                    <span class="cursor-not-allowed">Kebijakan Privasi</span>
                    <span class="cursor-not-allowed">Syarat &amp; Ketentuan</span>
                    <span class="cursor-not-allowed">FAQ Aduan</span>
                </div>
            </div>
        </div>
    </footer>
    <!-- End of Footer -->

    @stack('scripts')

</body>

</html>