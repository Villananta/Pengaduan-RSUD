@extends('admin.layout')

@section('title', 'Daftar Pengaduan')

@use('App\Enums\KategoriPengaduan')
@use('App\Enums\StatusInvestigasi')
@use('App\Enums\StatusPengaduan')
@use('App\Support\DaftarPengaduan')
@use('Illuminate\Support\Str')

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

        // Filter aktif ikut dibawa setiap kali form dikirim ulang.
        $filterAktif = array_filter([
            'status' => $daftar->status?->value,
            'investigasi' => $daftar->investigasi?->value,
            'unit' => $daftar->unit?->kode,
            'kategori' => $daftar->kategori?->value,
            'q' => $daftar->cari,
        ]);
    @endphp

    <div class="w-full px-margin py-space-md flex flex-col gap-space-lg">

        <!-- Strip Konteks: posisi halaman, aturan status, dan metrik cepat. -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-space-md">
            <div class="flex flex-col">
                <div class="flex items-center gap-space-xs text-secondary font-label-md text-label-md uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-secondary animate-ping" aria-hidden="true"></span>
                    Dual Synchronization Gateway &bull; Humas &harr; Unit Pelayanan
                </div>

                <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight mt-0.5">
                    Daftar Pengaduan Pasien &amp; Disposisi Medis
                </h1>

                <p class="font-body-md text-body-md text-on-surface-variant max-w-4xl">
                    Monitoring real-time investigasi {{ $ringkas['unit_total'] }} instalasi RSUD Dr. Soetomo
                    dengan SLA Permenkes RI No. 4/2018.
                    <span class="inline-flex items-center gap-1 font-label-sm text-label-sm bg-surface-container px-2 py-0.5 rounded text-secondary font-semibold ml-1 align-middle">
                        <x-symbol nama="sync_alt" class="text-[14px]" />
                        Aturan: status utama berubah menjadi &ldquo;Diproses&rdquo; tepat saat disposisi diteruskan ke unit
                    </span>
                </p>
            </div>

            <div class="flex items-center gap-space-sm bg-surface-container-lowest p-1.5 rounded-lg shadow-sm self-stretch lg:self-auto">
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-sm bg-surface-container-low">
                    <x-symbol nama="sync_saved_locally" class="text-[18px] text-secondary" />
                    <div class="flex flex-col">
                        <span class="font-label-sm text-label-sm text-on-surface-variant leading-none">Sinkronisasi Unit</span>
                        <span class="font-title-sm text-title-sm font-bold text-on-surface leading-tight mt-0.5">
                            {{ $ringkas['unit_terhubung'] }}/{{ $ringkas['unit_total'] }} Online
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2 px-3 py-1.5 rounded-sm bg-surface-container-low">
                    <x-symbol nama="schedule" class="text-[18px] text-on-tertiary-container" />
                    <div class="flex flex-col">
                        <span class="font-label-sm text-label-sm text-on-surface-variant leading-none">Lewat Batas Unit</span>
                        <span @class([
                            'font-title-sm text-title-sm font-bold leading-tight mt-0.5',
                            'text-error' => $ringkas['lewat'] > 0,
                            'text-secondary' => $ringkas['lewat'] === 0,
                        ])>
                            {{ $ringkas['lewat'] }} Kasus
                        </span>
                    </div>
                </div>

                <a
                    href="{{ route('admin.pengaduan.index') }}"
                    class="flex items-center gap-1.5 px-3 py-2 rounded-sm bg-secondary text-on-secondary font-label-md text-label-md hover:bg-secondary/90 transition-colors"
                    title="Muat ulang angka dan daftar pengaduan"
                >
                    <x-symbol nama="refresh" class="text-[16px]" />
                    Refresh Data
                </a>
            </div>
        </div>
        <!-- End of Strip Konteks -->

        <!-- Konsol Filter: Lapis 1, Lapis 2, dan pencarian gabungan. -->
        <div class="flex flex-col gap-space-sm bg-surface-container-lowest p-space-md rounded-lg shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-sm pb-space-sm">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-xs bg-primary-container font-label-sm text-label-sm uppercase font-bold tracking-wide" style="color: rgb(149, 249, 166)">Lapis 1</span>
                    <span class="font-title-sm text-title-sm text-on-surface font-semibold">Status Pengaduan Humas:</span>
                </div>

                <div class="flex flex-wrap items-center gap-1.5">
                    <a
                        href="{{ $daftar->urlTanpa('status') }}"
                        @class([
                            'px-3.5 py-1.5 rounded-xl font-label-md text-label-md flex items-center gap-1.5',
                            'bg-primary-container text-on-primary font-bold shadow-sm' => $daftar->status === null,
                            'bg-surface-container hover:bg-surface-container-high text-on-surface-variant transition-colors' => $daftar->status !== null,
                        ])
                        @if ($daftar->status === null) aria-current="true" @endif
                    >
                        Semua
                        <span class="px-1.5 py-0.2 rounded-full bg-surface-container-lowest/20 text-on-primary font-label-sm text-label-sm">
                            {{ number_format($daftar->jumlahTahap['semua'], 0, ',', '.') }}
                        </span>
                    </a>

                    @foreach (StatusPengaduan::cases() as $tahap)
                        <a
                            href="{{ $daftar->urlTanpa('status', $tahap->value) }}"
                            @class([
                                'px-3.5 py-1.5 rounded-xl font-label-md text-label-md flex items-center gap-1.5',
                                'bg-primary-container text-on-primary font-bold shadow-sm' => $daftar->status === $tahap,
                                'bg-surface-container hover:bg-surface-container-high text-on-surface-variant transition-colors' => $daftar->status !== $tahap,
                            ])
                            @if ($daftar->status === $tahap) aria-current="true" @endif
                        >
                            {{ $tahap->label() }}
                            <span @class([
                                'px-1.5 py-0.2 rounded-full font-label-sm text-label-sm font-bold',
                                'bg-surface-container-lowest/20 text-on-primary' => $daftar->status === $tahap,
                                $tahap->chip() => $daftar->status !== $tahap,
                            ])>
                                {{ number_format($daftar->jumlahTahap[$tahap->value], 0, ',', '.') }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-sm pt-space-xs">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-xs bg-tertiary-container text-tertiary-fixed font-label-sm text-label-sm uppercase font-bold tracking-wide">Lapis 2</span>
                    <span class="font-title-sm text-title-sm text-on-surface-variant font-medium">Status Investigasi di Unit:</span>
                </div>

                <div class="flex flex-wrap items-center gap-1.5">
                    <a
                        href="{{ $daftar->urlTanpa('investigasi') }}"
                        @class([
                            'px-3 py-1 rounded-sm font-label-sm text-label-sm',
                            'bg-surface-container-highest text-on-surface font-bold' => $daftar->investigasi === null,
                            'bg-surface-container-low hover:bg-surface-container text-on-surface-variant transition-colors' => $daftar->investigasi !== null,
                        ])
                        @if ($daftar->investigasi === null) aria-current="true" @endif
                    >
                        Semua Unit ({{ number_format($daftar->jumlahTahap['semua'], 0, ',', '.') }})
                    </a>

                    @foreach (StatusInvestigasi::cases() as $investigasi)
                        <a
                            href="{{ $daftar->urlTanpa('investigasi', $investigasi->value) }}"
                            @class([
                                'px-3 py-1 rounded-sm font-label-sm text-label-sm flex items-center gap-1 transition-colors',
                                'bg-surface-container-highest text-on-surface font-bold' => $daftar->investigasi === $investigasi,
                                'bg-surface-container-low hover:bg-surface-container text-on-surface-variant' => $daftar->investigasi !== $investigasi,
                            ])
                            @if ($daftar->investigasi === $investigasi) aria-current="true" @endif
                        >
                            <span class="w-1.5 h-1.5 rounded-full {{ $investigasi->titik() }}" aria-hidden="true"></span>
                            {{ $investigasi->label() }}
                            ({{ number_format($daftar->jumlahInvestigasi[$investigasi->value], 0, ',', '.') }})
                        </a>
                    @endforeach
                </div>
            </div>

            <form method="GET" action="{{ route('admin.pengaduan.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-space-sm pt-space-sm">
                @foreach ($filterAktif as $nama => $nilai)

                    <input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">

                @endforeach

                <div class="md:col-span-5 relative flex items-center">
                    <x-symbol nama="search" class="absolute left-3 text-outline text-[20px]" />
                    <input
                        type="search"
                        name="q"
                        value="{{ $daftar->cari }}"
                        class="w-full pl-10 pr-4 py-2.5 rounded-sm bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        placeholder="Cari No. Tiket, NRM, Nama Pelapor, atau Perihal..."
                    >
                </div>

                <div class="md:col-span-3 relative flex items-center">
                    <x-symbol nama="domain" class="absolute left-3 text-outline text-[18px]" />
                    <select
                        name="unit"
                        class="w-full pl-9 pr-8 py-2.5 rounded-sm bg-surface-container-low text-on-surface font-body-sm text-body-sm appearance-none focus:outline-none focus:ring-2 focus:ring-secondary/40"
                    >
                        <option value="">Semua Unit Tujuan</option>
                        @foreach ($daftar->pilihanUnit as $pilihan)
                            <option value="{{ $pilihan->kode }}" @selected($daftar->unit?->id === $pilihan->id)>
                                {{ $pilihan->kode }}: {{ $pilihan->nama }}
                            </option>
                        @endforeach
                    </select>
                    <x-symbol nama="expand_more" class="absolute right-3 text-outline text-[18px] pointer-events-none" />
                </div>

                <div class="md:col-span-2 relative flex items-center">
                    <x-symbol nama="medical_services" class="absolute left-3 text-outline text-[18px] pointer-events-none" />
                    <select
                        name="kategori"
                        class="w-full pl-9 pr-8 py-2.5 rounded-sm bg-surface-container-low text-on-surface font-body-sm text-body-sm appearance-none focus:outline-none focus:ring-2 focus:ring-secondary/40"
                    >
                        <option value="">Semua Kategori</option>
                        @foreach (KategoriPengaduan::cases() as $pilihan)
                            <option value="{{ $pilihan->value }}" @selected($daftar->kategori === $pilihan)>
                                {{ $pilihan->label() }}
                            </option>
                        @endforeach
                    </select>
                    <x-symbol nama="expand_more" class="absolute right-3 text-outline text-[18px] pointer-events-none" />
                </div>

                <div class="md:col-span-2 flex items-center gap-2">
                    <button
                        type="submit"
                        class="flex-1 py-2.5 px-3 rounded-sm bg-primary-container text-on-primary font-label-md text-label-md flex items-center justify-center gap-1.5 transition-colors hover:bg-primary"
                    >
                        <x-symbol nama="filter_list" class="text-[18px]" />
                        Terapkan
                    </button>

                    @if ($daftar->adaFilterLain() || $daftar->cari !== '')
                        <a
                            href="{{ route('admin.pengaduan.index') }}"
                            class="p-2.5 rounded-sm bg-surface-container hover:bg-surface-variant text-on-surface-variant hover:text-on-surface transition-colors shrink-0"
                            title="Hapus semua filter"
                            aria-label="Hapus semua filter"
                        >
                            <x-symbol nama="close" class="text-[18px]" />
                        </a>
                    @endif
                </div>
            </form>
        </div>
        <!-- End of Konsol Filter -->

        <!-- Tabel Utama Daftar Pengaduan -->
        <div class="w-full bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden flex flex-col">
            <div class="bg-surface-container px-space-md py-space-sm flex flex-wrap items-center justify-between gap-space-sm text-on-surface-variant font-label-sm text-label-sm">
                <div class="flex items-center gap-space-md">
                    <span class="flex items-center gap-1.5 text-on-surface font-bold">
                        <x-symbol nama="verified_user" class="text-[16px] text-secondary" />
                        Tampilan Terintegrasi Tripu SIMRS
                    </span>
                    <span class="hidden md:inline text-outline" aria-hidden="true">&bull;</span>
                    <span class="hidden md:flex items-center gap-1 text-on-surface-variant">
                        <span class="w-2 h-2 rounded-full bg-secondary" aria-hidden="true"></span>
                        Hijau: siklus investigasi sesuai target
                    </span>
                    <span class="hidden lg:flex items-center gap-1 text-on-surface-variant">
                        <span class="w-2 h-2 rounded-full bg-error" aria-hidden="true"></span>
                        Merah: tindak lanjut mendesak
                    </span>
                </div>

                <div class="flex items-center gap-space-sm">
                    <span class="hidden xl:flex items-center gap-1.5 text-on-surface-variant bg-surface-container-lowest px-2 py-0.5 rounded border border-outline-variant/20">
                        <x-symbol nama="notifications_active" class="text-[14px] text-secondary" />
                        Lonceng = eskalasi manual di luar pengingat hari ke-{{ $ringkas['hari_investigasi'] }}
                    </span>
                    <span>
                        Menampilkan <strong class="text-on-surface">{{ $tabel->count() }} dari {{ $ringkas['aktif'] }}</strong> tiket aktif
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full min-w-[1180px] text-left border-collapse">
                    <caption class="sr-only">
                        Daftar pengaduan pasien yang masuk ke portal pengaduan RSUD Dr. Soetomo
                    </caption>

                    {{-- Lebar kolom dikunci di sini supaya isi tiap kolom tidak
                         saling-desak, sedangkan tinggi baris tetap mengikuti isi. --}}
                    <colgroup>
                        <col class="w-[13%]">
                        <col class="w-[16%]">
                        <col class="w-[19%]">
                        <col class="w-[12%]">
                        <col class="w-[17%]">
                        <col class="w-[14%]">
                        <col class="w-[9%]">
                    </colgroup>

                    <thead>
                        <tr class="bg-surface-container text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider border-b border-outline-variant/20">
                            <th scope="col" class="py-space-sm px-space-md">No. Tiket &amp; Prioritas</th>
                            <th scope="col" class="py-space-sm px-space-md">Identitas Pelapor &amp; NRM</th>
                            <th scope="col" class="py-space-sm px-space-md">Pokok Keluhan &amp; Unit</th>
                            <th scope="col" class="py-space-sm px-space-md">Lapis 1: Status Utama</th>
                            <th scope="col" class="py-space-sm px-space-md">Lapis 2: Status di Unit</th>
                            <th scope="col" class="py-space-sm px-space-md">Monitoring SLA</th>
                            <th scope="col" class="py-space-sm px-space-md">Aksi Tindak</th>
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
                                    <div class="flex flex-col gap-1">
                                        <span class="font-title-sm text-title-sm font-bold text-on-surface">{{ $baris['kode'] }}</span>
                                        <span class="text-outline font-label-sm text-label-sm">
                                            {{ $pengaduan->created_at->format('d M Y, H:i') }} WIB
                                        </span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-xl {{ $baris['prioritas']['nada'] }} font-label-sm text-label-sm font-bold w-fit">
                                            <x-symbol :nama="$baris['prioritas']['ikon']" class="text-[12px]" />
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
                                        <span class="text-outline font-label-sm text-label-sm">{{ $baris['kategori']->label() }}</span>
                                    </div>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <div class="flex flex-col gap-1">
                                        <p class="font-title-sm text-title-sm font-semibold text-on-surface line-clamp-2 leading-snug">
                                            {{ $baris['subjek'] }}
                                        </p>
                                        <div class="flex items-center gap-1 font-label-sm text-label-sm font-semibold {{ $baris['tertaut'] ? 'text-secondary' : 'text-outline' }}">
                                            <x-symbol :nama="$baris['tertaut'] ? 'domain' : 'support_agent'" class="text-[14px] shrink-0" />
                                            <span class="truncate">{{ $baris['unit'] }}</span>
                                        </div>
                                        <span class="text-on-surface-variant font-label-sm text-label-sm italic line-clamp-1 leading-snug">
                                            &ldquo;{{ Str::limit($baris['kutipan'], 96) }}&rdquo;
                                        </span>
                                    </div>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <div class="flex flex-col gap-1.5">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl {{ $baris['status']->badgeAdmin() }} font-label-md text-label-md font-bold w-fit shadow-xs">
                                            <x-symbol :nama="$baris['status']->ikon()" class="text-[14px]" />
                                            {{ $baris['status']->label() }}
                                        </span>
                                        <span class="text-outline font-label-sm text-label-sm leading-snug">{{ $baris['catatanStatus'] }}</span>
                                    </div>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <div @class([
                                        'p-2 rounded-sm flex flex-col gap-1 border border-outline-variant/30',
                                        'bg-surface-container' => in_array($baris['investigasi'], StatusInvestigasi::racikanDanArsip(), true),
                                        'bg-surface-container-low' => ! in_array($baris['investigasi'], StatusInvestigasi::racikanDanArsip(), true),
                                    ]) title="{{ $baris['investigasi']->ringkas() }}">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full {{ $baris['investigasi']->titik() }} shrink-0" aria-hidden="true"></span>
                                            <span class="font-label-md text-label-md font-bold text-on-surface leading-none">
                                                {{ $baris['investigasi']->label() }}
                                            </span>
                                        </div>
                                        <span class="text-on-surface-variant font-label-sm text-label-sm leading-snug">
                                            {{ $baris['investigasi']->ringkasPendek() }}
                                        </span>
                                        <span @class([
                                            'font-label-sm text-label-sm leading-snug',
                                            'text-secondary' => in_array($baris['investigasi'], StatusInvestigasi::racikanDanArsip(), true),
                                            'text-outline' => ! in_array($baris['investigasi'], StatusInvestigasi::racikanDanArsip(), true),
                                        ])>
                                            {{ $baris['catatanInvestigasi'] }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <div class="flex flex-col gap-1.5">
                                        @if ($baris['sla']['jeda'])
                                            <div class="inline-flex items-center gap-1 px-2 py-1 rounded-sm bg-surface-container text-on-surface-variant font-label-sm text-label-sm font-semibold w-fit">
                                                <x-symbol nama="pause_circle" class="text-[13px] text-outline shrink-0" />
                                                {{ $baris['sla']['judul'] }}
                                            </div>
                                        @else
                                            <div class="flex items-center justify-between gap-space-xs font-label-sm text-label-sm">
                                                <span class="text-on-surface-variant truncate">{{ $baris['sla']['judul'] }}</span>
                                                <span class="font-bold {{ $baris['sla']['nadaPosisi'] }} whitespace-nowrap">{{ $baris['sla']['posisi'] }}</span>
                                            </div>
                                        @endif

                                        <div class="w-full bg-surface-container-high h-1.5 rounded-full overflow-hidden">
                                            <div class="{{ $baris['sla']['warnaProgres'] }} h-full rounded-full" style="width: {{ $baris['sla']['persen'] }}%"></div>
                                        </div>

                                        <span class="text-outline font-label-sm text-label-sm leading-snug">
                                            {{ $baris['sla']['ringkas'] }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <div class="flex flex-col items-stretch gap-1">
                                        @foreach ($baris['aksi'] as $tombol)
                                            @if ($tombol['url'])
                                                <a
                                                    href="{{ $tombol['url'] }}"
                                                    class="px-2 py-1.5 rounded-sm {{ $tombol['nada'] }} font-label-sm text-label-sm font-semibold flex items-center justify-center gap-1 hover:opacity-90 transition-opacity whitespace-nowrap"
                                                >
                                                    <x-symbol :nama="$tombol['ikon']" class="text-[14px] shrink-0" />
                                                    {{ $tombol['label'] }}
                                                </a>
                                            @else
                                                <button
                                                    type="button"
                                                    disabled
                                                    title="{{ $tombol['label'] }} &mdash; fitur sedang dirancang"
                                                    class="px-2 py-1.5 rounded-sm {{ $tombol['nada'] }} font-label-sm text-label-sm font-semibold flex items-center justify-center gap-1 cursor-not-allowed opacity-70 whitespace-nowrap"
                                                >
                                                    <x-symbol :nama="$tombol['ikon']" class="text-[14px] shrink-0" />
                                                    {{ $tombol['label'] }}
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-space-md py-space-xl text-center">
                                    <p class="font-title-sm text-title-sm font-semibold text-on-surface">Tidak ada pengaduan yang cocok</p>
                                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">
                                        @if ($daftar->adaFilterLain() || $daftar->cari !== '')
                                            Ubah kata kunci atau hapus filter untuk melihat pengaduan lain.
                                        @else
                                            Belum ada pengaduan yang masuk ke portal.
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Footer Tabel: ukuran halaman, ringkasan hasil, dan paginasi. -->
            <div class="bg-surface-container-lowest px-space-md py-space-sm flex flex-col md:flex-row items-center justify-between gap-space-md">
                <div class="flex items-center gap-space-md text-body-sm text-on-surface-variant flex-wrap">
                    <form method="GET" action="{{ route('admin.pengaduan.index') }}" class="flex items-center gap-2">
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
                        dari <strong>{{ number_format($ringkas['total'], 0, ',', '.') }}</strong> pengaduan
                    </span>
                </div>

                @if ($halamanTerakhir > 1)
                    <nav class="flex items-center gap-1" aria-label="Navigasi halaman daftar pengaduan">
                        @if ($tabel->onFirstPage())
                            <span class="p-1.5 rounded-sm bg-surface-container text-outline opacity-40" aria-hidden="true">
                                <x-symbol nama="first_page" class="text-[20px]" />
                            </span>
                            <span class="p-1.5 rounded-sm bg-surface-container text-outline opacity-40" aria-hidden="true">
                                <x-symbol nama="chevron_left" class="text-[20px]" />
                            </span>
                        @else
                            <a href="{{ $tabel->url(1) }}" class="p-1.5 rounded-sm bg-surface-container text-outline hover:text-on-surface transition-colors" aria-label="Halaman pertama">
                                <x-symbol nama="first_page" class="text-[20px]" />
                            </a>
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
                            <a href="{{ $tabel->nextPageUrl() }}" class="p-1.5 rounded-sm bg-surface-container text-on-surface hover:bg-surface-container-high transition-colors" rel="next" aria-label="Halaman berikutnya">
                                <x-symbol nama="chevron_right" class="text-[20px]" />
                            </a>
                            <a href="{{ $tabel->url($halamanTerakhir) }}" class="p-1.5 rounded-sm bg-surface-container text-on-surface hover:bg-surface-container-high transition-colors" aria-label="Halaman terakhir">
                                <x-symbol nama="last_page" class="text-[20px]" />
                            </a>
                        @endif
                    </nav>
                @endif
            </div>
            <!-- End of Footer Tabel -->
        </div>
        <!-- End of Tabel Utama Daftar Pengaduan -->
    </div>
@endsection
