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
            ['label' => 'Riwayat Aksi Admin', 'route' => 'admin.riwayat.index'],
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
                        <span class="font-title-md text-title-md text-on-surface font-bold tracking-tight leading-none">RSUD Dr. Soetomo</span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant tracking-wider uppercase mt-1">Surabaya &bull; Jawa Timur</span>
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

                <div class="relative" id="admin-notifikasi-wrapper">
                    <button
                        type="button"
                        id="admin-btn-notifikasi"
                        data-daftar-url="{{ route('admin.notifikasi.daftar') }}"
                        data-baca-url="{{ route('admin.notifikasi.baca') }}"
                        data-baca-semua-url="{{ route('admin.notifikasi.baca-semua') }}"
                        aria-label="Notifikasi"
                        aria-expanded="false"
                        class="relative p-space-xs text-on-surface-variant hover:text-brand-800 transition-colors"
                    >
                        <x-symbol nama="notifications" class="text-[24px]" />
                        @php
                            $jumlahNotifAdmin = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
                        @endphp
                        @if ($jumlahNotifAdmin > 0)
                            <span
                                id="admin-badge-notifikasi"
                                class="absolute -top-0.5 -right-0.5 w-5 h-5 min-w-5 rounded-full bg-error text-on-error text-[10px] font-bold flex items-center justify-center"
                                aria-label="{{ $jumlahNotifAdmin }} notifikasi belum dibaca"
                            >
                                {{ $jumlahNotifAdmin > 9 ? '9+' : $jumlahNotifAdmin }}
                            </span>
                        @endif
                    </button>

                    <div
                        id="admin-dropdown-notifikasi"
                        class="hidden absolute right-0 top-full mt-2 w-96 max-h-[420px] overflow-y-auto bg-white rounded-lg shadow-lg border border-outline-variant z-[60]"
                        role="menu"
                        aria-orientation="vertical"
                    >
                        <div class="p-3 border-b border-outline-variant flex items-center justify-between">
                            <h3 class="font-title-sm text-title-sm font-bold text-on-surface">Notifikasi</h3>
                            <form method="POST" action="{{ route('admin.notifikasi.baca-semua') }}" id="admin-form-tandai-semua" class="inline">
                                @csrf
                                <button
                                    type="submit"
                                    class="text-label-sm text-primary hover:text-primary/80 font-semibold transition-colors"
                                >
                                    Tandai semua dibaca
                                </button>
                            </form>
                        </div>
                        <div id="admin-daftar-notifikasi" class="max-h-[360px] overflow-y-auto">
                            <div class="p-4 text-center text-on-surface-variant text-body-sm">Memuat...</div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-space-sm pl-space-xs border-l border-outline-variant">
                    <span class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                        <x-symbol nama="person" class="text-on-primary text-[18px]" />
                    </span>
                    <div class="hidden xl:flex flex-col text-left">
                        <span class="font-label-lg text-label-lg text-on-surface font-semibold leading-tight">{{ auth()->user()?->name ?? 'Admin Humas' }}</span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant leading-none">Tim Humas RSUD</span>
                    </div>
                </div>

                <!-- Tombol Keluar: satu-satunya pintu akhiri sesi konsol admin,
                     sengaja dipisah garis dari identitas seperti menu akun. -->
                <form method="POST" action="{{ route('keluar') }}" class="pl-space-xs border-l border-outline-variant">
                    @csrf
                    <button
                        type="submit"
                        title="Keluar dari konsol admin"
                        class="flex items-center gap-space-xs rounded-full px-space-sm py-2 text-on-surface-variant transition-colors hover:text-error font-label-md text-label-md font-semibold"
                    >
                        <x-symbol nama="logout" class="text-[18px]" />
                        <span class="hidden sm:inline">Keluar</span>
                    </button>
                </form>
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
                                'text-on-surface-variant hover:text-on-surface hover:bg-surface-container font-title-sm text-title-sm font-semibold' => ! $aktif,
                            ])
                            @if ($aktif) aria-current="page" @endif
                        >{{ $item['label'] }}</a>
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

    <script>
        (function () {
            const wrapper = document.getElementById('admin-notifikasi-wrapper');
            const btn = document.getElementById('admin-btn-notifikasi');
            const dropdown = document.getElementById('admin-dropdown-notifikasi');
            const daftar = document.getElementById('admin-daftar-notifikasi');
            const formBacaSemua = document.getElementById('admin-form-tandai-semua');
            const badge = document.getElementById('admin-badge-notifikasi');
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            if (!btn || !dropdown) {
                return;
            }

            function muatNotifikasi() {
                const url = btn.dataset.daftarUrl;
                if (!url) return;
                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => {
                        if (!daftar) return;
                        if (!data.sukses || !data.notifications || data.notifications.length === 0) {
                            daftar.innerHTML = '<div class="p-4 text-center text-on-surface-variant text-body-sm">Tidak ada notifikasi baru</div>';
                            if (badge) badge.remove();
                            return;
                        }
                        if (badge) {
                            const total = data.total_belum_dibaca || 0;
                            badge.textContent = total > 9 ? '9+' : total;
                            badge.setAttribute('aria-label', total + ' notifikasi belum dibaca');
                        } else {
                            const span = document.createElement('span');
                            span.id = 'admin-badge-notifikasi';
                            span.className = 'absolute -top-0.5 -right-0.5 w-5 h-5 min-w-5 rounded-full bg-error text-on-error text-[10px] font-bold flex items-center justify-center';
                            span.textContent = (data.total_belum_dibaca || 0) > 9 ? '9+' : (data.total_belum_dibaca || 0);
                            btn.appendChild(span);
                        }
                        daftar.innerHTML = '<div class="divide-y divide-outline-variant"></div>';
                        const container = daftar.querySelector('.divide-y');
                        data.notifications.forEach(notif => {
                            const item = document.createElement('div');
                            item.className = 'p-3 hover:bg-surface-container-low transition-colors cursor-pointer admin-notif-item';
                            item.dataset.notifId = notif.id;
                            item.dataset.notifUrl = notif.url || '';
                            item.innerHTML = `
                                <div class="flex gap-2">
                                    <span class="mt-0.5 text-primary"><svg xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 -960 960 960" width="18px" fill="currentColor"><path d="M160-200v-80h80v-280q0-83 50-147.5T420-792v-28q0-25 17.5-42.5T480-880q25 0 42.5 17.5T540-820v28q80 20 130 84.5T720-560v280h80v80H160Zm320-300Zm0 420q-33 0-56.5-23.5T400-160h160q0 33-23.5 56.5T480-80ZM320-280h320v-280q0-66-47-113t-113-47q-66 0-113 47t-47 113v280Z"/></svg></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-label-md text-label-md font-semibold text-on-surface">${notif.title}</p>
                                        <p class="font-body-sm text-body-sm text-on-surface-variant line-clamp-2">${notif.message}</p>
                                        <p class="font-label-sm text-label-sm text-on-surface-variant mt-1">${notif.created_at || ''}</p>
                                    </div>
                                </div>
                            `;
                            container.appendChild(item);
                        });
                    })
                    .catch(() => {
                        if (daftar) daftar.innerHTML = '<div class="p-4 text-center text-on-surface-variant text-body-sm">Gagal memuat notifikasi</div>';
                    });
            }

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
                if (!dropdown.classList.contains('hidden')) {
                    muatNotifikasi();
                }
            });

            document.addEventListener('click', function (e) {
                if (wrapper && !wrapper.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });

            daftar.addEventListener('click', function (e) {
                const item = e.target.closest('.admin-notif-item');
                if (!item) return;
                const id = item.dataset.notifId;
                const url = item.dataset.notifUrl;
                const bacaUrl = btn.dataset.bacaUrl;
                if (bacaUrl && id) {
                    fetch(bacaUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({ notification_ids: [id] })
                    }).catch(() => {});
                }
                if (url) {
                    window.location.href = url;
                }
            });

            if (formBacaSemua) {
                formBacaSemua.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const bacaSemuaUrl = btn.dataset.bacaSemuaUrl;
                    if (!bacaSemuaUrl) return formBacaSemua.submit();
                    fetch(bacaSemuaUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token
                        }
                    }).then(() => {
                        if (badge) badge.remove();
                        if (daftar) daftar.innerHTML = '<div class="p-4 text-center text-on-surface-variant text-body-sm">Tidak ada notifikasi baru</div>';
                    }).catch(() => formBacaSemua.submit());
                });
            }

            if (badge) {
                muatNotifikasi();
            }
        })();
    </script>
    {{-- Script khusus halaman, misalnya perpindahan tab pada detail tiket. --}}
    @stack('scripts')

</body>

</html>