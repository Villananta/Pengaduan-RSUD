<!DOCTYPE html>

<html lang="id" class="scroll-smooth">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Token CSRF dipakai skrip lonceng: penandaan "sudah dibaca" dan
         "tandai semua" dikirim lewat fetch, bukan lewat formulir biasa. --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard Unit') — Portal Pengaduan Humas | RSUD Dr. Soetomo</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="bg-surface font-body-md text-on-surface min-h-screen">

    @php
        // Menu unit hanya muncul ketika kode unit sudah terbaca dari URL.
        // Halaman "belum ditautkan" sengaja tampil tanpa menu apa pun:
        // belum ada unit yang tujuannya bisa dipastikan, jadi tidak ada
        // tautan yang layak dipasang di sana.
        $navigasi = [];

        if ($unit !== null) {
            // Ruang kerja tiket tidak berdiri sendiri di topbar: halamannya
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
                        <span class="font-title-md text-title-md text-on-surface font-bold tracking-tight leading-none">RSUD Dr. Soetomo</span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant tracking-wider uppercase mt-1">Surabaya &bull; Jawa Timur</span>
                    </div>
                </div>

                <div class="hidden md:block h-6 w-px bg-outline-variant mx-space-xs" aria-hidden="true"></div>

                @if ($unit !== null)
                    <span class="hidden lg:inline-flex items-center px-space-sm py-0.5 rounded-full bg-secondary-container text-on-secondary-container font-label-sm text-label-sm font-bold uppercase tracking-wider">
                        PIC Unit &bull; {{ $unit->namaLengkap() }}
                    </span>
                @else
                    
                    <span class="hidden lg:inline-flex items-center px-space-sm py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm text-label-sm font-bold uppercase tracking-wider">
                        Akun Belum Ditautkan
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

                @include('unit.partials.notifikasi-dropdown')

                <div class="flex items-center gap-space-sm pl-space-xs border-l border-outline-variant">
                    <span class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                        <x-symbol nama="badge" class="text-on-primary text-[18px]" />
                    </span>
                    <div class="hidden xl:flex flex-col text-left">
                        <span class="font-label-lg text-label-lg text-on-surface font-semibold leading-tight">
                            {{ $unit?->pic ?? 'PIC belum ditetapkan' }}
                        </span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant leading-none">
                            {{ $unit?->jabatan_pic ?? 'Hubungi admin humas untuk penautan akun' }}
                        </span>
                    </div>
                </div>

                {{-- Tombol Keluar --}}
                <form method="POST" action="{{ route('keluar') }}" class="pl-space-xs border-l border-outline-variant">
                    @csrf
                    <button
                        type="submit"
                        title="Keluar dari konsol unit"
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
                            'text-on-surface-variant hover:text-on-surface hover:bg-surface-container font-title-sm text-title-sm font-semibold' => ! $aktif,
                        ])
                        @if ($aktif) aria-current="page" @endif
                    >{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="hidden md:flex items-center gap-space-xs text-on-surface-variant font-label-md text-label-md">
                <x-symbol nama="domain" class="text-[16px] text-outline" />
                <span>Unit Pelayanan</span>
                <x-symbol nama="chevron_right" class="text-[14px] text-outline-variant" />
                <span class="text-on-surface font-semibold">{{ $unit?->nama ?? 'Belum ada unit' }}</span>
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

    {{-- Lencana lonceng dipegang dua skrip sekaligus: penandaan baca dan
         polling. Kalau salah satunya menyimpan node sendiri, node itu lepas
         dari DOM setiap kali yang lain menghapusnya, lalu lencana bisa hilang
         permanen atau muncul dobel. Jadi keduanya lewat satu pintu ini. --}}
    <script>
        // Catatan bersama dua skrip notifikasi: id yang baru ditandai dibaca
        // di halaman ini, dan nomor generasi untuk membuang respons poll yang
        // berangkat sebelum aksi baca selesai.
        window.statusNotifikasi = {
            sudahDibaca: new Set(),
            generasi: 0,
        };

        window.perbaruiLencanaNotifikasi = function (total) {
            const btn = document.getElementById('btn-notifikasi');
            if (!btn) return;

            let badge = document.getElementById('badge-notifikasi');

            if (total <= 0) {
                badge?.remove();
                return;
            }

            if (!badge) {
                badge = document.createElement('span');
                badge.id = 'badge-notifikasi';
                badge.className = 'absolute -top-0.5 -right-0.5 w-5 h-5 min-w-5 rounded-full bg-error text-on-error text-[10px] font-bold flex items-center justify-center';
                btn.appendChild(badge);
            }

            badge.textContent = total > 9 ? '9+' : total;
            badge.setAttribute('aria-label', total + ' notifikasi belum dibaca');
        };
    </script>

    {{-- Script Notifikasi Unit (Global) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('btn-notifikasi');
            const dropdown = document.getElementById('dropdown-notifikasi');
            const wrapper = document.getElementById('notifikasi-wrapper');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            if (!btn || !dropdown) return;

            // Buka atau tutup dropdown
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

            // Klik satu notifikasi: tandai sudah dibaca, lalu buka tiketnya.
            dropdown.addEventListener('click', function (e) {
                const item = e.target.closest('.notif-item');
                if (!item) return;

                const markReadUrl = btn.dataset.markReadUrl;
                if (!markReadUrl) return;

                fetch(markReadUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ notification_ids: [item.dataset.notifId] }),
                }).then(response => response.json())
                    .then(data => {
                        if (!data.sukses) return;

                        // Server mengirim sisa yang sebenarnya, bukan tebakan
                        // dari angka di layar, supaya lencana tetap benar
                        // ketika notifikasi juga dibaca di tab lain.
                        window.perbaruiLencanaNotifikasi(data.total_belum_dibaca ?? 0);
                        window.statusNotifikasi.sudahDibaca.add(item.dataset.notifId);

                        // Hilangkan barisnya dari daftar (animasi singkat)
                        item.style.opacity = '0';
                        item.style.transition = 'opacity 0.2s';
                        setTimeout(() => item.remove(), 200);

                        // Buka halaman tiket bila notifikasi membawa tautan
                        if (item.dataset.notifUrl) {
                            window.location.href = item.dataset.notifUrl;
                        }
                    })
                    .catch(error => console.warn('Gagal menandai notifikasi:', error));
            });

            // "Tandai semua dibaca" diambil alih lewat fetch supaya daftar
            // bisa dikosongkan tanpa memuat ulang halaman; tanpa JavaScript
            // formulirnya tetap terkirim dan server membalas dengan kembali
            // ke halaman yang sama.
            const formSemua = document.getElementById('form-tandai-semua');
            if (formSemua) {
                formSemua.addEventListener('submit', function (e) {
                    e.preventDefault();

                    fetch(formSemua.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }).then(response => response.json())
                        .then(data => {
                            if (!data.sukses) return;

                            const daftar = document.getElementById('daftar-notifikasi');
                            if (daftar) {
                                daftar.innerHTML = '<div class="p-4 text-center text-on-surface-variant text-body-sm">Tidak ada notifikasi baru</div>';
                            }

                            window.perbaruiLencanaNotifikasi(0);
                            // Respons poll yang sudah berangkat tidak boleh
                            // mengisi lagi daftar yang baru saja dikosongkan.
                            window.statusNotifikasi.generasi += 1;
                            btn.querySelectorAll('.bg-warning').forEach(el => el.remove());
                            dropdown.classList.add('hidden');
                            btn.setAttribute('aria-expanded', 'false');
                        })
                        .catch(error => console.warn('Gagal menandai semua notifikasi:', error));
                });
            }
        });
    </script>

    {{-- Real-time Notification Polling (15 detik) --}}
    @if ($unit !== null)
        <script>
            // Patokan awal dimundurkan satu detik karena created_at hanya
            // berpresisi detik; notifikasi yang masuk di detik yang sama
            // dengan render halaman tetap ikut poll pertama.
            window.konfigurasiNotifikasi = {
                urlPolling: @js(route('unit.notifikasi.realtime', $unit)),
                waktuPollPertama: @js(now()->startOfSecond()->subSecond()->toISOString()),
            };
        </script>

        @verbatim
        <script>
            (function () {
                const wrapper = document.getElementById('notifikasi-wrapper');
                const btn = document.getElementById('btn-notifikasi');
                const dropdown = document.getElementById('dropdown-notifikasi');
                const list = document.getElementById('daftar-notifikasi');
                let lastPoll = window.konfigurasiNotifikasi.waktuPollPertama;
                let isPolling = false;

                if (!wrapper || !btn || !dropdown) return;

                async function pollNotifications() {
                    if (isPolling) return;
                    isPolling = true;
                    const generasi = window.statusNotifikasi.generasi;

                    try {
                        const url = window.konfigurasiNotifikasi.urlPolling + '?since=' + encodeURIComponent(lastPoll);
                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });

                        if (!response.ok) throw new Error('Gagal menarik notifikasi');

                        const data = await response.json();

                        // Aksi baca sesudah permintaan ini berangkat membuat
                        // isinya sudah basi; patokan sengaja tidak dimajukan
                        // supaya poll berikutnya tetap menyusul.
                        if (window.statusNotifikasi.generasi !== generasi) return;

                        // Hasil yang penuh berarti masih ada notifikasi di luar
                        // halaman ini; patokan maju ke notifikasi terakhir
                        // halaman supaya sisanya ikut terbawa, bukan dilewati.
                        lastPoll = data.next_since || data.server_time;

                        window.perbaruiLencanaNotifikasi(data.total_belum_dibaca ?? 0);

                        let ditambahkan = 0;

                        if (data.sukses && data.notifications.length > 0 && list) {
                            // Item hasil polling harus masuk ke wadah ber-divider
                            // yang sama dengan item dari server. Kalau daftar masih
                            // berisi pesan "tidak ada notifikasi", pesan itu diganti
                            // wadah kosong supaya tidak tertinggal di bawah item baru.
                            let wadah = list.querySelector('.divide-y');
                            if (!wadah) {
                                list.innerHTML = '<div class="divide-y divide-outline-variant"></div>';
                                wadah = list.querySelector('.divide-y');
                            }

                            // Server mengirim berurutan dari yang paling lama,
                            // jadi menyisipkan satu per satu di atas menyisakan
                            // yang terbaru di posisi teratas.
                            for (const notif of data.notifications) {
                                // Notifikasi yang sudah dirender server, sudah
                                // masuk lewat poll sebelumnya, atau baru saja
                                // ditandai dibaca tidak perlu ditambah lagi.
                                if (window.statusNotifikasi.sudahDibaca.has(notif.id)) continue;
                                if (wadah.querySelector('[data-notif-id="' + notif.id + '"]')) continue;

                                wadah.prepend(createNotificationElement(notif));
                                ditambahkan++;
                            }

                            // Titik follow up bila ada nudge yang belum dibuka
                            const hasNudge = data.notifications.some(n => n.type === 'pengaduan_nudge_unit');
                            if (hasNudge && !btn.querySelector('.bg-warning')) {
                                const nudgeDot = document.createElement('span');
                                nudgeDot.className = 'absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-warning ring-2 ring-white';
                                nudgeDot.setAttribute('aria-label', 'Ada follow up (nudge) dari admin humas');
                                btn.appendChild(nudgeDot);
                            }
                        }

                        // Masih ada sisa di luar batas satu poll: lanjutkan
                        // segera supaya daftar tidak tertinggal belasan detik.
                        // Tanpa tambahan baru, poll cepat hanya akan mengulang
                        // halaman yang sama, jadi biarkan interval biasa.
                        if (data.sukses && data.has_more && ditambahkan > 0) {
                            setTimeout(pollNotifications, 1000);
                        }
                    } catch (e) {
                        console.warn('Gagal menarik notifikasi:', e);
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
                    const icon = iconMap[notif.type] || iconMap.default;

                    const item = document.createElement('div');
                    item.className = 'p-3 hover:bg-surface-container-low transition-colors cursor-pointer notif-item';
                    item.dataset.notifId = notif.id;
                    item.dataset.notifUrl = notif.url || '';
                    item.dataset.notifType = notif.type;
                    item.role = 'menuitem';
                    item.tabIndex = 0;

                    item.innerHTML = `
                        <div class="flex gap-2">
                            <span class="mt-0.5 text-primary">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">${icon}</span>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="font-label-md text-label-md font-semibold text-on-surface">${escapeHtml(notif.title)}</p>
                                    <span class="text-[11px] text-on-surface-variant whitespace-nowrap shrink-0">${notif.waktu_relatif}</span>
                                </div>
                                <p class="font-body-sm text-body-sm text-on-surface-variant line-clamp-2 mt-0.5">${escapeHtml(notif.message)}</p>
                                ${notif.kode_tiket ? `
                                    <div class="mt-1.5">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-brand-100 text-brand-800 font-mono font-body-xs text-body-xs font-bold">
                                            <span class="material-symbols-outlined text-[10px]" aria-hidden="true">confirmation_number</span>
                                            ${escapeHtml(notif.kode_tiket)}
                                        </span>
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

                // Mulai polling tiap 15 detik
                setInterval(pollNotifications, 15000);
                // Poll perdana 2 detik setelah halaman siap
                setTimeout(pollNotifications, 2000);

                // Tarik juga saat tab kembali terlihat
                document.addEventListener('visibilitychange', function () {
                    if (!document.hidden) {
                        pollNotifications();
                    }
                });
            })();
        </script>
        @endverbatim
    @endif

</body>

</html>
