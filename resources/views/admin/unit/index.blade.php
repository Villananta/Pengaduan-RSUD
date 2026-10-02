@extends('admin.layout')

@section('title', 'Master Data Unit & Instalasi')

@section('content')

    <div class="w-full px-margin py-space-lg flex flex-col gap-space-lg">

        <!-- Pesan Hasil Tindakan -->
        @if (session('sukses'))
            <div
                role="status"
                class="w-full bg-secondary-container text-on-secondary-container rounded-lg px-space-md py-space-sm flex items-start gap-space-sm"
            >
                <x-symbol nama="check_circle" class="text-[20px] shrink-0" />
                <span class="text-body-sm">{{ session('sukses') }}</span>
            </div>
        @endif

        <!-- Strip Konteks: posisi halaman dan pintasan master unit. -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md bg-surface-container-lowest p-space-lg rounded-lg shadow-sm">
            <div class="flex items-start gap-space-md">
                <span class="w-12 h-12 rounded-lg bg-primary-container text-on-primary flex items-center justify-center shadow-sm shrink-0">
                    <x-symbol nama="domain" class="text-[26px]" />
                </span>

                <div class="flex flex-col">
                    <div class="flex items-center gap-space-sm flex-wrap">
                        <span class="font-label-sm text-label-sm uppercase tracking-wider font-bold bg-primary-container/60 text-on-primary px-space-xs py-0.5 rounded-xl">
                            Master Data &amp; Integrasi SIMRS
                        </span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-secondary" aria-hidden="true"></span>
                            Sumber Tunggal Tujuan Disposisi
                        </span>
                    </div>

                    <h1 class="font-headline-md text-headline-md text-on-surface font-bold tracking-tight mt-0.5">
                        Master Data Unit &amp; Instalasi
                    </h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        Daftar resmi unit yang dihubungi ketika pengaduan perlu telaah internal. Semua pengaduan hanya bisa ditugaskan ke unit di bawah ini.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-space-sm flex-wrap self-end lg:self-center">
                {{-- Sinkronisasi struktur unit dan kirim undangan PIC butuh
                     layanan luar yang belum ada di aplikasi ini, jadi
                     keduanya ditampilkan nonaktif, bukan sebagai tautan. --}}
                <span
                    class="px-space-md py-2 rounded-lg bg-surface-container text-outline font-label-lg text-label-lg cursor-not-allowed select-none flex items-center gap-1.5"
                    title="Sinkronisasi struktur unit dari SIMRS belum punya endpoint, jalankan dari mesin pengingat di luar aplikasi"
                >
                    <x-symbol nama="sync" class="text-[18px]" />
                    Sinkronkan SIMRS
                </span>

                <span
                    class="px-space-md py-2 rounded-lg bg-surface-container text-outline font-label-lg text-label-lg cursor-not-allowed select-none flex items-center gap-1.5"
                    title="Undangan aktivasi PIC butuh akun unit dan notifikasi yang belum dibangun, status dicatat manual dari form unit"
                >
                    <x-symbol nama="mark_email_unread" class="text-[18px]" />
                    Kirim Undangan
                </span>

                <a
                    href="{{ route('admin.unit.create') }}"
                    class="px-space-md py-2 rounded-lg bg-primary text-on-primary font-label-lg text-label-lg hover:bg-primary/90 transition-colors flex items-center gap-1.5 shadow-sm"
                >
                    <x-symbol nama="add" class="text-[18px]" />
                    Tambah Unit
                </a>
            </div>
        </div>
        <!-- End of Strip Konteks -->

        <!-- Empat Kartu Metrik: kondisi unit, koneksi, antrean, dan akses akun. -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-space-md">
            @foreach ($daftar->kartu as $k)
                <div class="bg-surface-container-lowest p-space-md rounded-lg shadow-sm flex flex-col justify-between gap-space-sm">
                    <div class="flex items-center justify-between gap-space-sm">
                        <span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">{{ $k['label'] }}</span>
                        <span class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center shrink-0">
                            <x-symbol :nama="$k['ikon']" class="text-[18px] {{ $k['nadaAngka'] }}" />
                        </span>
                    </div>

                    <div class="flex items-baseline gap-space-xs">
                        <span class="font-display-lg text-display-lg font-bold leading-none {{ $k['nadaAngka'] }}">
                            {{ number_format($k['nilai'], 0, ',', '.') }}
                        </span>
                        <span class="font-title-sm text-title-sm text-on-surface-variant font-medium">{{ $k['satuan'] }}</span>
                    </div>

                    <div class="w-full bg-surface-container rounded-full h-1.5 overflow-hidden">
                        <div class="{{ $k['nadaBar'] }} h-full rounded-full" style="width: {{ $k['persen'] }}%"></div>
                    </div>

                    <div class="flex flex-col">
                        <span class="font-label-sm text-label-sm font-semibold text-on-surface-variant">{{ $k['sisi'] }}</span>
                        <span class="font-body-sm text-body-sm text-outline mt-1">{{ $k['ket'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
        <!-- End of Empat Kartu Metrik -->

        <!-- Sebaran Kategori Layanan: jauhkan perhatian ke unit yang paling banyak. -->
        <div class="bg-surface-container-lowest p-space-lg rounded-lg shadow-sm flex flex-col gap-space-md">
            <div class="flex items-center justify-between gap-space-sm flex-wrap">
                <div class="flex items-center gap-space-xs">
                    <x-symbol nama="category" class="text-primary-container text-[24px]" />
                    <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">Sebaran Unit per Kategori Layanan</h2>
                </div>

                <span class="font-label-sm text-label-sm text-outline">Kategori menentukan unit mana yang pantas ditugaskan sebuah pengaduan</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-space-md">
                @foreach ($daftar->distribusi as $baris)
                    <div class="flex flex-col gap-1.5 p-space-sm rounded-lg bg-surface-container-low">
                        <div class="flex items-center justify-between gap-space-sm">
                            <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm font-bold {{ $baris['kategori']->badge() }}">
                                {{ $baris['kategori']->label() }}
                            </span>
                            <span class="font-title-sm text-title-sm font-bold text-on-surface">{{ number_format($baris['jumlah'], 0, ',', '.') }}</span>
                        </div>

                        <div class="w-full bg-surface-container rounded-full h-2 overflow-hidden">
                            <div class="bg-primary h-full rounded-full transition-all duration-500" style="width: {{ $baris['persen'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <!-- End of Sebaran Kategori Layanan -->

        <!-- Panel Tabel Unit: pencarian, saring, dan daftar unit. -->
        <div class="bg-surface-container-lowest p-space-lg rounded-lg shadow-sm flex flex-col gap-space-md">
            <div class="flex items-center justify-between gap-space-sm flex-wrap">
                <div class="flex items-center gap-space-xs">
                    <x-symbol nama="table_view" class="text-secondary text-[24px]" />
                    <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">Daftar Unit Terdaftar</h2>
                </div>

                <span class="font-label-sm text-label-sm text-on-surface-variant bg-surface-container px-space-sm py-1 rounded-xl">
                    Menampilkan {{ number_format($daftar->unit->total(), 0, ',', '.') }} dari {{ number_format($daftar->unit->total(), 0, ',', '.') }} unit
                </span>
            </div>

            <!-- Formulir Saring: semua kolom ikut terkirim saat satu saring diubah. -->
            <form method="GET" action="{{ route('admin.unit.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-space-sm pt-space-sm">

                <div class="md:col-span-4 relative flex items-center">
                    <x-symbol nama="search" class="absolute left-3 text-outline text-[20px]" />
                    <input
                        type="search"
                        name="q"
                        value="{{ $daftar->saring['q'] }}"
                        class="w-full pl-10 pr-4 py-2.5 rounded-sm bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        placeholder="Cari nama unit, kode, PIC, atau ekstensi..."
                    >
                </div>

                <div class="md:col-span-2 relative flex items-center">
                    <x-symbol nama="category" class="absolute left-3 text-outline text-[18px] pointer-events-none" />
                    <select
                        name="kategori"
                        class="w-full pl-9 pr-8 py-2.5 rounded-sm bg-surface-container-low text-on-surface font-body-sm text-body-sm appearance-none focus:outline-none focus:ring-2 focus:ring-secondary/40"
                    >
                        @foreach ($pilihan['kategori'] as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($daftar->saring['kategori'] === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-symbol nama="expand_more" class="absolute right-3 text-outline text-[18px] pointer-events-none" />
                </div>

                <div class="md:col-span-2 relative flex items-center">
                    <x-symbol nama="cloud_off" class="absolute left-3 text-outline text-[18px] pointer-events-none" />
                    <select
                        name="koneksi"
                        class="w-full pl-9 pr-8 py-2.5 rounded-sm bg-surface-container-low text-on-surface font-body-sm text-body-sm appearance-none focus:outline-none focus:ring-2 focus:ring-secondary/40"
                    >
                        @foreach ($pilihan['koneksi'] as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($daftar->saring['koneksi'] === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-symbol nama="expand_more" class="absolute right-3 text-outline text-[18px] pointer-events-none" />
                </div>

                <div class="md:col-span-2 relative flex items-center">
                    <x-symbol nama="badge" class="absolute left-3 text-outline text-[18px] pointer-events-none" />
                    <select
                        name="akses"
                        class="w-full pl-9 pr-8 py-2.5 rounded-sm bg-surface-container-low text-on-surface font-body-sm text-body-sm appearance-none focus:outline-none focus:ring-2 focus:ring-secondary/40"
                    >
                        @foreach ($pilihan['akses'] as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($daftar->saring['akses'] === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-symbol nama="expand_more" class="absolute right-3 text-outline text-[18px] pointer-events-none" />
                </div>

                <div class="md:col-span-2 relative flex items-center">
                    <x-symbol nama="sort" class="absolute left-3 text-outline text-[18px] pointer-events-none" />
                    <select
                        name="urutan"
                        class="w-full pl-9 pr-8 py-2.5 rounded-sm bg-surface-container-low text-on-surface font-body-sm text-body-sm appearance-none focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        title="Urutkan daftar unit"
                    >
                        @foreach ($pilihan['urutan'] as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($daftar->saring['urutan'] === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-symbol nama="expand_more" class="absolute right-3 text-outline text-[18px] pointer-events-none" />
                </div>

                {{-- Pilihan "punya tiket aktif" dibuat sebagai tautan, bukan select,
                     karena sering dipakai sebagai pintasan saat menelusuri
                     unit yang antreannya menumpuk. --}}
                <div class="md:col-span-12 flex items-center gap-space-sm flex-wrap">
                    <span class="font-label-sm text-label-sm text-outline">Beban antrean:</span>

                    <a
                        href="{{ $daftar->urlSaring('beban', 'ada') }}"
                        class="px-space-sm py-1 rounded-lg font-label-sm text-label-sm font-semibold transition-colors {{ $daftar->saring['beban'] === 'ada' ? 'bg-primary-container text-on-primary' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' }}"
                    >
                        Punya Tiket Aktif
                    </a>

                    <a
                        href="{{ $daftar->urlSaring('beban', 'kosong') }}"
                        class="px-space-sm py-1 rounded-lg font-label-sm text-label-sm font-semibold transition-colors {{ $daftar->saring['beban'] === 'kosong' ? 'bg-primary-container text-on-primary' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' }}"
                    >
                        Antrian Kosong
                    </a>

                    @if ($daftar->adaSaring())
                        <a
                            href="{{ route('admin.unit.index') }}"
                            class="px-space-sm py-1 rounded-lg font-label-sm text-label-sm font-semibold bg-error-container text-on-error-container flex items-center gap-1"
                        >
                            <x-symbol nama="close" class="text-[14px]" />
                            Reset Saring
                        </a>
                    @endif

                    <button
                        type="submit"
                        class="ml-auto px-space-md py-2 rounded-lg bg-secondary text-on-secondary font-label-lg text-label-lg hover:bg-secondary/90 transition-colors flex items-center gap-1.5"
                    >
                        <x-symbol nama="filter_alt" class="text-[18px]" />
                        Terapkan
                    </button>
                </div>
            </form>
            <!-- End of Formulir Saring -->

            @if ($daftar->unit->isEmpty())
                <p class="font-body-md text-body-md text-on-surface-variant py-space-lg text-center">
                    Tidak ada unit yang cocok dengan saring ini.
                </p>
            @else
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-left font-body-sm text-body-sm border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low text-on-surface font-label-md text-label-md">
                                <th class="py-3 px-space-md rounded-lg">Unit / Instalasi</th>
                                <th class="py-3 px-space-md">Kategori</th>
                                <th class="py-3 px-space-md text-center">Beban Aktif</th>
                                <th class="py-3 px-space-md text-center">Kepatuhan SLA</th>
                                <th class="py-3 px-space-md">Koneksi SIMRS</th>
                                <th class="py-3 px-space-md">Akses PIC</th>
                                <th class="py-3 px-space-md text-right rounded-lg">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container">
                            @foreach ($daftar->unit as $unit)
                                @php
                                    $beban = $daftar->beban($unit);
                                    $lewat = $daftar->lewat($unit);
                                    $kepatuhan = $daftar->kepatuhan($unit);
                                @endphp

                                <tr class="transition-colors hover:bg-surface-container/60">
                                    <td class="py-space-md px-space-md min-w-[220px]">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-title-sm text-title-sm font-bold text-on-surface">{{ $unit->nama }}</span>

                                            @unless ($unit->aktif)
                                                <span class="px-2 py-0.5 rounded-full bg-surface-container-high text-outline font-label-sm text-label-sm font-bold">
                                                    Nonaktif
                                                </span>
                                            @endunless
                                        </div>

                                        <span class="font-label-sm text-label-sm text-on-surface-variant">{{ $unit->kode }}</span>

                                        <div class="flex flex-col mt-1">
                                            <span class="font-body-sm text-body-sm text-on-surface-variant">
                                                {{ $unit->pic ?? 'PIC belum ditetapkan' }}
                                            </span>
                                            <span class="font-label-sm text-label-sm text-outline">
                                                {{ $unit->jabatan_pic ?? 'Jabatan PIC belum diisi' }}
                                                @if ($unit->ekstensi)
                                                    &middot; Ekstensi {{ $unit->ekstensi }}
                                                @endif
                                            </span>
                                        </div>
                                    </td>

                                    <td class="py-space-md px-space-md">
                                        <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm font-bold {{ $unit->kategori->badge() }}">
                                            {{ $unit->kategori->label() }}
                                        </span>

                                        <span class="block mt-1 font-label-sm text-label-sm text-outline">
                                            {{ $unit->jam_layanan ?? 'Jam layanan belum diisi' }}
                                        </span>
                                    </td>

                                    <td class="py-space-md px-space-md text-center">
                                        <span class="font-display-sm text-display-sm font-bold {{ $beban > 0 ? 'text-on-surface' : 'text-outline' }}">
                                            {{ number_format($beban, 0, ',', '.') }}
                                        </span>

                                        @if ($lewat > 0)
                                            <span class="mt-1 block px-2 py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm text-label-sm font-bold">
                                                {{ $lewat }} lewat batas
                                            </span>
                                        @endif
                                    </td>

                                    <td class="py-space-md px-space-md text-center">
                                        @if ($kepatuhan === null)
                                            <span class="font-label-sm text-label-sm text-outline">Belum ada data</span>
                                        @else
                                            <span class="font-title-sm text-title-sm font-bold {{ $kepatuhan >= $daftar->standarKepatuhan() ? 'text-secondary' : 'text-error' }}">
                                                {{ number_format($kepatuhan, 1, ',', '.') }}%
                                            </span>

                                            <div class="w-full min-w-[64px] bg-surface-container rounded-full h-1.5 overflow-hidden mt-1">
                                                <div class="{{ $kepatuhan >= $daftar->standarKepatuhan() ? 'bg-secondary' : 'bg-error' }} h-full rounded-full" style="width: {{ min(100, $kepatuhan) }}%"></div>
                                            </div>
                                        @endif
                                    </td>

                                    <td class="py-space-md px-space-md min-w-[160px]">
                                        @if ($unit->koneksiAktif())
                                            <span class="px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-container font-label-sm text-label-sm font-bold flex items-center gap-1 w-fit">
                                                <x-symbol nama="cloud_done" class="text-[14px]" />
                                                Aktif
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm text-label-sm font-bold flex items-center gap-1 w-fit">
                                                <x-symbol nama="cloud_off" class="text-[14px]" />
                                                Terputus
                                            </span>
                                        @endif

                                        @if ($unit->disposisi)
                                            <span class="block mt-1 font-label-sm text-label-sm text-outline">{{ $unit->disposisi->ringkas() }}</span>
                                        @else
                                            <span class="block mt-1 font-label-sm text-label-sm text-outline">Belum pernah ditugaskan tiket</span>
                                        @endif
                                    </td>

                                    <td class="py-space-md px-space-md">
                                        <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm font-bold flex items-center gap-1 w-fit {{ $unit->status_akses->badge() }}">
                                            <x-symbol :nama="$unit->status_akses->ikon()" class="text-[14px]" />
                                            {{ $unit->status_akses->label() }}
                                        </span>
                                    </td>

                                    <td class="py-space-md px-space-md text-right">
                                        <div class="flex flex-col items-end gap-1.5">
                                            <a
                                                href="{{ route('admin.pengaduan.index', ['unit' => $unit->kode]) }}"
                                                class="px-space-sm py-1.5 rounded-lg bg-surface-container text-on-surface font-label-sm text-label-sm font-bold hover:bg-surface-container-high transition-colors inline-flex items-center gap-1 whitespace-nowrap"
                                                title="Lihat pengaduan aktif yang ditugaskan ke {{ $unit->nama }}"
                                            >
                                                <x-symbol nama="assignment" class="text-[16px]" />
                                                Beban Tiket
                                            </a>

                                            <a
                                                href="{{ route('admin.unit.edit', ['unit' => $unit->kode]) }}"
                                                class="px-space-sm py-1.5 rounded-lg bg-primary-container text-on-primary font-label-sm text-label-sm font-bold hover:bg-primary/85 transition-colors inline-flex items-center gap-1 whitespace-nowrap"
                                                title="Ubah data unit"
                                            >
                                                <x-symbol nama="edit" class="text-[16px]" />
                                                Edit Unit
                                            </a>

                                            <form method="POST" action="{{ route('admin.unit.status', ['unit' => $unit->kode]) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="px-space-sm py-1.5 rounded-lg font-label-sm text-label-sm font-bold transition-colors inline-flex items-center gap-1 whitespace-nowrap {{ $unit->aktif ? 'bg-surface-container-high text-outline hover:bg-error-container hover:text-on-error-container' : 'bg-secondary-container text-on-secondary-container hover:bg-secondary/85' }}"
                                                    title="{{ $unit->aktif ? 'Nonaktifkan unit ini supaya tidak bisa dipilih lagi' : 'Aktifkan kembali unit ini' }}"
                                                >
                                                    <x-symbol :nama="$unit->aktif ? 'toggle_off' : 'toggle_on'" class="text-[16px]" />
                                                    {{ $unit->aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="flex items-center justify-between gap-space-sm flex-wrap">
                <span class="font-body-sm text-body-sm text-outline">
                    Halaman {{ $daftar->unit->currentPage() }} dari {{ max(1, $daftar->unit->lastPage()) }}.
                </span>

                {{ $daftar->unit->onEachSide(1)->links() }}
            </div>
        </div>
        <!-- End of Panel Tabel Unit -->

    </div>

@endsection