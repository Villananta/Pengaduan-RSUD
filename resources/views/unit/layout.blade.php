<!DOCTYPE html>

<html lang="id" class="scroll-smooth">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Dashboard Unit') — Portal Pengaduan Humas | RSUD Dr. Soetomo</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="bg-surface font-body-md text-on-surface min-h-screen">

        @php
            // Menu unit hanya muncul ketika kode unit sudah terbaca dari URL,
            // supaya halaman pemilih unit tidak dipenuhi tautan yang tujuannya
            // belum bisa ditentukan. Menu "Pilih Unit" justru hanya ada di
            // halaman pemilih: begitu pengguna masuk ke dashboard unit, pintu
            // keluar dari unit itu dihapus dari topbar supaya tidak memancing
            // orang pindah unit tanpa sengaja.
            $navigasi = [];

            if ($unit !== null) {
                $navigasi[] = [
                    'label' => 'Beranda Unit',
                    'pola' => 'unit.dashboard',
                    'url' => route('unit.dashboard', $unit),
                ];

                $navigasi[] = [
                    'label' => 'Daftar Disposisi Masuk',
                    'pola' => 'unit.disposisi.index',
                    'url' => route('unit.disposisi.index', $unit),
                ];

                // Workspace selalu menunjuk satu tiket tertentu. Di luar
                // halaman detail tujuannya belum bisa ditentukan, jadi
                // menunya ditampilkan tanpa tautan, bukan diarahkan ke daftar
                // yang isinya berbeda dengan yang dijanjikan menu.
                $kodeTiket = request()->route('kode');

                $navigasi[] = [
                    'label' => 'Workspace & Form Jawaban',
                    'pola' => 'unit.disposisi.show',
                    'url' => is_string($kodeTiket) && $kodeTiket !== ''
                        ? route('unit.disposisi.show', ['unit' => $unit, 'kode' => $kodeTiket])
                        : null,
                ];

                $navigasi[] = [
                    'label' => 'Riwayat & Arsip Disposisi',
                    'pola' => 'unit.disposisi.arsip',
                    'url' => route('unit.disposisi.arsip', $unit),
                ];
            } else {
                // Di halaman pemilih unit belum ada tujuan lain, jadi satu-satunya
                // menu yang bisa diberi tautan tetap adalah pemilih unit itu sendiri.
                $navigasi[] = [
                    'label' => 'Pilih Unit',
                    'pola' => 'unit.pilih',
                    'url' => route('unit.pilih'),
                ];
            }
        @endphp

    <!-- Topbar -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-white shadow-[0_2px_14px_rgba(26,26,46,0.08)]">

        <!-- Baris Identitas: logo, standar SLA, dan unit yang sedang dibuka. -->
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

                @if ($unit !== null)
                    <span class="hidden lg:inline-flex items-center px-space-sm py-0.5 rounded-full bg-secondary-container text-on-secondary-container font-label-sm text-label-sm font-bold uppercase tracking-wider">
                        PIC Unit &bull; {{ $unit->namaLengkap() }}
                    </span>
                @else
                    <span class="hidden lg:inline-flex items-center px-space-sm py-0.5 rounded-full bg-secondary-container text-on-secondary-container font-label-sm text-label-sm font-bold uppercase tracking-wider">
                        Pilih Unit Layanan
                    </span>
                @endif
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

                <div class="flex items-center gap-space-sm pl-space-xs border-l border-outline-variant">
                    <span class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                        <x-symbol nama="badge" class="text-on-primary text-[18px]" />
                    </span>
                    <div class="hidden xl:flex flex-col text-left">
                        <span class="font-label-lg text-label-lg text-ink font-semibold leading-tight">
                            {{ $unit?->pic ?? 'Belum ada PIC' }}
                        </span>
                        <span class="font-label-sm text-label-sm text-ink-muted leading-none">
                            {{ $unit?->jabatan_pic ?? 'Pilih unit untuk mulai bekerja' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Baris Identitas -->

        <!-- Navigasi Utama -->
        <div class="bg-white border-b border-outline-variant h-12 w-full px-margin flex items-center justify-between">
                <nav class="flex items-center h-full gap-space-sm overflow-x-auto" aria-label="Navigasi dashboard unit">
                    @foreach ($navigasi as $item)

                        @php
                            $aktif = request()->routeIs($item['pola']);
                        @endphp

                        {{-- Menu aktif memakai pill mint, jadi penanda halaman
                             terbaca tanpa memotong tinggi bilah navigasi. --}}
                        @if ($item['url'] === null)
                            {{-- Menu tanpa tujuan tetap ditampilkan sebagai label
                                 supaya urutan menu sama dengan halaman lain, tapi
                                 tidak boleh berpura-pura menjadi tautan. --}}
                            <span
                                class="flex items-center rounded-full px-3 py-1.5 text-outline cursor-not-allowed font-title-sm text-title-sm font-semibold"
                            >{{ $item['label'] }}</span>
                        @else
                            <a
                                href="{{ $item['url'] }}"
                                @class([
                                    'flex items-center rounded-full px-3 py-1.5 transition-colors',
                                    'bg-secondary-container text-on-secondary-container font-bold' => $aktif,
                                    'text-ink-muted hover:text-ink hover:bg-surface-container font-title-sm text-title-sm font-semibold' => ! $aktif,
                                ])
                                @if ($aktif) aria-current="page" @endif
                            >{{ $item['label'] }}</a>
                        @endif
                    @endforeach
                </nav>

            <div class="hidden md:flex items-center gap-space-xs text-ink-muted font-label-md text-label-md">
                <x-symbol nama="domain" class="text-[16px] text-outline" />
                <span>Unit Pelayanan</span>
                <x-symbol nama="chevron_right" class="text-[14px] text-outline-variant" />
                <span class="text-ink font-semibold">{{ $unit?->nama ?? 'Pilih Instalasi' }}</span>
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
