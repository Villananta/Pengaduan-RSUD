@extends('admin.layout')

@section('title', 'Beranda Utama')

@section('content')
    <div class="w-full px-margin py-space-lg flex flex-col gap-space-lg">

        {{-- Peringatan kritis: tiket yang lewat SLA unit dan telaah yang siap diracik. --}}
        <section
            @class([
                'relative w-full overflow-hidden rounded-lg p-space-md shadow-sm',
                'bg-error-container text-on-error-container' => $dashboard->adaPelanggaran(),
                'bg-secondary-container text-on-secondary-container' => ! $dashboard->adaPelanggaran(),
            ])
            aria-live="polite"
        >
            <div
                @class([
                    'absolute -right-6 -bottom-6 w-36 h-36 rounded-xl blur-2xl pointer-events-none',
                    'bg-error/10' => $dashboard->adaPelanggaran(),
                    'bg-secondary/20' => ! $dashboard->adaPelanggaran(),
                ])
                aria-hidden="true"
            ></div>

            <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-space-md">
                <div class="flex items-start gap-space-sm">
                    <span
                        @class([
                            'w-10 h-10 rounded-full flex items-center justify-center shrink-0',
                            'bg-error text-on-error' => $dashboard->adaPelanggaran(),
                            'bg-secondary text-on-secondary' => ! $dashboard->adaPelanggaran(),
                        ])
                    >
                        <x-symbol :nama="$dashboard->adaPelanggaran() ? 'crisis_alert' : 'verified'" class="text-[22px] animate-pulse" />
                    </span>

                    <div class="flex flex-col">
                        <div class="flex items-center gap-space-xs flex-wrap">
                            <span class="font-title-md text-title-md font-bold">Peringatan Kritis Kepatuhan SLA Unit &amp; Humas</span>
                            <span
                                @class([
                                    'px-space-xs py-0.5 rounded-xs font-label-sm text-label-sm uppercase font-bold tracking-wider',
                                    'bg-error text-on-error' => $dashboard->adaPelanggaran(),
                                    'bg-secondary text-on-secondary' => ! $dashboard->adaPelanggaran(),
                                ])
                            >{{ $dashboard->adaPelanggaran() ? 'Tindakan Diperlukan' : 'Semua Dalam Batas' }}</span>
                        </div>

                        <p class="font-body-md text-body-md mt-0.5 max-w-4xl opacity-90">{{ $dashboard->kalimatKritis() }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-space-sm w-full lg:w-auto shrink-0 justify-end">
                    <button
                        type="button"
                        class="px-space-md py-2 rounded-sm bg-surface-container-lowest text-error font-label-lg text-label-lg shadow-sm transition-colors flex items-center gap-space-xs"
                    >
                        <x-symbol nama="emergency_home" class="text-[18px]" />
                        Eskalasi ke Wadir Pelayanan
                    </button>
                    <button
                        type="button"
                        class="px-space-md py-2 rounded-sm bg-error text-on-error font-label-lg text-label-lg shadow-sm transition-opacity flex items-center gap-space-xs"
                    >
                        <x-symbol nama="bolt" class="text-[18px]" />
                        Tinjau {{ $dashboard->telaah() }} Draft Jawaban
                    </button>
                </div>
            </div>
        </section>

        {{-- Baris statistik: empat tahap pengaduan, kepatuhan SLA, dan rata-rata penyelesaian. --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-space-md">
            @foreach ($tahap as $item)
                <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col justify-between gap-space-md">
                    <div class="flex items-center justify-between gap-space-sm">
                        <div class="flex flex-col">
                            <span class="font-label-md text-label-md text-on-surface-variant font-semibold uppercase tracking-wider">{{ $item->label() }}</span>

                            @if ($item === $tahap[0])
                                <span class="px-space-xs py-0.5 mt-1 rounded-xs bg-secondary-container text-on-secondary-container font-label-sm text-label-sm font-bold flex items-center gap-1 w-fit">
                                    <span class="w-1.5 h-1.5 rounded-full bg-secondary animate-pulse" aria-hidden="true"></span>
                                    Perlu triase penanganan
                                </span>
                            @endif
                        </div>

                        <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $item->warnaIkon() }}">
                            <x-symbol :nama="['inbox', 'autorenew', 'history_edu', 'task_alt'][$loop->index]" class="text-[18px]" />
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
                                <span>triage oleh humas</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="rounded-lg bg-primary-container text-on-primary p-space-md shadow-sm flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-24 h-24 rounded-xl bg-secondary/30 blur-xl pointer-events-none" aria-hidden="true"></div>

                <div class="relative z-10 flex items-center justify-between">
                    <span class="font-label-md text-label-md text-on-primary-container font-semibold uppercase tracking-wider">Kepatuhan SLA {{ $dashboard->hariKerja }} Hari</span>
                    <x-symbol nama="verified" class="text-[20px] text-primary-fixed" />
                </div>

                <div class="mt-space-md relative z-10">
                    <div class="flex items-baseline gap-space-xs">
                        <div class="font-display-lg text-display-lg font-bold leading-none">
                            {{ $dashboard->kepatuhan === null ? '—' : number_format($dashboard->kepatuhan, 1, ',', '.') }}
                        </div>
                        @if ($dashboard->kepatuhan !== null)
                            <span class="font-title-lg text-title-lg text-primary-fixed">%</span>
                        @endif
                    </div>

                    <div class="w-full bg-surface-container-lowest/20 rounded-full h-1.5 mt-space-xs overflow-hidden">
                        <div class="bg-secondary-container h-full rounded-full" style="width: {{ min(100, $dashboard->kepatuhan ?? 0) }}%"></div>
                    </div>

                    <span class="font-body-sm text-body-sm text-on-primary-container mt-1 block">
                        Standar Kemenkes &ge; {{ $dashboard->standarKepatuhan }}%
                    </span>
                </div>
            </div>

            <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col justify-between gap-space-md">
                <div class="flex items-center justify-between gap-space-sm">
                    <span class="font-label-md text-label-md text-on-surface-variant font-semibold uppercase tracking-wider">Rata-rata Waktu</span>
                    <span class="w-8 h-8 rounded-full bg-surface-container-high flex items-center justify-center text-primary-container">
                        <x-symbol nama="speed" class="text-[18px]" />
                    </span>
                </div>

                <div>
                    <div class="flex items-baseline gap-space-xs">
                        <div class="font-display-lg text-display-lg font-bold leading-none">
                            {{ $dashboard->rataRata === null ? '—' : number_format($dashboard->rataRata, 1, ',', '.') }}
                        </div>
                        <span class="font-title-md text-title-md text-on-surface-variant font-semibold">Hari Kerja</span>
                    </div>

                    @if ($dashboard->persenLebihCepat() === null)
                        <p class="text-on-surface-variant font-body-sm text-body-sm mt-space-xs">Belum ada pengaduan selesai</p>
                    @else
                        <div class="flex items-center gap-space-xs mt-space-xs text-secondary font-body-sm text-body-sm">
                            <x-symbol nama="check_circle" class="text-[16px]" />
                            <span class="font-semibold">{{ $dashboard->persenLebihCepat() }}% lebih cepat</span>
                            <span>dari limit {{ $dashboard->hariKerja }} hari</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sinkronisasi sub-status unit layanan terkait. --}}
        <div class="w-full rounded-lg bg-surface-container-low p-space-md shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-xs mb-space-sm pb-space-xs">
                <div class="flex items-center gap-space-xs">
                    <x-symbol nama="hub" class="text-secondary text-[20px]" />
                    <h2 class="font-title-lg text-title-lg font-bold text-on-surface">Sinkronisasi Real-Time: Status Agregat di Unit Layanan Terkait</h2>
                </div>
                <span class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-space-xs">
                    <span class="w-2 h-2 rounded-full bg-secondary animate-ping" aria-hidden="true"></span>
                    Sinkron SIMRS terpadu, pembaruan tiap 30 detik
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-md">
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

        {{-- Isi utama: daftar pengaduan butuh tindakan dan ringkasan beban unit. --}}
        <div class="w-full grid grid-cols-1 lg:grid-cols-12 gap-space-lg">
            <div class="lg:col-span-8 flex flex-col gap-space-md">
                <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col gap-space-md">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md pb-space-sm border-b border-outline-variant/30">
                        <div class="flex items-start gap-space-sm">
                            <span class="w-10 h-10 rounded-full bg-error-container text-on-error-container flex items-center justify-center shrink-0 mt-0.5">
                                <x-symbol nama="notification_important" class="text-[22px]" />
                            </span>
                            <div class="flex flex-col">
                                <h2 class="font-title-lg text-title-lg font-bold text-on-surface">Daftar Pengaduan Butuh Tindakan Segera</h2>
                                <p class="font-body-sm text-body-sm text-on-surface-variant">Antrean pengaduan prioritas tinggi, mendekati atau lewat SLA, dan memerlukan keputusan Humas.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-space-sm shrink-0">
                            <div class="relative">
                                <x-symbol nama="search" class="absolute left-3 top-2.5 text-[18px] text-outline" />
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
                        @forelse ($dashboard->perluTindakan as $pengaduan)
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
                                            <button type="button" class="{{ $tombol['nada'] }} text-label-sm text-label-sm flex items-center gap-space-xs">
                                                <x-symbol :nama="$tombol['ikon']" class="text-[16px]" />
                                                {{ $tombol['label'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </article>
                        @empty
                            <p class="rounded-sm bg-surface-container-low px-space-md py-space-lg text-center text-body-sm text-body-sm text-on-surface-variant">
                                Tidak ada pengaduan yang membutuhkan tindakan segera. Semua tiket berada dalam batas SLA.
                            </p>
                        @endforelse
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-space-sm pt-space-xs text-on-surface-variant font-body-sm text-body-sm">
                        <span>{{ $dashboard->ringkasTindakan() }}</span>
                        <span class="text-secondary font-title-sm text-title-sm font-semibold flex items-center gap-space-xs">
                            Buka Seluruh Antrean Tiket Terpadu
                            <x-symbol nama="arrow_forward" class="text-[16px]" />
                        </span>
                    </div>
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
                                    <span class="text-body-sm text-body-sm font-semibold {{ $persen !== null && $persen < $dashboard->standarKepatuhan ? 'text-error' : 'text-secondary' }}">
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
                            <p class="sm:col-span-2 lg:col-span-4 rounded-sm bg-surface-container-low px-space-md py-space-lg text-center text-body-sm text-body-sm text-on-surface-variant">
                                Belum ada pengaduan aktif yang ditugaskan ke unit MASTER_UNITS.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Sidebar: siklus SLA, status koneksi SIMRS, dan instruksi direksi. --}}
            <div class="lg:col-span-4 flex flex-col gap-space-md">
                <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col gap-space-md">
                    <div class="flex items-start justify-between gap-space-sm">
                        <div class="flex flex-col">
                            <span class="font-label-sm text-label-sm text-secondary font-bold uppercase tracking-wider">Standar Kepatuhan Medis</span>
                            <h3 class="font-title-md text-title-md font-bold text-on-surface mt-0.5">Siklus {{ $dashboard->hariKerja }} Hari Kerja RSUD</h3>
                        </div>
                        <span class="w-8 h-8 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-bold text-label-sm">SOP</span>
                    </div>

                    <p class="font-body-sm text-body-sm text-on-surface-variant">Setiap aduan wajib dituntaskan secara defensibel dalam matriks fase waktu:</p>

                    <div class="flex flex-col gap-space-md relative pl-6 before:content-[''] before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-container-high">
                        @foreach ($dashboard->siklus() as $pillar)
                            <div class="relative flex flex-col">
                                <span class="absolute -left-6 top-0 w-5 h-5 rounded-full flex items-center justify-center {{ $pillar['kelas']['titik'] }}">
                                    <x-symbol :nama="$pillar['ikon']" class="text-[13px]" />
                                </span>

                                <div class="flex items-center justify-between gap-space-sm">
                                    <span class="font-title-sm text-title-sm {{ $pillar['kelas']['judul'] }}">{{ $pillar['judul'] }}</span>
                                    <span class="font-label-sm text-label-sm {{ $pillar['kelas']['badge'] }}">{{ $pillar['badge'] }}</span>
                                </div>

                                <p class="font-body-sm text-body-sm mt-0.5 {{ $pillar['kelas']['ket'] }}">{{ $pillar['ket'] }}</p>

                                @if ($loop->last && $dashboard->totalLewat() > 0)
                                    <div class="mt-space-xs px-space-sm py-1 rounded-sm bg-error-container/30 border border-error/20 font-label-sm text-label-sm text-error font-bold flex items-center justify-between gap-space-sm">
                                        <span>{{ $dashboard->totalLewat() }} Tiket Melebihi Batas {{ $dashboard->hariInvestigasi }} Hari</span>
                                        <span class="underline truncate">{{ $dashboard->kodeTerlambat() }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col gap-space-md">
                    <div class="flex items-center justify-between gap-space-sm">
                        <div class="flex items-center gap-space-xs">
                            <x-symbol nama="lan" class="text-secondary text-[20px]" />
                            <h3 class="font-title-md text-title-md font-bold text-on-surface">Koneksi SIMRS &amp; Disposisi</h3>
                        </div>
                        <span class="px-space-xs py-0.5 rounded-xl bg-secondary-container text-on-secondary-container font-label-sm text-label-sm font-bold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-secondary" aria-hidden="true"></span>
                            {{ $dashboard->unitTerhubung }}/{{ count($dashboard->statusKoneksi) }} Online
                        </span>
                    </div>

                    <div class="flex flex-col gap-space-xs">
                        @forelse ($dashboard->unitKoneksi() as $unit)
                            <div class="flex items-center justify-between gap-space-sm p-space-xs rounded-sm">
                                <div class="flex items-center gap-space-sm">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $unit['aktif'] ? 'bg-secondary' : 'bg-error' }}" aria-hidden="true"></span>
                                    <div class="flex flex-col">
                                        <span class="font-title-sm text-title-sm font-semibold text-on-surface leading-tight">{{ $unit['nama'] }}</span>
                                        <span class="font-body-sm text-body-sm text-on-surface-variant">{{ $unit['ringkas'] }} &bull; {{ $unit['berkas'] }} Berkas Aktif</span>
                                    </div>
                                </div>
                                <x-symbol :nama="$unit['aktif'] ? 'cloud_done' : 'cloud_off'" class="text-[18px] {{ $unit['aktif'] ? 'text-secondary' : 'text-error' }}" />
                            </div>
                        @empty
                            <p class="text-body-sm text-body-sm text-on-surface-variant">Belum ada unit yang terdaftar di MASTER_UNITS.</p>
                        @endforelse
                    </div>

                    <div class="p-space-xs rounded-sm bg-surface-container flex items-center justify-between gap-space-sm text-on-surface-variant font-label-sm text-label-sm">
                        <span>Protokol Enkripsi Rekam Medis:</span>
                        <span class="font-semibold text-on-surface flex items-center gap-1">
                            <x-symbol nama="lock" class="text-[14px] text-secondary" />
                            HL7 / FHIR Terenkripsi
                        </span>
                    </div>
                </div>

                <div class="rounded-lg bg-primary-container text-on-primary p-space-md shadow-sm relative overflow-hidden flex flex-col gap-space-xs">
                    <div class="flex items-center gap-space-xs text-primary-fixed font-title-sm text-title-sm font-bold">
                        <x-symbol nama="shield" class="text-[18px]" />
                        Instruksi Direksi Humas
                    </div>
                    <p class="font-body-sm text-body-sm text-on-primary-container leading-relaxed">
                        &ldquo;Seluruh jawaban tertulis yang mencakup rekam medis, dosis kemoterapi, dan keputusan tindakan operatif wajib diverifikasi oleh Ketua Komite Medik sebelum diterbitkan kepada pihak keluarga pasien.&rdquo;
                    </p>
                    <div class="flex items-center justify-between gap-space-sm pt-space-xs border-t border-outline-variant/20 mt-space-xs">
                        <span class="font-label-sm text-label-sm text-primary-fixed">SK Direktur No. 188/442/2024</span>
                        <span class="font-label-sm text-label-sm text-on-primary-container">Surabaya, Jawa Timur</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
