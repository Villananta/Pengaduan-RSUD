<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard Admin') — Sistem Pengaduan RSUD Dr. Soetomo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-admin-bg font-sans text-admin-ink antialiased">

@php
    $navigasi = [
        [
            'label' => 'Beranda',
            'url' => route('admin.dashboard'),
            'aktif' => request()->routeIs('admin.dashboard'),
        ],
        [
            'label' => 'Pengaduan',
            'url' => route('admin.pengaduan.index'),
            'aktif' => request()->routeIs('admin.pengaduan.*'),
        ],
        [
            'label' => 'Form Publik',
            'url' => route('pengaduan.create'),
            'aktif' => request()->routeIs('pengaduan.*'),
        ],
    ];
@endphp

<header class="sticky top-0 z-20 bg-white shadow-[0_1px_8px_rgba(17,28,45,0.08)]">
    {{-- Baris utama: identitas RSUD, status sistem, dan kontrol admin. --}}
    <div class="flex flex-wrap items-center justify-between gap-4 bg-admin-brand-900 px-4 py-3 sm:px-8">
        <div class="flex items-center gap-2 sm:gap-3">
            <x-brand-logo class="h-9 w-9 rounded bg-white p-0.5" />
            <div>
                <p class="text-base font-bold leading-none tracking-[-0.4px] text-white">RSUD Dr. Soetomo</p>
                <p class="mt-1 text-[11px] font-bold uppercase leading-3.5 tracking-[0.55px] text-admin-brand-300">
                    Surabaya &bull; Jawa Timur
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center gap-2 rounded-full bg-admin-success-soft px-2 py-0.5">
                <span class="h-1.5 w-1.5 rounded-full bg-admin-success-strong"></span>
                <span class="text-[11px] font-bold uppercase leading-[14px] tracking-[0.55px] text-admin-success-strong">
                    Sistem Aktif
                </span>
            </span>

            <div class="hidden items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-2 py-1 sm:flex">
                <x-icon nama="dokumen" class="h-3.5 w-3.5 text-admin-warning-veil" />
                <span class="text-[11px] font-bold uppercase leading-[11px] tracking-[0.44px] text-admin-warning-veil">
                    Permenkes No. 4/2018
                </span>
            </div>

            <button
                type="button"
                class="relative flex flex-col items-center justify-center rounded px-1 pb-2.5 pt-1 text-admin-brand-300"
                aria-label="Notifikasi penting"
            >
                <x-icon nama="lonceng" class="h-4 w-4" />
                <span class="absolute right-0 top-0 h-2.5 w-2.5 rounded-full bg-admin-danger-base ring-2 ring-admin-brand-900"></span>
            </button>

            <div class="flex items-center gap-2 border-l border-white/20 pl-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-admin-brand-800">
                    <x-icon nama="orang" class="h-3 w-3 text-white" />
                </span>
                <div class="hidden sm:block">
                    <p class="text-sm font-semibold leading-[18px] text-white">BGM</p>
                    <p class="text-[11px] font-bold uppercase leading-[11px] tracking-[0.44px] text-admin-brand-300">
                        Admin Humas
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Navigasi utama konsol admin. --}}
    <nav class="flex flex-wrap items-center justify-between gap-3 border-b border-admin-border px-4 sm:px-8">
        <div class="flex flex-wrap items-center">
            @foreach ($navigasi as $item)
                <a
                    href="{{ $item['url'] }}"
                    @class([
                        'inline-flex items-center border-b-[3px] px-3 py-2.5',
                        'border-admin-brand-700 bg-admin-info-soft/50 text-sm font-semibold text-admin-brand-700' => $item['aktif'],
                        'border-transparent text-sm font-semibold text-admin-ink-muted hover:bg-admin-info-soft' => ! $item['aktif'],
                    ])
                >{{ $item['label'] }}</a>
            @endforeach
        </div>

        <div class="flex items-center gap-1.5 py-1.5 text-xs font-semibold text-admin-ink-muted">
            <x-icon nama="jam" class="h-3 w-3 text-admin-ink-subtle" />
            <span>Terakhir diperbarui {{ now()->format('d M Y H:i') }} WIB</span>
        </div>
    </nav>
</header>

<main class="px-4 py-6 sm:px-8">
    <p class="mb-6 rounded-xl border border-dashed border-admin-info-mid bg-white px-4 py-3 text-xs text-admin-ink-muted">
        Mode demo: autentikasi admin belum tersedia. Akses dashboard ini akan dibatasi setelah akun admin humas dibuat.
    </p>

    @yield('content')
</main>

<footer class="mt-8 border-t border-admin-border bg-admin-surface-alt">
    <div class="flex flex-wrap items-center justify-between gap-4 px-4 py-5 sm:px-8">
        <div class="flex items-center gap-2">
            <x-brand-logo class="h-7 w-7" />
            <div>
                <p class="text-sm font-bold leading-5 text-admin-ink">RSUD Dr. Soetomo</p>
                <p class="text-xs leading-4 text-admin-ink-muted">Sistem Pengaduan Pelayanan</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 text-xs text-admin-ink-muted">
            <span class="font-bold uppercase tracking-[0.44px] text-admin-ink-muted">SLA {{ \App\Support\Sla::hariKerja() }} Hari Kerja</span>
            <span class="text-admin-border" aria-hidden="true">&bull;</span>
            <span>Permenkes No. 4/2018</span>
            <span class="text-admin-border" aria-hidden="true">&bull;</span>
            <span>Humas &amp; Promosi</span>
        </div>
    </div>
</footer>

</body>
</html>
