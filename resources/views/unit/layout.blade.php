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
            // Workspace tiket tidak berdiri sendiri di topbar: halamannya
            // dibuka dari tombol detail pada beranda unit dan daftar
            // disposisi, sama seperti konsol admin.
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

                {{-- Notifikasi Bell --}}
                @include('unit.partials.notifikasi-dropdown')
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
                    <a
                        href="{{ $item['url'] }}"
                        @class([
                            'flex items-center rounded-full px-3 py-1.5 transition-colors',
                            'bg-secondary-container text-on-secondary-container font-bold' => $aktif,
                            'text-ink-muted hover:text-ink hover:bg-surface-container font-title-sm text-title-sm font-semibold' => ! $aktif,
                        ])
                        @if ($aktif) aria-current="page" @endif
                    >{{ $item['label'] }}</a>
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

    {{-- Script Notifikasi Unit (Global) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('btn-notifikasi');
            const dropdown = document.getElementById('dropdown-notifikasi');
            const wrapper = document.getElementById('notifikasi-wrapper');
            const badge = document.getElementById('badge-notifikasi');

            if (!btn || !dropdown) return;

            // Toggle dropdown
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const isOpen = dropdown.classList.toggle('hidden');
                btn.setAttribute('aria-expanded', !isOpen);
            });

            // Tutup dropdown klik di luar
            document.addEventListener('click', function (e) {
                if (!wrapper.contains(e.target)) {
                    dropdown.classList.add('hidden');
                    btn.setAttribute('aria-expanded', 'false');
                }
            });

            // Tutup dropdown tekan Escape
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    dropdown.classList.add('hidden');
                    btn.setAttribute('aria-expanded', 'false');
                }
            });

            // Delegasi klik item notifikasi -> tandai dibaca + redirect
            dropdown.addEventListener('click', function (e) {
                const item = e.target.closest('.notif-item');
                if (!item) return;

                const notifId = item.dataset.notifId;
                const notifUrl = item.dataset.notifUrl;
                const notifType = item.dataset.notifType;

                // Tandai dibaca via AJAX
                const markReadUrl = btn.dataset.markReadUrl;
                if (!markReadUrl) {
                    console.warn('Mark read URL not available');
                    return;
                }
                fetch(markReadUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ notification_ids: [notifId] }),
                }).then(response => response.json())
                    .then(data => {
                        if (data.sukses) {
                            // Update badge count
                            const currentCount = parseInt(badge?.textContent || '0', 10);
                            if (badge && currentCount > 0) {
                                const newCount = currentCount - 1;
                                if (newCount <= 0) {
                                    badge.remove();
                                } else {
                                    badge.textContent = newCount > 9 ? '9+' : newCount;
                                }
                            }

                            // Hapus item dari dropdown (animasi)
                            item.style.opacity = '0';
                            item.style.transform = 'translateX(10px)';
                            item.style.transition = 'opacity 0.2s, transform 0.2s';
                            setTimeout(() => item.remove(), 200);

                            // Redirect jika ada URL
                            if (notifUrl) {
                                window.location.href = notifUrl;
                            }
                        })
                        .catch(console.error);
            });

            // Load more (placeholder)
            const loadMoreBtn = document.getElementById('btn-load-more-notif');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    loadMoreBtn.textContent = 'Memuat...';
                    loadMoreBtn.disabled = true;
                    // TODO: Implementasi load more via AJAX
                    setTimeout(() => {
                        loadMoreBtn.textContent = 'Belum diimplementasikan';
                    }, 500);
                });
            }
        });
    });
    </script>

    {{-- Real-time Notification Polling (15 detik) --}}
    @if ($unit !== null)
        @verbatim
        <script>
            (function () {
                const wrapper = document.getElementById('notifikasi-wrapper');
                const btn = document.getElementById('btn-notifikasi');
                const badge = document.getElementById('badge-notifikasi');
                const dropdown = document.getElementById('dropdown-notifikasi');
                const list = document.getElementById('daftar-notifikasi');
                const markAllBtn = dropdown?.querySelector('form[action*="baca-semua"] button');
                let lastPoll = '{{ now()->toISOString() }}';
                let isPolling = false;

                if (!wrapper || !btn || !dropdown) return;

                async function pollNotifications() {
                    if (isPolling) return;
                    isPolling = true;

                    try {
                        const url = '{{ route("unit.notifikasi.realtime", $unit) }}' + '?since=' + encodeURIComponent(lastPoll);
                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });

                        if (!response.ok) throw new Error('Poll failed');

                        const data = await response.json();
                        lastPoll = data.server_time;

                        if (data.sukses && data.notifications.length > 0) {
                            // Update badge
                            if (badge) {
                                badge.textContent = data.total_belum_dibaca > 9 ? '9+' : data.total_belum_dibaca;
                                badge.style.display = 'flex';
                            } else if (data.total_belum_dibaca > 0) {
                                const newBadge = document.createElement('span');
                                newBadge.id = 'badge-notifikasi';
                                newBadge.className = 'absolute -top-0.5 -right-0.5 w-5 h-5 min-w-5 rounded-full bg-error text-on-error text-[10px] font-bold flex items-center justify-center animate-pulse';
                                newBadge.setAttribute('aria-label', data.total_belum_dibaca + ' notifikasi belum dibaca');
                                newBadge.textContent = data.total_belum_dibaca > 9 ? '9+' : data.total_belum_dibaca;
                                btn.appendChild(newBadge);
                            }

                            // Prepend new notifications to dropdown
                            if (list) {
                                for (const notif of data.notifications.reverse()) {
                                    const item = createNotificationElement(notif);
                                    list.prepend(item);
                                }

                                // Show nudge indicator if any new nudge
                                const hasNudge = data.notifications.some(n => n.type === 'pengaduan_nudge_unit');
                                if (hasNudge && !btn.querySelector('.bg-warning')) {
                                    const nudgeDot = document.createElement('span');
                                    nudgeDot.className = 'absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-warning ring-2 ring-white';
                                    nudgeDot.setAttribute('aria-label', 'Ada follow up (nudge) dari admin humas');
                                    btn.appendChild(nudgeDot);
                                }
                            }
                        }
                    } catch (e) {
                        console.warn('Notification poll error:', e);
                    } finally {
                        isPolling = false;
                    }
                }

                function createNotificationElement(notif) {
                    const iconMap = {
                        'pengaduan_nudge_unit': 'notifications_active',
                        'pengaduan_sla_mendekati': 'timelapse',
                        'pengaduan_sla_lewat': 'warning',
                        'pengaduan_jawaban_unit': 'reply',
                        'pengaduan_disposisi': 'forward_to_inbox',
                        'pengaduan_tahap_berubah': 'swap_vert',
                        'pengaduan_kasus_berat': 'warning',
                        default: 'notifications',
                    };
                    const badgeClassMap = {
                        'pengaduan_nudge_unit': 'bg-warning text-on-warning',
                        'pengaduan_sla_lewat': 'bg-error-container text-on-error-container',
                        'pengaduan_sla_mendekati': 'bg-tertiary-container text-on-tertiary-container',
                        'pengaduan_jawaban_unit': 'bg-secondary-container text-on-secondary-container',
                        'pengaduan_disposisi': 'bg-primary-container text-on-primary-container',
                        default: 'bg-surface-container-highest text-on-surface-variant',
                    };
                    const icon = iconMap[notif.type] || iconMap.default;
                    const badgeClass = badgeClassMap[notif.type] || badgeClassMap.default;

                    const item = document.createElement('div');
                    item.className = 'p-3 hover:bg-surface-container-low transition-colors cursor-pointer notif-item';
                    item.dataset.notifId = notif.id;
                    item.dataset.notifUrl = notif.url || '';
                    item.dataset.notifType = notif.type;
                    item.role = 'menuitem';
                    item.tabIndex = 0;

                    item.innerHTML = `
                        <div class="flex items-start gap-3">
                            <span class="w-8 h-8 rounded-full ${badgeClassMap[notif.type] || badgeClassMap.default} flex items-center justify-center shrink-0 flex-shrink-0">
                                <x-symbol :nama="${iconMap[notif.type] || iconMap.default}" class="text-[18px]" />
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="font-title-sm text-title-sm font-bold text-ink truncate">${escapeHtml(notif.title)}</h4>
                                    <span class="text-[11px] text-ink-muted whitespace-nowrap shrink-0">${notif.waktu_relatif}</span>
                                </div>
                                <p class="text-body-sm text-ink-muted mt-0.5 line-clamp-2">${escapeHtml(notif.message)}</p>
                                ${notif.kode_tiket ? `
                                    <div class="mt-1.5 flex items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-brand-100 text-brand-800 font-mono font-body-xs text-body-xs font-bold">
                                            <x-symbol nama="confirmation_number" class="text-[10px]" />
                                            ${escapeHtml(notif.kode_tiket)}
                                        </span>
                                        ${notif.url ? `
                                            <a href="${notif.url}" class="text-label-sm text-secondary hover:text-secondary/80 font-semibold transition-colors whitespace-nowrap">
                                                <x-symbol nama="open_in_new" class="text-[12px]" />
                                                Buka
                                            </a>
                                        ` : ''}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                    return item;
                }

                function escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }

                // Start polling every 15 seconds
                setInterval(pollNotifications, 15000);
                // Initial poll after 2 seconds
                setTimeout(pollNotifications, 2000);

                // Also poll when tab becomes visible
                document.addEventListener('visibilitychange', function () {
                    if (!document.hidden) {
                        pollNotifications();
                    }
                });
            })();
        </script>
        @endverbatim
    @endif

    {{-- Script khusus halaman, misalnya perpindahan tab pada detail tiket. --}}
    @stack('scripts')

</body>

</html>
