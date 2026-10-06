<!DOCTYPE html>

<html lang="id" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>@yield('title', 'Buat Aduan') — Sistem Pengaduan RSUD Dr. Soetomo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans bg-brand-light text-ink antialiased">

    <!-- Header -->
    <header class="fixed inset-x-0 top-0 z-20 bg-white shadow-[0_2px_14px_rgba(26,26,46,0.07)]">
        <div class="flex h-20 w-full items-center justify-between gap-6 px-6 sm:px-8">
            <div class="flex items-center gap-3">
                <x-brand-logo class="h-11 w-11" />
                <div class="leading-tight">
                    <p class="text-lg font-extrabold tracking-[-0.2px] text-brand-800">RSUD Dr. Soetomo</p>
                    <p class="text-[10px] font-bold uppercase tracking-[0.6px] text-ink-muted">Sistem Pengaduan Pelayanan</p>
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

            {{-- Menu aktif memakai pill mint dengan teks hijau, menu pasif
                 tetap navy dan hanya mewarna saat disorot. --}}
            <nav class="flex items-center gap-1 overflow-x-auto py-2">

                @foreach ($menu as $item)
                    <a
                        href="{{ $item['url'] }}"
                        @class([
                            'shrink-0 rounded-full px-4 py-2 text-sm font-bold transition',
                            'bg-brand-100 text-brand-800' => $item['aktif'],
                            'text-ink hover:bg-brand-50 hover:text-brand-800' => ! $item['aktif'],
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
    <footer class="relative z-10 w-full bg-white py-10 border-t border-brand-200/60 shadow-[0_-2px_14px_rgba(26,26,46,0.05)]">
        <div class="flex w-full flex-col gap-10 px-8">
            <div class="grid grid-cols-4 gap-6">
                <div>
                    <div class="flex items-center gap-1">
                        <x-brand-logo class="h-10 w-10" />
                        <p class="whitespace-nowrap text-base font-bold leading-[22px] text-brand-800">RSUD Dr. Soetomo</p>
                    </div>
                    <p class="mt-8 max-w-[264px] text-[13px] leading-[21px] text-ink-muted">Rumah Sakit Umum Daerah kelas A milik Pemerintah Provinsi Jawa Timur yang beroperasi sebagai Badan Layanan Umum Daerah (BLUD) di Kota Surabaya.</p>

                    <!-- Lencana akreditasi memakai emas karena termasuk pencapaian,
                         bukan informasi biasa. -->
                    <p class="mt-6 inline-flex items-center gap-2 rounded-full bg-gold-100 border border-gold-300 px-3 py-[4px] text-[11px] font-bold tracking-[0.33px] text-gold-800">
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

            <div class="flex items-center justify-between gap-[260px] border-t border-brand-200/60 pt-4">
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

    <!-- Tombol Aksesibilitas -->
    <div class="fixed bottom-6 left-6 z-30 flex flex-col items-start gap-3">
        <button
            type="button"
            id="tombol-akses"
            aria-expanded="false"
            aria-controls="panel-akses"
            class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-800 text-white shadow-[0_8px_24px_rgba(15,122,60,0.35)] transition hover:bg-brand-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
        >
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="10" cy="4" r="2" fill="currentColor" stroke="none"/>
                <path d="M10 7v6h5l3 5"/>
                <path d="M15 13a5.5 5.5 0 1 1-6-4.6"/>
            </svg>
            <span class="sr-only">Buka pengaturan aksesibilitas</span>
        </button>

        <div id="panel-akses" class="hidden w-60 rounded-2xl border border-brand-200 bg-white p-4 shadow-[0_14px_36px_rgba(26,26,46,0.16)]">
            <p class="text-xs font-extrabold uppercase tracking-[0.6px] text-brand-800">Aksesibilitas</p>
            <div class="mt-3 flex flex-col gap-2">
                <button type="button" data-akses="perbesar" class="rounded-xl bg-brand-light px-3 py-2 text-left text-sm font-semibold text-ink transition hover:bg-brand-100 hover:text-brand-800">Perbesar teks</button>
                <button type="button" data-akses="kecilkan" class="rounded-xl bg-brand-light px-3 py-2 text-left text-sm font-semibold text-ink transition hover:bg-brand-100 hover:text-brand-800">Perkecil teks</button>
                <button type="button" data-akses="reset" class="rounded-xl bg-brand-light px-3 py-2 text-left text-sm font-semibold text-ink transition hover:bg-brand-100 hover:text-brand-800">Kembalikan ukuran</button>
                <button type="button" data-akses="kontras" aria-pressed="false" class="rounded-xl bg-brand-light px-3 py-2 text-left text-sm font-semibold text-ink transition hover:bg-brand-100 hover:text-brand-800">Kontras tinggi</button>
            </div>
        </div>
    </div>
    <!-- End of Tombol Aksesibilitas -->

    <!-- Widget Pilih Suara -->
    <div class="fixed bottom-6 right-6 z-30 flex flex-col items-end gap-3">
        <div id="panel-suara" class="hidden w-64 rounded-2xl border border-brand-200 bg-white p-4 shadow-[0_14px_36px_rgba(26,26,46,0.16)]">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-extrabold uppercase tracking-[0.6px] text-brand-800">Pilih Suara</p>

                <!-- Bendera digambar sebagai SVG supaya tidak bergantung pada
                     berkas gambar atau emoji yang bisa tampil beda tiap sistem. -->
                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-light px-2 py-0.5 text-[10px] font-bold text-ink-muted">
                    <svg class="h-3 w-4 rounded-[2px] ring-1 ring-black/10" viewBox="0 0 30 20" aria-hidden="true">
                        <rect width="30" height="10" fill="#CE1126"/>
                        <rect y="10" width="30" height="10" fill="#FFFFFF"/>
                    </svg>
                    ID
                </span>
            </div>

            <label for="suara-bahasa" class="mt-3 block text-[11px] font-bold uppercase tracking-[0.5px] text-ink-muted">Bahasa</label>
            <select id="suara-bahasa" class="mt-1 w-full rounded-xl border border-brand-200 bg-brand-light px-3 py-2 text-sm font-semibold text-ink outline-none focus:ring-2 focus:ring-brand-600">
                <option value="id-ID">Bahasa Indonesia</option>
                <option value="en-US">English</option>
            </select>

            <button
                type="button"
                id="suara-main"
                class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl bg-brand-800 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-700"
            >
                Putar Suara
            </button>
            <p id="suara-status" class="mt-2 text-[11px] leading-4 text-ink-muted">Membacakan isi halaman yang sedang dibuka.</p>
        </div>

        <button
            type="button"
            id="tombol-suara"
            aria-expanded="false"
            aria-controls="panel-suara"
            class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-800 text-white shadow-[0_8px_24px_rgba(15,122,60,0.35)] transition hover:bg-brand-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
        >
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M11 5 6 9H3v6h3l5 4V5Z"/>
                <path d="M15.5 8.5a5 5 0 0 1 0 7"/>
                <path d="M18.5 5.5a9 9 0 0 1 0 13"/>
            </svg>
            <span class="sr-only">Buka pemilih suara</span>
        </button>
    </div>
    <!-- End of Widget Pilih Suara -->

    <script>
        // Kedua panel mengandalkan kelas `hidden`, jadi tombolnya cukup
        // membalik status tersebut tanpa menyimpan state terpisah.
        function pasangPanel(tombolId, panelId) {
            const tombol = document.getElementById(tombolId);
            const panel = document.getElementById(panelId);

            if (! tombol || ! panel) {
                return;
            }

            tombol.addEventListener('click', function () {
                const terbuka = panel.classList.toggle('hidden') === false;
                tombol.setAttribute('aria-expanded', String(terbuka));
            });
        }

        pasangPanel('tombol-akses', 'panel-akses');
        pasangPanel('tombol-suara', 'panel-suara');

        // --- Pengaturan aksesibilitas ---
        const SKALA = [0.9, 1, 1.15, 1.3];
        let tingkatSkala = Number(localStorage.getItem('skala-teks') ?? 1);

        function terapkanSkala() {
            document.documentElement.style.fontSize = (SKALA[tingkatSkala] * 100) + '%';
            localStorage.setItem('skala-teks', String(tingkatSkala));
        }

        if (localStorage.getItem('kontras-tinggi') === '1') {
            document.documentElement.classList.add('kontras-tinggi');
        }

        terapkanSkala();

        document.querySelectorAll('[data-akses]').forEach(function (tombol) {
            tombol.addEventListener('click', function () {
                const perintah = tombol.dataset.akses;

                if (perintah === 'perbesar') {
                    tingkatSkala = Math.min(tingkatSkala + 1, SKALA.length - 1);
                    terapkanSkala();
                } else if (perintah === 'kecilkan') {
                    tingkatSkala = Math.max(tingkatSkala - 1, 0);
                    terapkanSkala();
                } else if (perintah === 'reset') {
                    tingkatSkala = 1;
                    terapkanSkala();
                } else if (perintah === 'kontras') {
                    const aktif = document.documentElement.classList.toggle('kontras-tinggi');
                    tombol.setAttribute('aria-pressed', String(aktif));
                    localStorage.setItem('kontras-tinggi', aktif ? '1' : '0');
                }
            });
        });

        // --- Pemutar suara (Web Speech API) ---
        const suaraUtama = document.getElementById('suara-main');
        const suaraBahasa = document.getElementById('suara-bahasa');
        const suaraStatus = document.getElementById('suara-status');

        if (suaraUtama) {
            suaraUtama.addEventListener('click', function () {
                if (! ('speechSynthesis' in window)) {
                    suaraStatus.textContent = 'Peramban ini belum mendukung pembaca suara.';
                    return;
                }

                if (window.speechSynthesis.speaking) {
                    window.speechSynthesis.cancel();
                    suaraUtama.textContent = 'Putar Suara';
                    suaraStatus.textContent = 'Dihentikan.';
                    return;
                }

                const isi = document.querySelector('main');
                const teks = isi ? isi.innerText.replace(/\s+/g, ' ').trim() : '';

                if (! teks) {
                    suaraStatus.textContent = 'Tidak ada isi halaman untuk dibacakan.';
                    return;
                }

                const ucap = new SpeechSynthesisUtterance(teks.slice(0, 4000));
                ucap.lang = suaraBahasa.value;
                ucap.rate = 1;
                ucap.onend = function () {
                    suaraUtama.textContent = 'Putar Suara';
                    suaraStatus.textContent = 'Selesai dibacakan.';
                };

                window.speechSynthesis.speak(ucap);
                suaraUtama.textContent = 'Berhenti';
                suaraStatus.textContent = 'Sedang membacakan isi halaman...';
            });
        }
    </script>

    @stack('scripts')

</body>

</html>
