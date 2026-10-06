@extends('admin.layout')

@section('title', 'Beranda Utama')

@section('content')

    @php
        // Antrean tiket dipaginasikan, jadi jendela halaman dihitung di
        // sini supaya template utama tetap hanya menampilkan data.
        $tindakan = $dashboard->perluTindakan;
        $antrean = $tindakan->getCollection();

        $halamanSekarang = $tindakan->currentPage();
        $halamanTerakhir = $tindakan->lastPage();
        $halamanMulai = max(1, $halamanSekarang - 2);
        $halamanAkhir = min($halamanTerakhir, $halamanMulai + 4);
        $halamanMulai = max(1, $halamanAkhir - 4);
    @endphp

    <div class="w-full px-margin py-space-lg flex flex-col gap-space-lg">

        <!-- Baris Statistik -->
        {{-- Kartu kepatuhan SLA dan rata-rata waktu sengaja dimatikan sementara,
             isinya masih disusun. --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-md">
            @foreach ($tahap as $item)
                <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col justify-between gap-space-md">
                    <div class="flex items-center justify-between gap-space-sm">
                        <div class="flex flex-col">
                            <span class="font-label-md text-label-md text-on-surface-variant font-semibold uppercase tracking-wider">{{ $item->label() }}</span>
                        </div>

                        <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $item->warnaIkon() }}">
                            <x-symbol :nama="$item->ikon()" class="text-[18px]" />
                        </span>
                    </div>

                    <div>
                        <div class="font-display-lg text-display-lg font-bold leading-none {{ $item->warnaAngka() }}">
                            {{ number_format($dashboard->jumlah($item), 0, ',', '.') }}
                        </div>

                        @if ($item === $tahap[1])
                            <div class="flex items-center gap-space-xs mt-space-xs text-on-surface-variant font-body-sm text-body-sm">
                                <span class="font-semibold text-secondary">{{ $dashboard->disposisi['unit'] }} disposisi unit</span>
                                <span>+ {{ $dashboard->disposisi['humas'] }} humas langsung</span>
                            </div>
                        @elseif ($item === $tahap[2])
                            <div class="flex items-center gap-space-xs mt-space-xs text-on-surface-variant font-body-sm text-body-sm">
                                <span class="font-semibold text-error">Menunggu</span>
                                <span>kelengkapan pelapor</span>
                            </div>
                        @elseif ($item === $tahap[3])
                            <div class="flex items-center gap-space-xs mt-space-xs text-on-surface-variant font-body-sm text-body-sm">
                                <x-symbol
                                    :nama="$dashboard->selesaiBulan['selisih'] >= 0 ? 'trending_up' : 'trending_down'"
                                    class="text-[14px] {{ $dashboard->selesaiBulan['selisih'] >= 0 ? 'text-secondary' : 'text-error' }}"
                                />
                                <span class="font-semibold {{ $dashboard->selesaiBulan['selisih'] >= 0 ? 'text-secondary' : 'text-error' }}">
                                    {{ $dashboard->selesaiBulan['selisih'] >= 0 ? '+' : '' }}{{ $dashboard->selesaiBulan['selisih'] }}
                                </span>
                                <span>bulan berjalan</span>
                            </div>
                        @else
                            <div class="flex items-center gap-space-xs mt-space-xs text-on-surface-variant font-body-sm text-body-sm">
                                <span class="font-semibold text-secondary">Belum ada keputusan</span>
                                <span>dari unit pelayanan</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

          
         
        </div>
        <!-- End of Baris Statistik -->

        <!-- Banner Kepatuhan SLA -->
        @if ($dashboard->adaPelanggaran())
            <!-- Peringatan Kritis -->
            <div class="w-full rounded-lg bg-error-container/25 border border-error/40 p-space-md shadow-sm">
                <div class="flex items-start gap-space-sm">
                    <span class="w-10 h-10 rounded-full bg-error text-on-error flex items-center justify-center shrink-0 mt-0.5">
                        <x-symbol nama="warning" class="text-[22px]" />
                    </span>

                    <div class="flex flex-col gap-space-xs">
                        <h2 class="font-title-lg text-title-lg font-bold text-on-error-container">Peringatan Kritis Kepatuhan SLA</h2>
                        <p class="font-body-sm text-body-sm text-on-error-container/80">
                            Terdapat {{ $dashboard->totalLewat() }} tiket yang melampaui batas
                            {{ $dashboard->hariInvestigasi }} hari investigasi unit. Periksa daftar pengaduan butuh tindakan segera.
                        </p>
                    </div>
                </div>
            </div>
            <!-- End of Peringatan Kritis -->

        @else
            <!-- SLA Aman -->
            <div class="w-full rounded-lg bg-secondary-container/30 border border-secondary/40 p-space-md shadow-sm">
                <div class="flex items-start gap-space-sm">
                    <span class="w-10 h-10 rounded-full bg-secondary text-on-secondary flex items-center justify-center shrink-0 mt-0.5">
                        <x-symbol nama="check_circle" class="text-[22px]" />
                    </span>

                    <div class="flex flex-col gap-space-xs">
                        <h2 class="font-title-lg text-title-lg font-bold text-on-secondary-container">Semua Dalam Batas</h2>
                        <p class="font-body-sm text-body-sm text-on-secondary-container/80">
                            Seluruh tiket dalam batas {{ $dashboard->hariInvestigasi }} hari investigasi unit,
                            dengan {{ $dashboard->telaah() }} telaah jawaban unit siap diracik humas.
                        </p>
                    </div>
                </div>
            </div>
            <!-- End of SLA Aman -->
        @endif
        <!-- End of Banner Kepatuhan SLA -->

        <!-- Status Tiket di Unit Layanan -->
        <div class="w-full rounded-lg bg-surface-container-low p-space-md shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-xs mb-space-sm pb-space-xs">
                <div class="flex items-center gap-space-xs">
                    <x-symbol nama="hub" class="text-secondary text-[20px]" />
                    <h2 class="font-title-lg text-title-lg font-bold text-on-surface">Status Tiket di Unit Layanan</h2>
                </div>

                <span class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-space-xs">
                    <span class="w-2 h-2 rounded-full bg-secondary animate-ping" aria-hidden="true"></span>
                    Sinkron SIMRS terpadu, pembaruan tiap 30 detik
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-space-md">
                @foreach ($dashboard->sinkronisasiUnit() as $kartu)
                    <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col justify-between gap-space-sm">
                        <div class="flex items-start justify-between gap-space-sm">
                            <div class="flex flex-col">
                                <span class="font-label-sm text-label-sm font-bold uppercase tracking-wider {{ $kartu['nada'] }}">{{ $kartu['eyebrow'] }}</span>
                                <span class="font-title-sm text-title-sm text-on-surface font-semibold mt-0.5">{{ $kartu['judul'] }}</span>
                            </div>

                            <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $kartu['warnaIkon'] }}">
                                <x-symbol :nama="$kartu['ikon']" class="text-[18px]" />
                            </span>
                        </div>

                        @if ($kartu['catatan'])
                            <p @class([
                                'text-[11px] text-on-surface-variant leading-tight flex items-center gap-1',
                                'mt-space-xs' => $kartu['ikonCatatan'] === null,
                            ])>
                                @if ($kartu['ikonCatatan'])
                                    <x-symbol :nama="$kartu['ikonCatatan']" class="text-[14px] text-secondary" />
                                @endif
                                {{ $kartu['catatan'] }}
                            </p>
                        @endif

                        <div class="flex items-end justify-between gap-space-sm">
                            <div class="font-headline-lg text-headline-lg font-bold leading-none {{ $kartu['warnaAngka'] }}">
                                {{ number_format($kartu['jumlah'], 0, ',', '.') }} <span class="font-body-sm text-body-sm text-on-surface-variant font-normal">Tiket</span>
                            </div>

                            @if ($kartu['aksinya'])
                                <button
                                    type="button"
                                    class="px-space-xs py-1 rounded-xs font-label-sm text-label-sm font-semibold transition-colors flex items-center gap-1 {{ $kartu['nadaCatatan'] ?? 'bg-error-container text-on-error-container' }}"
                                >
                                    <x-symbol :nama="$kartu['aksinya']['ikon']" class="text-[13px]" />
                                    {{ $kartu['aksinya']['label'] }}
                                </button>
                            @elseif ($kartu['nadaCatatan'])
                                <span class="px-space-xs py-0.5 rounded-xl font-label-sm text-label-sm font-semibold {{ $kartu['nadaCatatan'] }}">{{ $kartu['catatan'] }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <!-- End of Status Tiket di Unit Layanan -->

        <!-- Isi Utama -->
        <div class="w-full grid grid-cols-1 lg:grid-full gap-space-lg">
            <div class="lg:col-span-8 flex flex-col gap-space-md">
                <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col gap-space-md">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md pb-space-sm border-b border-outline-variant/30">
                        <div class="flex items-start gap-space-sm">
                            <span class="w-10 h-10 rounded-full bg-error-container text-on-error-container flex items-center justify-center shrink-0 mt-0.5">
                                <x-symbol nama="notification_important" class="text-[22px]" />
                            </span>
                            <div class="flex flex-col">
                                <h2 class="font-title-lg text-title-lg font-bold text-on-surface">Daftar Pengaduan Butuh Tindakan Segera</h2>
                                <p class="font-body-sm text-body-sm text-on-surface-variant">Hanya pengaduan yang sisa 2 hari kerja atau kurang menuju batas SLA, dan pengaduan yang ditandai kasus berat.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-space-sm shrink-0">
                            <div class="relative">
                                <x-symbol nama="search" class="absolute left-3 top-2.5 text-outline text-[18px]" />
                                <input
                                    type="text"
                                    placeholder="Cari nomor tiket, RM, kata kunci..."
                                    class="pl-9 pr-3 py-1.5 rounded-sm border border-outline-variant text-body-sm text-on-surface bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-secondary w-56 sm:w-64"
                                    disabled
                                >
                            </div>

                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-sm border border-outline-variant bg-surface-container-low text-on-surface font-label-sm text-label-sm font-semibold flex items-center gap-1"
                            >
                                <x-symbol nama="filter_list" class="text-[16px] text-outline" />
                                Filter
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col gap-space-sm">
                        @forelse ($antrean as $pengaduan)

                            @php $tiket = $dashboard->kartuTiket($pengaduan); @endphp

                            <article class="p-space-md rounded-lg {{ $tiket['kartu'] }} flex flex-col gap-space-sm shadow-sm">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-xs">
                                    <div class="flex items-center gap-space-sm flex-wrap">
                                        <span class="font-title-md text-title-md font-bold text-on-surface">#{{ $tiket['kode'] }}</span>
                                        <span class="px-space-xs py-0.5 rounded-xl {{ $tiket['nadaStatus'] }} font-label-sm text-label-sm font-bold">{{ $tiket['status'] }}</span>
                                        <span class="px-space-xs py-0.5 rounded-xl {{ $tiket['tahap']['nada'] }} font-label-sm text-label-sm font-bold flex items-center gap-1">
                                            <x-symbol :nama="$tiket['tahap']['ikon']" class="text-[13px]" />
                                            {{ $tiket['tahap']['label'] }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-space-xs font-label-sm text-label-sm font-bold {{ $tiket['sla']['nada'] }}">
                                        <x-symbol :nama="$tiket['sla']['ikon']" class="text-[16px]" />
                                        {{ $tiket['sla']['label'] }}
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-space-md">
                                    <div class="flex flex-col max-w-xl">
                                        <h3 class="font-title-sm text-title-sm font-bold text-on-surface">{{ $tiket['subjek'] }}</h3>

                                        <div class="flex items-center gap-space-md text-on-surface-variant font-body-sm text-body-sm mt-1 flex-wrap">
                                            <span class="flex items-center gap-1">
                                                <x-symbol nama="domain" class="text-[15px]" />
                                                {{ $tiket['unit'] }}
                                            </span>
                                            <span aria-hidden="true">&bull;</span>
                                            <span class="flex items-center gap-1">
                                                <x-symbol nama="badge" class="text-[15px]" />
                                                {{ $tiket['pelapor'] }} (NRM: {{ $tiket['nrm'] }})
                                            </span>
                                            <span aria-hidden="true">&bull;</span>
                                            <span class="{{ $tiket['catatan']['nada'] }}">{{ $tiket['catatan']['label'] }}</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-space-xs shrink-0 w-full sm:w-auto justify-end">
                                        @foreach ($tiket['tombol'] as $tombol)
                                            @if ($tombol['url'])
                                                <a
                                                    href="{{ $tombol['url'] }}"
                                                    class="{{ $tombol['nada'] }} text-label-sm flex items-center gap-space-xs"
                                                >
                                                    <x-symbol :nama="$tombol['ikon']" class="text-[16px]" />
                                                    {{ $tombol['label'] }}
                                                </a>
                                            @else
                                                <button type="button" class="{{ $tombol['nada'] }} text-label-sm flex items-center gap-space-xs">
                                                    <x-symbol :nama="$tombol['ikon']" class="text-[16px]" />
                                                    {{ $tombol['label'] }}
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </article>
                        @empty
                            <p class="rounded-sm bg-surface-container-low px-space-md py-space-lg text-center text-body-sm text-on-surface-variant">
                                Tidak ada pengaduan yang mendekati batas SLA dan tidak ada pula kasus berat yang menunggu keputusan humas.
                            </p>
                        @endforelse
                    </div>

                    <!-- Footer Antrean: ringkasan hasil dan navigasi halaman. -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-space-sm pt-space-xs text-on-surface-variant font-body-sm text-body-sm">
                        <span>{{ $dashboard->ringkasTindakan() }}</span>

                        @if ($halamanTerakhir > 1)
                            <nav class="flex items-center gap-1" aria-label="Navigasi halaman antrean tiket">
                                @if ($tindakan->onFirstPage())
                                    <span class="p-1.5 rounded-sm bg-surface-container text-outline opacity-40" aria-hidden="true">
                                        <x-symbol nama="first_page" class="text-[20px]" />
                                    </span>
                                    <span class="p-1.5 rounded-sm bg-surface-container text-outline opacity-40" aria-hidden="true">
                                        <x-symbol nama="chevron_left" class="text-[20px]" />
                                    </span>
                                @else
                                    <a href="{{ $tindakan->url(1) }}" class="p-1.5 rounded-sm bg-surface-container text-outline hover:text-on-surface transition-colors" aria-label="Halaman pertama">
                                        <x-symbol nama="first_page" class="text-[20px]" />
                                    </a>
                                    <a href="{{ $tindakan->previousPageUrl() }}" class="p-1.5 rounded-sm bg-surface-container text-outline hover:text-on-surface transition-colors" rel="prev" aria-label="Halaman sebelumnya">
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
                                            href="{{ $tindakan->url($halaman) }}"
                                            class="w-8 h-8 rounded-sm bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-medium flex items-center justify-center transition-colors"
                                        >{{ $halaman }}</a>
                                    @endif
                                @endfor

                                @if ($tindakan->hasMorePages())
                                    <span class="px-1 text-outline" aria-hidden="true">...</span>
                                    <a href="{{ $tindakan->nextPageUrl() }}" class="p-1.5 rounded-sm bg-surface-container text-on-surface hover:bg-surface-container-high transition-colors" rel="next" aria-label="Halaman berikutnya">
                                        <x-symbol nama="chevron_right" class="text-[20px]" />
                                    </a>
                                    <a href="{{ $tindakan->url($halamanTerakhir) }}" class="p-1.5 rounded-sm bg-surface-container text-on-surface hover:bg-surface-container-high transition-colors" aria-label="Halaman terakhir">
                                        <x-symbol nama="last_page" class="text-[20px]" />
                                    </a>
                                @endif
                            </nav>
                        @endif
                    </div>
                    <!-- End of Footer Antrean -->
                </div>

                <div class="w-full rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col gap-space-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="font-title-md text-title-md font-bold text-on-surface">Beban Resolusi Unit Terbanyak (Minggu Ini)</span>
                            <span class="font-body-sm text-body-sm text-on-surface-variant">Keseimbangan penyelesaian investigasi unit internal terhadap limit SLA</span>
                        </div>

                        <span class="px-space-xs py-0.5 rounded-xs bg-surface-container text-on-surface font-label-sm text-label-sm font-semibold">Audit Terpadu</span>
                    </div>

                    <div class="w-full pt-space-xs grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-sm">
                        @forelse ($dashboard->unitTerbebani as $unit)

                            @php $persen = $dashboard->kepatuhanUnit($unit); @endphp

                            <div class="p-space-sm rounded-sm bg-surface-container-low flex flex-col justify-between">
                                <span class="font-title-sm text-title-sm font-semibold text-on-surface leading-snug">{{ $unit->namaLengkap() }}</span>

                                <div class="mt-space-sm flex items-baseline justify-between gap-space-sm">
                                    <span class="font-headline-sm text-headline-sm font-bold text-on-surface">{{ $unit->beban_aktif }} Aduan</span>
                                    <span class="text-body-sm font-semibold {{ $persen !== null && $persen < $dashboard->standarKepatuhan ? 'text-error' : 'text-secondary' }}">
                                        {{ $persen === null ? 'SLA —' : 'SLA '.number_format($persen, 1, ',', '.').'%' }}
                                    </span>
                                </div>

                                <div class="w-full bg-surface-container-high h-1.5 rounded-full mt-1.5 overflow-hidden">
                                    <div
                                        class="h-full rounded-full {{ $persen !== null && $persen < $dashboard->standarKepatuhan ? 'bg-error' : 'bg-secondary' }}"
                                        style="width: {{ $persen ?? 0 }}%"
                                    ></div>
                                </div>
                            </div>
                        @empty
                            <p class="sm:col-span-2 lg:col-span-4 rounded-sm bg-surface-container-low px-space-md py-space-lg text-center text-body-sm text-on-surface-variant">
                                Belum ada pengaduan aktif yang ditugaskan ke unit pelayanan.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Isi Utama -->
    </div>
@endsection