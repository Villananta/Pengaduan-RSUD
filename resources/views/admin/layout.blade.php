<!DOCTYPE html>

<html lang="id" class="scroll-smooth">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Beranda Utama') — Portal Pengaduan Humas | RSUD Dr. Soetomo</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="bg-surface font-body-md text-on-surface min-h-screen">

    @php
        $beranda = 'admin.dashboard';
        $navigasi = [
            ['label' => 'Beranda Utama', 'route' => $beranda],
            ['label' => 'Daftar Pengaduan', 'route' => 'admin.pengaduan.index'],
            ['label' => 'Input Aduan', 'route' => 'admin.pengaduan.create'],
            ['label' => 'Monitor Disposisi & SLA', 'route' => 'admin.monitor.index'],
            ['label' => 'Master Data Unit', 'route' => 'admin.unit.index', 'pola' => 'admin.unit.*'],
        ];
    @endphp

    <!-- Topbar -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-white shadow-[0_2px_14px_rgba(26,26,46,0.08)]">

        <!-- Baris Identitas: logo, standar SLA, dan petugas humas. -->
        <div class="bg-white h-16 w-full px-margin flex items-center justify-between">
            <div class="flex items-center gap-space-md">
                <div class="flex items-center gap-space-sm">
                    <x-brand-logo class="h-9 w-9 object-contain rounded-sm bg-surface-container-low p-0.5" />
                    <div class="flex flex-col">
                        <span class="font-title-md text-title-md text-ink font-bold tracking-tight leading-none">RSUD Dr. Soetomo</span>
                        <span class="font-label-sm text-label-sm text-ink-muted tracking-wider uppercase mt-1">Surabaya &bull; Jawa Timur</span>
                    </div>
                </div>

                <div class="hidden md:block h-6 w-px bg-outline-variant mx-space-xs" aria-hidden="true"></div>

                <span class="hidden lg:inline-flex items-center px-space-sm py-0.5 rounded-full bg-secondary-container text-on-secondary-container font-label-sm text-label-sm font-bold uppercase tracking-wider">
                    Admin Humas &amp; Kepatuhan Medis
                </span>
            </div>

            <div class="flex items-center gap-space-md">
                <!-- Badge standar SLA memakai emas karena menandai angka
                     pencapaian yang dijanjikan, bukan sekadar informasi. -->
                <div class="hidden sm:flex items-center gap-space-sm px-space-sm py-1 rounded-xl bg-gold-100 border border-gold-300 text-gold-800">
                    <x-symbol nama="timer" class="text-[18px] text-gold-700 animate-pulse" />
                    <div class="flex flex-col text-left">
                        <span class="font-label-sm text-label-sm font-bold leading-none">SLA {{ \App\Support\Sla::hariKerja() }} Hari Kerja</span>
                        <span class="font-body-sm text-body-sm text-gold-700 leading-none mt-0.5">Permenkes No. 4/2018</span>
                    </div>
                </div>

                <button
                    type="button"
                    aria-label="Notifikasi penting"
                    class="relative p-space-xs text-ink-muted hover:text-brand-800 transition-colors"
                >
                    <x-symbol nama="notifications" class="text-[24px]" />
                    <span class="absolute top-0 right-0 w-2.5 h-2.5 bg-error rounded-full ring-2 ring-white" aria-hidden="true"></span>
                </button>

                <div class="flex items-center gap-space-sm pl-space-xs border-l border-outline-variant">
                    <span class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                        <x-symbol nama="person" class="text-on-primary text-[18px]" />
                    </span>
                    <div class="hidden xl:flex flex-col text-left">
                        <span class="font-label-lg text-label-lg text-ink font-semibold leading-tight">{{ auth()->user()?->name ?? 'Admin Humas' }}</span>
                        <span class="font-label-sm text-label-sm text-ink-muted leading-none">Tim Humas RSUD</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Baris Identitas -->

        <!-- Navigasi Utama -->
        <div class="bg-white border-b border-outline-variant h-12 w-full px-margin flex items-center justify-between">
            <nav class="flex items-center h-full gap-space-sm" aria-label="Navigasi konsol admin">
                @foreach ($navigasi as $item)
                    @php
                        $penanda = $item['pola'] ?? $item['route'] ?? null;
                        $aktif = $penanda !== null && request()->routeIs($penanda);
                    @endphp

                    {{-- Menu aktif memakai pill mint, jadi penanda halaman
                         terbaca tanpa memotong tinggi bilah navigasi. --}}
                    @if ($item['route'])
                        <a
                            href="{{ route($item['route']) }}"
                            @class([
                                'flex items-center rounded-full px-3 py-1.5 transition-colors',
                                'bg-secondary-container text-on-secondary-container font-bold' => $aktif,
                                'text-ink-muted hover:text-ink hover:bg-surface-container font-title-sm text-title-sm font-semibold' => ! $aktif,
                            ])
                            @if ($aktif) aria-current="page" @endif
                        >{{ $item['label'] }}</a>
                    @elseif ($item['pola'] ?? false)
                       
                        <span
                            @class([
                                'flex items-center rounded-full px-3 py-1.5 font-title-sm text-title-sm font-semibold',
                                'bg-secondary-container text-on-secondary-container font-bold' => $aktif,
                                'text-ink-muted' => ! $aktif,
                            ])
                            @if ($aktif) aria-current="page" @endif
                        >{{ $item['label'] }}</span>
                    @else
                        <span
                            @class([
                                'flex items-center rounded-full px-3 py-1.5 font-title-sm text-title-sm font-semibold',
                                'select-none',
                                'bg-secondary-container text-on-secondary-container font-bold' => $aktif,
                                'text-ink-muted/50 cursor-not-allowed' => ! $aktif,
                            ])
                            @if (! $aktif) title="Fitur sedang dirancang" aria-disabled="true" @endif
                        >{{ $item['label'] }}</span>
                    @endif
                @endforeach
            </nav>

            <div class="hidden md:flex items-center gap-space-xs text-ink-muted font-label-md text-label-md">
                <x-symbol nama="domain" class="text-[16px] text-outline" />
                <span>Humas</span>
                <x-symbol nama="chevron_right" class="text-[14px] text-outline-variant" />
                <span class="text-ink font-semibold">Pelayanan Terpadu</span>
            </div>
        </div>
        <!-- End of Navigasi Utama -->

    </header>
    <!-- End of Topbar -->

    <!-- Main Content -->
    <main class="w-full pt-28 bg-surface flex-1">
        @yield('content')
    </main>
    <!-- End of Main Content -->

    <!-- Footer -->
    <footer class="w-full bg-white border-t border-outline-variant mt-space-xl py-space-lg">
        <div class="w-full px-margin flex flex-col md:flex-row items-center justify-between gap-space-md">
            <div class="flex items-center gap-space-md">
                <div class="flex items-center gap-space-xs">
                    <x-brand-logo class="h-7 w-7 object-contain rounded-sm shrink-0" />
                    <span class="font-title-sm text-title-sm text-on-surface font-bold">RSUD Dr. Soetomo Surabaya</span>
                </div>
                <span class="text-outline-variant" aria-hidden="true">&bull;</span>
                <span class="font-body-sm text-body-sm text-on-surface-variant">Pemerintah Provinsi Jawa Timur</span>
                <span class="text-outline-variant" aria-hidden="true">&bull;</span>
                <span class="font-body-sm text-body-sm text-on-surface-variant">Terintegrasi SIMRS &amp; Kemenkes RI</span>
            </div>

            <div class="font-label-sm text-label-sm text-on-surface-variant flex items-center gap-space-md">
                <span>SLA Standar: Permenkes No. 4/2018 (Maksimal {{ \App\Support\Sla::hariKerja() }} Hari Kerja)</span>
                <span>&copy; {{ now()->year }} Instalasi Humas &amp; Pemasaran RSUD Dr. Soetomo. Hak Cipta Dilindungi.</span>
            </div>
        </div>
    </footer>
    <!-- End of Footer -->

    {{-- Script khusus halaman, misalnya perpindahan tab pada detail tiket. --}}
    @stack('scripts')

</body>

</html>