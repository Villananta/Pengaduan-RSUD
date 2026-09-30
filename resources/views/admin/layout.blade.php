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
<body class="bg-surface font-body-md text-on-surface min-h-screen flex flex-col">

@php
    /*
     * Navigasi konsol admin.
     *
     * Item dibedakan menjadi tiga bentuk supaya tidak ada menu yang
     * ditampilkan lebihenabled daripada kenyataannya:
     *
     * - 'route'  : punya halaman sendiri, jadi benar-benar tautan.
     * - 'pola'   : fiturnya sudah jadi tetapi tidak punya halaman indeks.
     *   Detail Pengaduan hanya bisa dibuka per tiket dari daftar pengaduan,
     *   jadi menunya tidak punya tujuan untuk diklik.
     * - kosong   : fiturnya belum dibangun, tampil sebagai menu mati.
     *
     * Penanda aktif diambil dari route yang sedang dibuka, sehingga Detail
     * Pengaduan tetap ditandai aktif meskipun tidak punya tautan.
     */
    $beranda = 'admin.dashboard';
    $navigasi = [
        ['label' => 'Beranda Utama', 'route' => $beranda],
        ['label' => 'Daftar Pengaduan', 'route' => 'admin.pengaduan.index'],
        ['label' => 'Detail Pengaduan', 'route' => null, 'pola' => 'admin.pengaduan.show'],
        ['label' => 'Monitor Disposisi & SLA', 'route' => null],
        ['label' => 'Master Data Unit', 'route' => null],
    ];
@endphp

<header class="fixed top-0 left-0 right-0 z-50 shadow-[0_1px_8px_rgba(17,28,45,0.08)]">
    {{-- Baris identitas: logo, standar SLA, dan petugas humas. --}}
    <div class="bg-primary-container text-on-primary h-16 w-full px-margin flex items-center justify-between">
        <div class="flex items-center gap-space-md">
            <div class="flex items-center gap-space-sm">
                <x-brand-logo class="h-9 w-9 object-contain rounded-sm bg-surface-container-lowest p-0.5" />
                <div class="flex flex-col">
                    <span class="font-title-md text-title-md text-on-primary font-bold tracking-tight leading-none">RSUD Dr. Soetomo</span>
                    <span class="font-label-sm text-label-sm text-on-primary-container tracking-wider uppercase mt-1">Surabaya &bull; Jawa Timur</span>
                </div>
            </div>

            <div class="hidden md:block h-6 w-px bg-outline-variant/30 mx-space-xs" aria-hidden="true"></div>

            <span class="hidden lg:inline-flex items-center px-space-sm py-0.5 rounded-xl bg-secondary-container text-on-secondary-container font-label-sm text-label-sm font-bold uppercase tracking-wider">
                Admin Humas &amp; Kepatuhan Medis
            </span>
        </div>

        <div class="flex items-center gap-space-md">
            <div class="hidden sm:flex items-center gap-space-sm px-space-sm py-1 rounded-xl bg-tertiary-container/40 border border-tertiary-fixed-dim/40 text-tertiary-fixed">
                <x-symbol nama="timer" class="text-[18px] text-tertiary-fixed-dim animate-pulse" />
                <div class="flex flex-col text-left">
                    <span class="font-label-sm text-label-sm font-bold leading-none">SLA {{ \App\Support\Sla::hariKerja() }} Hari Kerja</span>
                    <span class="font-body-sm text-body-sm text-on-primary-container leading-none mt-0.5">Permenkes No. 4/2018</span>
                </div>
            </div>

            <button
                type="button"
                aria-label="Notifikasi penting"
                class="relative p-space-xs text-on-primary-container hover:text-on-primary transition-colors"
            >
                <x-symbol nama="notifications" class="text-[24px]" />
                <span class="absolute top-0 right-0 w-2.5 h-2.5 bg-error rounded-full ring-2 ring-primary-container" aria-hidden="true"></span>
            </button>

            <div class="flex items-center gap-space-sm pl-space-xs border-l border-outline-variant/20">
                <span class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                    <x-symbol nama="person" class="text-on-primary text-[18px]" />
                </span>
                <div class="hidden xl:flex flex-col text-left">
                    <span class="font-label-lg text-label-lg text-on-primary font-semibold leading-tight">{{ auth()->user()?->name ?? 'Admin Humas' }}</span>
                    <span class="font-label-sm text-label-sm text-on-primary-container leading-none">Tim Humas RSUD</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Navigasi utama. --}}
    <div class="bg-surface-container-lowest border-b border-outline-variant h-12 w-full px-margin flex items-center justify-between">
        <nav class="flex items-center h-full gap-space-md" aria-label="Navigasi konsol admin">
            @foreach ($navigasi as $item)
                @php
                    $penanda = $item['route'] ?? $item['pola'] ?? null;
                    $aktif = $penanda !== null && request()->routeIs($penanda);
                @endphp

                @if ($item['route'])
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'h-full flex items-center px-space-sm transition-colors',
                            'text-secondary border-b-[3px] border-secondary font-semibold bg-surface-container/50' => $aktif,
                            'text-on-surface-variant hover:text-on-surface font-title-sm text-title-sm' => ! $aktif,
                        ])
                        @if ($aktif) aria-current="page" @endif
                    >{{ $item['label'] }}</a>
                @elseif ($item['pola'] ?? false)
                    {{-- Menu tanpa halaman indeks. Tidak diklik karena tidak
                         ada tujuan yang boleh ditautkan, tapi tampil normal
                         supaya tidak terbaca sebagai fitur yang belum ada. --}}
                    <span
                        @class([
                            'h-full flex items-center px-space-sm font-title-sm text-title-sm',
                            'text-secondary border-b-[3px] border-secondary font-semibold bg-surface-container/50' => $aktif,
                            'text-on-surface-variant' => ! $aktif,
                        ])
                        @if ($aktif) aria-current="page" @endif
                    >{{ $item['label'] }}</span>
                @else
                    <span
                        @class([
                            'h-full flex items-center px-space-sm font-title-sm text-title-sm',
                            'select-none',
                            'text-secondary border-b-[3px] border-secondary font-semibold bg-surface-container/50' => $aktif,
                            'text-on-surface-variant/50 cursor-not-allowed' => ! $aktif,
                        ])
                        @if (! $aktif) title="Fitur sedang dirancang" aria-disabled="true" @endif
                    >{{ $item['label'] }}</span>
                @endif
            @endforeach
        </nav>

        <div class="hidden md:flex items-center gap-space-xs text-on-surface-variant font-label-md text-label-md">
            <x-symbol nama="domain" class="text-[16px] text-outline" />
            <span>Humas</span>
            <x-symbol nama="chevron_right" class="text-[14px] text-outline-variant" />
            <span class="text-on-surface font-semibold">Pelayanan Terpadu</span>
        </div>
    </div>
</header>

<main class="w-full pt-28 bg-surface flex-1">
    @yield('content')
</main>

<footer class="w-full bg-surface-container-low border-t border-outline-variant mt-space-xl py-space-lg">
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

{{-- Script khusus halaman, misalnya perpindahan tab pada detail tiket. --}}
@stack('scripts')

</body>
</html>
