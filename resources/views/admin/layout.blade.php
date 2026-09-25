<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard Admin') — Sistem Pengaduan RSUD Dr. Soetomo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans bg-brand-light text-ink antialiased">
    <header class="w-full bg-white shadow-[0_1px_8px_rgba(0,0,0,0.04)]">
        <div class="flex w-full flex-wrap items-center justify-between gap-3 px-8 py-5">
            <div>
                <p class="text-base font-bold text-brand-800">Dashboard Admin Humas</p>
                <p class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">Sistem Pengaduan Pelayanan RSUD Dr. Soetomo</p>
            </div>

            <nav class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.pengaduan.index') }}" class="rounded-lg bg-brand-700 px-3 py-1.5 text-xs font-semibold text-white">
                    Daftar Pengaduan
                </a>
                <a href="{{ route('pengaduan.lacak') }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-ink-muted hover:bg-brand-50">
                    Halaman Publik
                </a>
            </nav>
        </div>
    </header>

    <main class="w-full px-8 py-8">
        <p class="mb-6 rounded-xl border border-dashed border-brand-200 bg-white px-4 py-3 text-xs text-ink-muted">
            Mode demo: autentikasi admin belum tersedia. Akses dashboard ini akan dibatasi setelah akun admin humas dibuat.
        </p>

        @yield('content')
    </main>
</body>
</html>
