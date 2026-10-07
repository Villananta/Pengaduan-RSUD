@extends('unit.layout')

@section('title', 'Daftar Disposisi Masuk '.$unit->nama)

@use('App\Enums\KategoriPengaduan')
@use('App\Enums\StatusInvestigasi')
@use('App\Support\DaftarPengaduan')

@section('content')

    @php
        // Paginasi, angka ringkasan, dan jendela halaman dipecah di sini
        // supaya isi tabel tetap terbaca sebagai data, bukan perhitungan.
        $tabel = $daftar->daftar;
        $ringkas = $daftar->ringkas;

        $halamanSekarang = $tabel->currentPage();
        $halamanTerakhir = $tabel->lastPage();
        $halamanMulai = max(1, $halamanSekarang - 2);
        $halamanAkhir = min($halamanTerakhir, $halamanMulai + 4);
        $halamanMulai = max(1, $halamanAkhir - 4);

        $filterAktif = array_filter([
            'investigasi' => $daftar->investigasi?->value,
            'kategori' => $daftar->kategori?->value,
            'q' => $daftar->cari,
        ]);

        // Ditangani langsung humas berarti tiket tidak pernah sampai ke unit
        // ini, jadi tab-nya sengaja tidak ditawarkan di halaman disposisi unit.
        $tabUnit = array_filter(
            StatusInvestigasi::cases(),
            fn (StatusInvestigasi $status): bool => $status !== StatusInvestigasi::LangsungHumas,
        );
    @endphp

    <div class="w-full px-margin py-space-lg flex flex-col gap-space-lg">

        <!-- Strip Konteks: identitas unit pemilik daftar ini. -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-space-md">
            <div class="flex flex-col">
                <div class="flex items-center gap-space-xs text-secondary font-label-md text-label-md uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-secondary animate-ping" aria-hidden="true"></span>
                    Disposisi Masuk &bull; {{ $unit->kategori->label() }}
                </div>

                <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight mt-0.5">
                    Daftar Disposisi Masuk &mdash; {{ $unit->namaLengkap() }}
                </h1>

                <p class="font-body-md text-body-md text-on-surface-variant max-w-4xl">
                    Pengaduan yang ditugaskan ke unit ini saja, diurutkan dari yang belum ditutup
                    lalu yang terbaru. Batas telaah unit {{ $ringkas['hari_investigasi'] }} hari kerja,
                    terpisah dari SLA {{ \App\Support\Sla::hariKerja() }} hari kerja yang dibaca pelapor.
                </p>
            </div>

            <a
                href="{{ route('unit.dashboard', $unit) }}"
                class="flex items-center gap-1.5 px-3 py-2 rounded-sm border border-outline-variant bg-surface-container-low text-on-surface font-label-md text-label-md hover:bg-surface-container transition-colors shrink-0"
            >
                <x-symbol nama="arrow_back" class="text-[16px]" />
                Beranda Unit
            </a>
        </div>
        <!-- End of Strip Konteks -->

        <!-- Ringkasan Angka Disposisi -->
        <div class="w-full rounded-lg bg-surface-container-low p-space-md shadow-sm grid grid-cols-2 lg:grid-cols-5 gap-space-md">
            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider font-bold">Total Disposisi</span>
                <span class="font-headline-sm text-headline-sm font-bold text-on-surface mt-0.5">
                    {{ number_format($ringkas['total'], 0, ',', '.') }}
                </span>
            </div>

            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider font-bold">Masih Aktif</span>
                <span class="font-headline-sm text-headline-sm font-bold text-on-surface mt-0.5">
                    {{ number_format($ringkas['aktif'], 0, ',', '.') }}
                </span>
            </div>

            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider font-bold">Lewat Batas SLA</span>
                <span class="font-headline-sm text-headline-sm font-bold {{ $ringkas['terlambat'] > 0 ? 'text-error' : 'text-on-surface' }} mt-0.5">
                    {{ number_format($ringkas['terlambat'], 0, ',', '.') }}
                </span>
            </div>

            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider font-bold">Mendek Batas SLA</span>
                <span class="font-headline-sm text-headline-sm font-bold {{ $ringkas['mendek'] > 0 ? 'text-on-tertiary-container' : 'text-on-surface' }} mt-0.5">
                    {{ number_format($ringkas['mendek'], 0, ',', '.') }}
                </span>
            </div>

            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider font-bold">Sudah Tuntas</span>
                <span class="font-headline-sm text-headline-sm font-bold text-secondary mt-0.5">
                    {{ number_format($ringkas['selesai'], 0, ',', '.') }}
                </span>
            </div>
        </div>
        <!-- End of Ringkasan Angka Disposisi -->

        <!-- Tab Status Investigasi -->
        <div class="flex flex-col gap-space-sm">
            <div class="flex flex-wrap items-center gap-space-xs">
                <a
                    href="{{ $daftar->urlTanpa('investigasi') }}"
                    @class([
                        'flex items-center gap-space-xs px-space-md py-2 rounded-full font-label-md text-label-md font-bold transition-colors',
                        'bg-secondary-container text-on-secondary-container' => $daftar->investigasi === null,
                        'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container' => $daftar->investigasi !== null,
                    ])
                >
                    <span class="w-2 h-2 rounded-full bg-secondary" aria-hidden="true"></span>
                    Semua Disposisi
                    <span class="opacity-70">({{ number_format($daftar->jumlahTahap['semua'], 0, ',', '.') }})</span>
                </a>

                @foreach ($tabUnit as $status)

                    <a
                        href="{{ $daftar->urlTanpa('investigasi', $status->value) }}"
                        @class([
                            'flex items-center gap-space-xs px-space-md py-2 rounded-full font-label-md text-label-md font-bold transition-colors',
                            'bg-secondary-container text-on-secondary-container' => $daftar->investigasi === $status,
                            'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container' => $daftar->investigasi !== $status,
                        ])
                    >
                        <span class="w-2 h-2 rounded-full {{ $status->titik() }}" aria-hidden="true"></span>
                        {{ $status->label() }}
                        <span class="opacity-70">({{ number_format($daftar->jumlahInvestigasi[$status->value], 0, ',', '.') }})</span>
                    </a>
                @endforeach
            </div>

            <!-- Pencarian & Kategori -->
            <form method="GET" action="{{ route('unit.disposisi.index', $unit) }}" class="grid grid-cols-1 md:grid-cols-12 gap-space-sm">
                @foreach ($filterAktif as $nama => $nilai)

                    <input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">

                @endforeach

                <div class="md:col-span-6 flex items-center gap-space-xs bg-surface-container-lowest rounded-lg border border-outline-variant/40 px-space-md">
                    <x-symbol nama="search" class="text-[18px] text-outline" />
                    <input
                        type="search"
                        name="q"
                        value="{{ $daftar->cari }}"
                        placeholder="Cari nomor tiket, subjek, pelapor, atau NRM..."
                        class="w-full bg-transparent py-2.5 font-body-md text-body-md text-on-surface outline-none placeholder:text-outline"
                    >
                </div>

                <div class="md:col-span-4 flex items-center gap-space-xs bg-surface-container-lowest rounded-lg border border-outline-variant/40 px-space-md">
                    <x-symbol nama="category" class="text-[18px] text-outline" />
                    <select name="kategori" class="w-full bg-transparent py-2.5 font-body-md text-body-md text-on-surface outline-none">
                        <option value="">Semua Kategori</option>

                        @foreach (KategoriPengaduan::cases() as $kategori)

                            <option value="{{ $kategori->value }}" @selected($daftar->kategori === $kategori)>
                                {{ $kategori->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2 flex items-center gap-space-xs">
                    <button
                        type="submit"
                        class="w-full px-space-md py-2.5 rounded-lg bg-primary text-on-primary font-label-md text-label-md font-bold hover:opacity-90 transition-opacity"
                    >Terapkan</button>
                </div>
            </form>
            <!-- End of Pencarian & Kategori -->
        </div>
        <!-- End of Tab Status Investigasi -->

        <!-- Tabel Disposisi -->
        <div class="w-full rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col gap-space-sm">
            <div class="overflow-x-auto w-full">
                <table class="w-full min-w-[1000px] text-left border-collapse">
                    <caption class="sr-only">
                        Disposisi masuk milik {{ $unit->namaLengkap() }}
                    </caption>

                    <colgroup>
                        <col class="w-[13%]">
                        <col class="w-[17%]">
                        <col class="w-[22%]">
                        <col class="w-[17%]">
                        <col class="w-[17%]">
                        <col class="w-[14%]">
                    </colgroup>

                    <thead>
                        <tr class="bg-surface-container text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider border-b border-outline-variant/20">
                            <th scope="col" class="py-space-sm px-space-md">No. Tiket</th>
                            <th scope="col" class="py-space-sm px-space-md">Pelapor</th>
                            <th scope="col" class="py-space-sm px-space-md">Pokok Keluhan</th>
                            <th scope="col" class="py-space-sm px-space-md">Status &amp; Investigasi</th>
                            <th scope="col" class="py-space-sm px-space-md">Monitoring SLA</th>
                            <th scope="col" class="py-space-sm px-space-md">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-outline-variant/20 text-body-sm text-on-surface">
                        @forelse ($tabel as $pengaduan)

                            @php $baris = $daftar->baris($pengaduan); @endphp

                            <tr @class([
                                'align-top transition-colors',
                                'bg-surface-container-low/50' => $baris['redup'],
                                'hover:bg-surface-container-low' => ! $baris['redup'],
                            ])>
                                <td class="py-space-sm px-space-md align-top">
                                    <div class="flex flex-col gap-1.5">
                                        <span class="font-title-sm text-title-sm font-bold text-on-surface">{{ $baris['kode'] }}</span>

                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-xl font-label-sm text-label-sm font-bold w-fit {{ $baris['prioritas']['nada'] }}">
                                            <x-symbol :nama="$baris['prioritas']['ikon']" class="text-[13px]" />
                                            {{ $baris['prioritas']['label'] }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <div class="flex flex-col gap-1">
                                        <span class="font-title-sm text-title-sm font-bold text-on-surface">{{ $baris['pelapor'] }}</span>

                                        <div class="flex items-center gap-1 text-on-surface-variant font-label-md text-label-md">
                                            <x-symbol nama="badge" class="text-[14px] text-outline" />
                                            <span class="truncate">NRM: <strong class="text-on-surface">{{ $baris['nrm'] }}</strong></span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <div class="flex flex-col gap-1.5">
                                        <p class="font-title-sm text-title-sm font-semibold text-on-surface line-clamp-2 leading-snug">
                                            {{ $baris['subjek'] }}
                                        </p>

                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-xl bg-surface-container text-on-surface-variant font-label-sm text-label-sm font-bold w-fit">
                                            {{ $baris['kategori']->label() }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <div class="flex flex-col gap-1.5">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl {{ $baris['status']->badgeAdmin() }} font-label-md text-label-md font-bold w-fit shadow-xs">
                                            <x-symbol :nama="$baris['status']->ikon()" class="text-[14px]" />
                                            {{ $baris['status']->label() }}
                                        </span>

                                        <span class="font-label-md text-label-md font-bold {{ $baris['investigasi']->nada() }} px-2 py-0.5 rounded-xl w-fit">
                                            {{ $baris['investigasi']->label() }}
                                        </span>

                                        <span class="text-outline font-label-sm text-label-sm leading-snug">
                                            {{ $baris['catatanInvestigasi'] }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex items-start gap-1.5 {{ $baris['sla']['nadaPosisi'] }}">
                                            <x-symbol nama="schedule" class="text-[15px] shrink-0" />
                                            <span class="font-label-md text-label-md font-semibold leading-snug">
                                                {{ $baris['sla']['posisi'] }}
                                            </span>
                                        </div>

                                        <div class="h-1.5 w-full rounded-full bg-surface-container overflow-hidden">
                                            <div
                                                class="h-full rounded-full {{ $baris['sla']['warnaProgres'] }}"
                                                style="width: {{ $baris['sla']['persen'] }}%"
                                            ></div>
                                        </div>

                                        <span class="text-outline font-label-sm text-label-sm leading-snug">
                                            {{ $baris['sla']['ringkas'] }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <div class="flex flex-col gap-1.5">
                                        @foreach ($baris['aksi'] as $aksi)

                                            @if ($aksi['url'] !== null)
                                                <a
                                                    href="{{ $aksi['url'] }}"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-sm {{ $aksi['nada'] }} font-label-sm text-label-sm font-bold"
                                                >
                                                    <x-symbol :nama="$aksi['ikon']" class="text-[14px]" />
                                                    {{ $aksi['label'] }}
                                                </a>
                                            @else
                                                {{-- Tindakan yang belum dibangun tetap tampil sebagai
                                                     penanda pekerjaan, bukan sebagai tautan palsu. --}}
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-sm {{ $aksi['nada'] }} font-label-sm text-label-sm font-bold cursor-not-allowed">
                                                    <x-symbol :nama="$aksi['ikon']" class="text-[14px]" />
                                                    {{ $aksi['label'] }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-space-lg px-space-md text-center text-on-surface-variant">
                                    Belum ada disposisi yang masuk ke unit ini. Tiket baru akan muncul
                                    begitu humas menugaskan pengaduan ke {{ $unit->namaLengkap() }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Footer Tabel: ukuran halaman, ringkasan hasil, dan paginasi. -->
            <div class="bg-surface-container-low px-space-md py-space-sm flex flex-col md:flex-row items-center justify-between gap-space-md rounded-lg">
                <div class="flex items-center gap-space-md text-body-sm text-on-surface-variant flex-wrap">
                    <form method="GET" action="{{ route('unit.disposisi.index', $unit) }}" class="flex items-center gap-2">
                        @foreach ($filterAktif as $nama => $nilai)

                            <input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">

                        @endforeach

                        <label for="per_halaman">Baris per halaman:</label>
                        <select
                            id="per_halaman"
                            name="per_halaman"
                            onchange="this.form.submit()"
                            class="px-2 py-1 rounded-sm bg-surface-container text-on-surface font-label-md text-label-md focus:outline-none"
                        >
                            @foreach (DaftarPengaduan::UKURAN_HALAMAN as $ukuran)
                                <option value="{{ $ukuran }}" @selected($daftar->perHalaman === $ukuran)>{{ $ukuran }}</option>
                            @endforeach
                        </select>
                        <noscript>
                            <button type="submit" class="px-2 py-1 rounded-sm bg-surface-container-high text-on-surface font-label-sm text-label-sm">
                                Terapkan
                            </button>
                        </noscript>
                    </form>

                    <span>
                        Menampilkan <strong>{{ $tabel->firstItem() ?? 0 }} &ndash; {{ $tabel->lastItem() ?? 0 }}</strong>
                        dari <strong>{{ number_format($ringkas['total'], 0, ',', '.') }}</strong> disposisi
                    </span>
                </div>

                @if ($halamanTerakhir > 1)
                    <nav class="flex items-center gap-1" aria-label="Navigasi halaman daftar disposisi">
                        @if ($tabel->onFirstPage())
                            <span class="p-1.5 rounded-sm bg-surface-container text-outline opacity-40" aria-hidden="true">
                                <x-symbol nama="chevron_left" class="text-[20px]" />
                            </span>
                        @else
                            <a href="{{ $tabel->previousPageUrl() }}" class="p-1.5 rounded-sm bg-surface-container text-outline hover:text-on-surface transition-colors" rel="prev" aria-label="Halaman sebelumnya">
                                <x-symbol nama="chevron_left" class="text-[20px]" />
                            </a>
                        @endif

                        @for ($halaman = $halamanMulai; $halaman <= $halamanAkhir; $halaman++)
                            @if ($halaman === $halamanSekarang)
                                <span
                                    class="w-8 h-8 rounded-sm bg-primary-container text-on-primary font-label-md text-label-md font-bold flex items-center justify-center"
                                    aria-current="page"
                                >{{ $halaman }}</span>
                            @else
                                <a
                                    href="{{ $tabel->url($halaman) }}"
                                    class="w-8 h-8 rounded-sm bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-medium flex items-center justify-center transition-colors"
                                >{{ $halaman }}</a>
                            @endif
                        @endfor

                        @if ($tabel->hasMorePages())
                            <span class="px-1 text-outline" aria-hidden="true">...</span>
                        @endif

                        @if ($tabel->hasMorePages())
                            <a href="{{ $tabel->nextPageUrl() }}" class="p-1.5 rounded-sm bg-surface-container text-outline hover:text-on-surface transition-colors" rel="next" aria-label="Halaman berikutnya">
                                <x-symbol nama="chevron_right" class="text-[20px]" />
                            </a>
                        @else
                            <span class="p-1.5 rounded-sm bg-surface-container text-outline opacity-40" aria-hidden="true">
                                <x-symbol nama="chevron_right" class="text-[20px]" />
                            </span>
                        @endif
                    </nav>
                @endif
            </div>
            <!-- End of Footer Tabel -->
        </div>
        <!-- End of Tabel Disposisi -->

    </div>
@endsection
