@extends('admin.layout')

@section('title', 'Beranda Admin')

@section('content')
    @php
        use App\Enums\StatusPengaduan;
        use App\Support\Sla;
    @endphp

    {{-- Banner peringatan kritis: rindu SLA dan sinkronisasi unit. --}}
    <section
        @class([
            'relative isolate overflow-hidden rounded-lg p-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)]',
            'bg-admin-danger-soft' => $dashboard->adaPelanggaran(),
            'bg-admin-success-soft' => ! $dashboard->adaPelanggaran(),
        ])
        aria-live="polite"
    >
        <div
            @class([
                'absolute -bottom-6 -right-6 h-36 w-36 rounded-xl blur-[20px]',
                'bg-admin-danger-base/10' => $dashboard->adaPelanggaran(),
                'bg-admin-success-strong/10' => ! $dashboard->adaPelanggaran(),
            ])
            aria-hidden="true"
        ></div>

        <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-2">
                <span
                    @class([
                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl',
                        'bg-admin-danger-base' => $dashboard->adaPelanggaran(),
                        'bg-admin-success-strong' => ! $dashboard->adaPelanggaran(),
                    ])
                >
                    <x-icon
                        nama="{{ $dashboard->adaPelanggaran() ? 'peringatan' : 'centang' }}"
                        class="h-5 w-5 text-white"
                    />
                </span>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-1">
                        <h2
                            @class([
                                'text-base font-bold leading-[22px]',
                                'text-admin-danger-strong' => $dashboard->adaPelanggaran(),
                                'text-admin-success-strong' => ! $dashboard->adaPelanggaran(),
                            ])
                        >
                            {{ $dashboard->adaPelanggaran() ? 'Peringatan Kritis SLA & Sinkronisasi' : 'SLA & Sinkronisasi Dalam Batas' }}
                        </h2>
                        <span
                            @class([
                                'rounded-full px-1 py-0.5 text-[11px] font-bold uppercase leading-[14px] tracking-[0.55px]',
                                'bg-admin-danger-base text-white' => $dashboard->adaPelanggaran(),
                                'bg-admin-success-strong text-white' => ! $dashboard->adaPelanggaran(),
                            ])
                        >{{ $dashboard->adaPelanggaran() ? 'Perlu Tindakan' : 'Aman' }}</span>
                    </div>

                    <p
                        @class([
                            'mt-1 max-w-3xl text-sm leading-5',
                            'text-admin-danger-strong/90' => $dashboard->adaPelanggaran(),
                            'text-admin-success-strong/90' => ! $dashboard->adaPelanggaran(),
                        ])
                    >{{ $dashboard->kalimatKritis() }}</p>
                </div>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <a
                    href="{{ route('admin.pengaduan.index', ['telaah' => 1]) }}"
                    class="inline-flex items-center gap-1 rounded bg-white px-4 py-2 text-sm font-semibold text-admin-danger-base shadow-[0_1px_2px_rgba(0,0,0,0.05)]"
                >
                    <x-icon nama="jam" class="h-4 w-4" />
                    Telaah jawaban unit
                </a>
                <a
                    href="{{ route('admin.pengaduan.index', ['status' => StatusPengaduan::Diproses->value]) }}"
                    class="inline-flex items-center gap-1 rounded bg-admin-danger-base px-4 py-2 text-sm font-semibold text-white shadow-[0_1px_2px_rgba(0,0,0,0.05)]"
                >
                    <x-icon nama="dokumen" class="h-3.5 w-3.5" />
                    Pengaduan diproses
                </a>
            </div>
        </div>
    </section>

    <div class="mt-6 grid gap-4 xl:grid-cols-3">
        {{-- Kolom kiri: bento statistik, sub-status, tiket, beban unit. --}}
        <div class="space-y-4 xl:col-span-2">
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($tahap as $item)
                    <a
                        href="{{ route('admin.pengaduan.index', ['status' => $item->value]) }}"
                        class="flex flex-col justify-between gap-4 rounded-lg bg-white p-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition hover:shadow-[0_0_0_1px_rgba(0,32,17,0.10)]"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase leading-4 tracking-[0.6px] text-admin-ink-muted">
                                    {{ $item->label() }}
                                </p>
                                <p class="mt-1 inline-flex items-center gap-1 rounded-sm bg-admin-success-soft px-1 py-0.5 text-[11px] font-bold leading-[14px] tracking-[0.44px] text-admin-success-strong">
                                    <span class="h-2 w-0.5 rounded-full bg-admin-success-strong"></span>
                                    {{ $dashboard->jumlah($item) }} tiket
                                </p>
                            </div>

                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $item->warnaIkon() }}">
                                <x-icon :nama="$item === $tahap[0] ? 'kotak' : ($item === $tahap[1] ? 'jam' : ($item === $tahap[2] ? 'dokumen' : 'centang'))" class="h-3.5 w-3.5" />
                            </span>
                        </div>

                        <div>
                            <p class="text-5xl font-bold leading-none tracking-[-0.96px] {{ $item->warnaAngka() }}">
                                {{ number_format($dashboard->jumlah($item), 0, ',', '.') }}
                            </p>
                            <p class="mt-1 flex items-center gap-1 text-xs leading-4 text-admin-ink-muted">
                                <x-icon nama="jam" class="h-3 w-3 shrink-0 text-admin-success-strong" />
                                {{ $item->selesai() ? 'Selesai ditindaklanjuti' : 'Menunggu penyelesaian' }}
                            </p>
                        </div>
                    </a>
                @endforeach

                {{-- Kartu SLA 12 hari: satu-satunya kartu gelap pada bento. --}}
                <a
                    href="{{ route('admin.pengaduan.index', ['status' => StatusPengaduan::Selesai->value]) }}"
                    class="relative isolate flex flex-col justify-between gap-4 overflow-hidden rounded-lg bg-admin-brand-900 p-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)]"
                >
                    <div class="absolute -right-4 -top-4 h-24 w-24 rounded-xl bg-admin-success-strong/30 blur-xl" aria-hidden="true"></div>

                    <div class="relative flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold uppercase leading-4 tracking-[0.6px] text-admin-brand-300">
                            Kepatuhan SLA {{ $dashboard->hariKerja }} Hari
                        </p>
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-admin-success-soft">
                            <x-icon nama="centang" class="h-4 w-4 text-admin-brand-200" />
                        </span>
                    </div>

                    <div class="relative">
                        <p class="flex items-baseline gap-1">
                            <span class="text-5xl font-bold leading-none tracking-[-0.96px] text-white">
                                {{ $dashboard->kepatuhan === null ? '—' : number_format($dashboard->kepatuhan, 1, ',', '.') }}
                            </span>
                            @if ($dashboard->kepatuhan !== null)
                                <span class="text-lg font-semibold leading-6 text-admin-brand-200">%</span>
                            @endif
                        </p>

                        <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-white/20">
                            <div
                                class="h-full rounded-full bg-admin-success-soft"
                                style="width: {{ min(100, $dashboard->kepatuhan ?? 0) }}%"
                            ></div>
                        </div>

                        <p class="mt-1.5 text-xs leading-4 text-admin-brand-300">Standar Kemenkes &ge; 90%</p>
                    </div>
                </a>

                <a
                    href="{{ route('admin.pengaduan.index', ['status' => StatusPengaduan::Selesai->value]) }}"
                    class="flex flex-col justify-between gap-4 rounded-lg bg-white p-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)]"
                >
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold uppercase leading-4 tracking-[0.6px] text-admin-ink-muted">
                            Rata-rata Penyelesaian
                        </p>
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-admin-info-mid">
                            <x-icon nama="grafik" class="h-3 w-3 text-admin-brand-900" />
                        </span>
                    </div>

                    <div>
                        <p class="flex items-baseline gap-1">
                            <span class="text-5xl font-bold leading-none tracking-[-0.96px] text-admin-ink">
                                {{ $dashboard->rataRata === null ? '—' : number_format($dashboard->rataRata, 1, ',', '.') }}
                            </span>
                            <span class="text-base font-semibold leading-[22px] text-admin-ink-muted">hari</span>
                        </p>
                        <p class="mt-2 flex items-center gap-1 text-xs leading-4 text-admin-ink-muted">
                            <x-icon nama="jam" class="h-3.5 w-3.5 shrink-0 text-admin-success-strong" />
                            {{ $dashboard->rataRata === null ? 'Belum ada pengaduan selesai' : 'Dari '.$dashboard->jumlah($tahap[3]).' pengaduan selesai' }}
                        </p>
                    </div>
                </a>
            </section>

            {{-- Baris sub-status sinkronisasi unit. --}}
            <section class="rounded-lg bg-admin-surface-alt p-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                <div class="flex flex-wrap items-center justify-between gap-2 pb-1">
                    <h2 class="flex items-center gap-1 text-lg font-bold leading-6 text-admin-ink">
                        <x-icon nama="unit" class="h-5 w-5 text-admin-success-strong" />
                        Sinkronisasi Sub-status Unit
                    </h2>
                    <span class="flex items-center gap-1 text-xs text-admin-ink-muted">
                        <span class="h-2 w-2 rounded-full bg-admin-success-strong"></span>
                        Terhubung langsung ke SIMRS
                    </span>
                </div>

                <div class="mt-2 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($dashboard->subStatus() as $baris)
                        <a
                            href="{{ $baris['url'] }}"
                            class="flex flex-col justify-between gap-2 rounded-lg bg-white p-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)]"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-[11px] font-bold uppercase leading-[14px] tracking-[0.55px] {{ $baris['status']->warnaAngka() }}">
                                        {{ $baris['status']->subStatus() }}
                                    </p>
                                    <p class="mt-0.5 text-sm font-semibold leading-5 text-admin-ink">{{ $baris['keterangan'] }}</p>
                                </div>

                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $baris['status']->warnaIkon() }}">
                                    <x-icon :nama="$baris['status']->perluAksi() ? 'dokumen' : 'centang'" class="h-3.5 w-3.5" />
                                </span>
                            </div>

                            <div class="flex items-end justify-between gap-2">
                                <p class="flex items-end gap-1">
                                    <span class="text-3xl font-bold leading-8 tracking-[-0.32px] {{ $baris['status']->warnaAngka() }}">
                                        {{ number_format($baris['jumlah'], 0, ',', '.') }}
                                    </span>
                                    <span class="pb-0.5 text-xs leading-4 text-admin-ink-muted">tiket</span>
                                </p>
                                <span
                                    @class([
                                        'rounded-sm px-1 py-0.5 text-[11px] font-bold leading-[14px] tracking-[0.44px]',
                                        'bg-admin-success-soft text-admin-success-strong' => $baris['bolehDitutup'],
                                        'bg-admin-danger-soft text-admin-danger-strong' => ! $baris['bolehDitutup'],
                                    ])
                                >{{ $baris['bolehDitutup'] ? 'Aktif' : 'Perlu racik' }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- Tabel pengaduan yang butuh tindakan segera. --}}
            <section class="space-y-4">
                <div class="overflow-hidden rounded-lg bg-white shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-admin-border/30 px-4 py-4">
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 flex h-10 w-10 items-center justify-center rounded-xl bg-admin-danger-soft">
                                <x-icon nama="peringatan" class="h-4 w-4 text-admin-danger-strong" />
                            </span>
                            <div>
                                <h2 class="text-lg font-bold leading-6 text-admin-ink">Pengaduan Butuh Tindakan Segera</h2>
                                <p class="text-xs leading-4 text-admin-ink-muted">
                                    Diurutkan dari yang paling melewati ambang SLA
                                </p>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('admin.pengaduan.index') }}" class="flex items-center gap-2">
                            <label class="sr-only" for="cari-tiket">Cari pengaduan</label>
                            <div class="relative">
                                <x-icon nama="cari" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-admin-ink-subtle" />
                                <input
                                    id="cari-tiket"
                                    name="q"
                                    type="search"
                                    value="{{ request('q') }}"
                                    placeholder="Cari nomor tiket, NRM, kata kunci..."
                                    class="h-[30px] w-64 rounded border border-admin-border bg-white py-1 pl-9 pr-3 text-xs text-admin-ink placeholder:text-admin-ink-subtle focus:border-admin-brand-700 focus:outline-none focus:ring-1 focus:ring-admin-brand-700"
                                >
                            </div>
                            <button
                                type="submit"
                                class="inline-flex h-7 items-center gap-1 rounded border border-admin-border bg-admin-surface-alt px-3 text-[11px] font-semibold leading-[14px] tracking-[0.44px] text-admin-ink"
                            >
                                <x-icon nama="filter" class="h-3 w-3 text-admin-ink-subtle" />
                                Cari
                            </button>
                        </form>
                    </div>

                    <div class="space-y-2 p-4">
                        @forelse ($dashboard->perluTindakan as $pengaduan)
                            <article
                                @class([
                                    'rounded-lg border p-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)]',
                                    'border-admin-border/60 bg-white' => ! $pengaduan->status->perluAksi(),
                                    'border-admin-danger-base/20 bg-admin-danger-soft/30' => $pengaduan->status->perluAksi(),
                                ])
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-base font-bold leading-[22px] text-admin-ink">{{ $pengaduan->subjek }}</h3>
                                        <span class="rounded-full px-1 py-0.5 text-[11px] font-bold leading-[14px] tracking-[0.44px] {{ $pengaduan->status->badgeAdmin() }}">
                                            {{ $pengaduan->status->label() }}
                                        </span>
                                        <span
                                            @class([
                                                'inline-flex items-center gap-1 rounded-full px-1 py-0.5 text-[11px] font-bold leading-[14px] tracking-[0.44px] text-white',
                                                'bg-admin-danger-base' => $pengaduan->sisaHariSla() === 0,
                                                'bg-admin-brand-700' => $pengaduan->sisaHariSla() > 0,
                                            ])
                                        >
                                            <x-icon nama="jam" class="h-3 w-3" />
                                            {{ $pengaduan->sisaHariSla() > 0
                                                ? 'Sisa '.$pengaduan->sisaHariSla().' hari kerja'
                                                : 'Lewat '.Sla::hariKerjaLewat($pengaduan->created_at).' hari kerja' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center justify-between gap-4">
                                    <div class="min-w-0 space-y-1">
                                        <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs leading-4 text-admin-ink-muted">
                                            <span class="inline-flex items-center gap-1">
                                                <x-icon nama="kartu" class="h-3 w-3" />
                                                {{ $pengaduan->kode_tiket }}
                                            </span>
                                            <span class="inline-flex items-center gap-1">
                                                <x-icon nama="unit" class="h-3 w-3" />
                                                {{ $pengaduan->namaUnit() }}
                                            </span>
                                            <span class="inline-flex items-center gap-1">
                                                <x-icon nama="jam" class="h-3 w-3" />
                                                Masuk {{ $pengaduan->created_at->format('d M Y') }}
                                            </span>
                                        </p>
                                        <p class="text-xs font-medium text-admin-ink-muted">{{ $pengaduan->ringkasSla() }}</p>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-2">
                                        <a
                                            href="{{ route('admin.pengaduan.index', ['q' => $pengaduan->kode_tiket]) }}"
                                            class="inline-flex items-center gap-1 rounded bg-admin-info-mid px-4 py-2 text-[11px] font-semibold leading-[14px] tracking-[0.44px] text-admin-ink"
                                        >
                                            <x-icon nama="cari" class="h-3.5 w-3.5" />
                                            Lihat detail
                                        </a>
                                        <a
                                            href="{{ route('admin.pengaduan.show', $pengaduan->kode_tiket) }}"
                                            class="inline-flex items-center gap-1 rounded bg-admin-danger-base px-4 py-2 text-[11px] font-bold leading-[14px] tracking-[0.44px] text-white shadow-[0_1px_2px_rgba(0,0,0,0.05)]"
                                        >
                                            <x-icon nama="dokumen" class="h-3.5 w-3.5" />
                                            Tangani
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <p class="rounded-lg bg-admin-surface-alt px-4 py-6 text-center text-xs text-admin-ink-muted">
                                Tidak ada pengaduan yang membutuhkan tindakan segera. Semua tiket berada dalam batas SLA.
                            </p>
                        @endforelse
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 px-4 pb-4">
                        <p class="text-xs text-admin-ink-muted">
                            Menampilkan {{ $dashboard->perluTindakan->count() }} tiket paling mendesak
                        </p>
                        <a
                            href="{{ route('admin.pengaduan.index') }}"
                            class="inline-flex items-center gap-1 text-sm font-semibold text-admin-success-strong hover:underline"
                        >
                            Lihat semua pengaduan
                            <x-icon nama="panah" class="h-3 w-3" />
                        </a>
                    </div>
                </div>

                {{-- Visualisasi beban unit dari MASTER_UNITS. --}}
                <div class="rounded-lg bg-white p-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h2 class="text-base font-bold leading-[22px] text-admin-ink">Beban Resolusi Unit Terbanyak</h2>
                            <p class="text-xs leading-4 text-admin-ink-muted">Dihitung dari pengaduan aktif per MASTER_UNITS</p>
                        </div>
                        <span class="rounded-sm bg-admin-info-soft px-1 py-0.5 text-[11px] font-semibold leading-[14px] tracking-[0.44px] text-admin-ink">
                            {{ $dashboard->unitTerbebani->count() }} unit
                        </span>
                    </div>

                    <div class="mt-2 flex flex-wrap gap-2">
                        @forelse ($dashboard->unitTerbebani as $unit)
                            @php $porsi = $dashboard->porsi($unit); @endphp
                            <a
                                href="{{ route('admin.pengaduan.index', ['q' => $unit->kode]) }}"
                                class="flex min-w-[180px] flex-1 flex-col justify-between gap-1.5 rounded bg-admin-surface-alt p-2"
                            >
                                <p class="line-clamp-2 text-sm font-semibold leading-[19px] text-admin-ink">{{ $unit->namaLengkap() }}</p>

                                <p class="flex items-baseline justify-between gap-1">
                                    <span class="text-xl font-bold leading-7 tracking-[-0.32px] text-admin-ink">
                                        {{ number_format($unit->beban_aktif, 0, ',', '.') }}
                                    </span>
                                    <span class="text-xs font-semibold leading-4 text-admin-brand-700">aktif</span>
                                </p>

                                <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-admin-info-mid">
                                    <div
                                        class="h-full rounded-full {{ $porsi >= 90 ? 'bg-admin-danger-base' : 'bg-admin-success-strong' }}"
                                        style="width: {{ max(6, $porsi) }}%"
                                    ></div>
                                </div>
                            </a>
                        @empty
                            <p class="w-full rounded-lg bg-admin-surface-alt px-4 py-6 text-center text-xs text-admin-ink-muted">
                                Belum ada pengaduan aktif yang ditugaskan ke unit MASTER_UNITS.
                            </p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>

        {{-- Sidebar: prosedur, status koneksi SIMRS, dan catatan kepatuhan. --}}
        <div class="space-y-4">
            <section class="rounded-lg bg-white p-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-[11px] font-bold uppercase leading-[14px] tracking-[0.55px] text-admin-success-strong">
                            Permenkes No. 4/2018
                        </p>
                        <h2 class="mt-0.5 text-base font-bold leading-[22px] text-admin-ink">
                            Siklus {{ $dashboard->hariKerja }} Hari Kerja
                        </h2>
                    </div>
                    <span class="rounded-full bg-admin-success-soft px-2 py-1.5 text-[11px] font-bold leading-[14px] tracking-[0.44px] text-center text-admin-success-strong">
                        {{ $dashboard->hariKerja }} HK
                    </span>
                </div>

                <p class="text-xs leading-4 text-admin-ink-muted">
                    Setiap aduan wajib dituntaskan secara defensibel dalam matriks fase waktu:
                </p>

                <ol class="relative mt-4 space-y-4 border-l-2 border-admin-info-mid pl-6">
                    @foreach ($dashboard->prosedur() as $index => $pillar)
                        <li class="relative">
                            <span
                                @class([
                                    'absolute -left-[30px] top-0 flex h-5 w-5 items-center justify-center rounded-full text-[11px] font-bold leading-[14px] text-white',
                                    'bg-admin-success-strong' => $index === 0,
                                    'bg-admin-info-mid' => $index === 1,
                                    'bg-admin-brand-900 ring-4 ring-admin-success-soft' => $index === 2,
                                    'bg-admin-danger-base ring-4 ring-admin-danger-soft' => $index === 3,
                                ])
                            >{{ $index + 1 }}</span>

                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h3
                                    @class([
                                        'text-sm leading-5',
                                        'font-bold text-admin-success-strong' => $index === 0,
                                        'font-semibold text-admin-ink' => $index === 1,
                                        'font-bold text-admin-brand-900' => $index === 2,
                                        'font-bold text-admin-danger-base' => $index === 3,
                                    ])
                                >{{ $pillar['judul'] }}</h3>

                                <span
                                    @class([
                                        'rounded-sm px-1 py-0.5 text-[11px] font-bold leading-[14px] tracking-[0.44px]',
                                        'bg-admin-success-soft text-admin-success-strong' => $index === 0,
                                        'bg-admin-danger-soft text-admin-danger-strong' => $index === 3,
                                        'bg-admin-brand-200 text-admin-brand-900' => $index === 2,
                                        'bg-admin-brand-200 text-admin-ink-muted' => $index === 1,
                                    ])
                                >{{ $pillar['batas'] }} HK</span>
                            </div>

                            <p class="text-[11px] font-semibold uppercase leading-[14px] tracking-[0.44px] text-admin-ink-subtle">
                                {{ $pillar['hari'] }}
                            </p>

                            <p
                                @class([
                                    'mt-0.5 text-xs leading-4',
                                    'font-medium text-admin-ink' => $index === 2,
                                    'text-admin-ink-muted' => $index !== 2,
                                ])
                            >{{ $pillar['ket'] }}</p>
                        </li>
                    @endforeach
                </ol>

                <p class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-sm border border-admin-danger-base/20 bg-admin-danger-soft/30 px-2 py-1.5 text-[11px] font-bold leading-[14px] tracking-[0.44px] text-admin-danger-base">
                    <span>Auto-eskalasi Wadir &amp; Komite Medik</span>
                    <span class="underline">tiap {{ $dashboard->hariInvestigasi }} hari kerja</span>
                </p>
            </section>

            <section class="rounded-lg bg-white p-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="flex items-center gap-1 text-base font-bold leading-[22px] text-admin-ink">
                        <x-icon nama="tautan" class="h-4 w-4 text-admin-success-strong" />
                        Status Koneksi SIMRS
                    </h2>
                    <span class="inline-flex items-center gap-1 rounded-full bg-admin-success-soft px-1 py-0.5 text-[11px] font-bold leading-[14px] tracking-[0.44px] text-admin-success-strong">
                        <span class="h-1.5 w-1.5 rounded-full bg-admin-success-strong"></span>
                        {{ $dashboard->unitTerhubung }} unit terhubung
                    </span>
                </div>

                <div class="mt-3 space-y-1">
                    @foreach ($dashboard->statusKoneksi as $unit)
                        <div class="flex items-center justify-between gap-2 rounded px-1 py-1 hover:bg-admin-surface-alt">
                            <div class="flex min-w-0 items-center gap-2">
                                <span
                                    @class([
                                        'h-2.5 w-2.5 shrink-0 rounded-full',
                                        'bg-admin-success-strong' => $unit->koneksiAktif(),
                                        'bg-admin-danger-base' => ! $unit->koneksiAktif(),
                                    ])
                                    aria-hidden="true"
                                ></span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold leading-[18px] text-admin-ink">{{ $unit->namaLengkap() }}</p>
                                    <p class="truncate text-xs leading-4 text-admin-ink-muted">{{ $unit->disposisi->ringkas() }}</p>
                                </div>
                            </div>

                            <x-icon
                                :nama="$unit->koneksiAktif() ? 'centang' : 'peringatan'"
                                class="h-3.5 w-3.5 shrink-0 {{ $unit->koneksiAktif() ? 'text-admin-success-strong' : 'text-admin-danger-base' }}"
                            />
                        </div>
                    @endforeach
                </div>

                <p class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-sm bg-admin-info-soft px-2 py-1.5 text-[11px] leading-[14px]">
                    <span class="font-bold tracking-[0.44px] text-admin-ink-muted">Ringkasan disposisi</span>
                    <span class="font-semibold tracking-[0.44px] text-admin-ink">
                        {{ $dashboard->statusKoneksi->filter(fn ($u) => $u->koneksiAktif())->count() }}/{{ $dashboard->statusKoneksi->count() }} aktif
                    </span>
                </p>
            </section>

            <section class="rounded-lg bg-admin-brand-900 p-4 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                <h2 class="flex items-center gap-1 text-sm font-bold leading-5 text-admin-brand-200">
                    <x-icon nama="dokumen" class="h-3.5 w-3.5" />
                    Catatan Kepatuhan Medis
                </h2>

                <p class="mt-2 text-xs leading-5 text-admin-brand-300">
                    &ldquo;Seluruh jawaban tertulis yang mencakup rekam medis, dosis kemoterapi, dan
                    keputusan tindakan operatif wajib diverifikasi oleh Ketua Komite Medik sebelum
                    diterbitkan kepada pihak keluarga pasien.&rdquo;
                </p>

                <p class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-white/20 pt-2 text-[11px] font-bold uppercase leading-[14px] tracking-[0.44px]">
                    <span class="text-admin-brand-200">Wadir Pelayanan Medik</span>
                    <span class="text-admin-brand-300">Komite Medik RSUD</span>
                </p>
            </section>
        </div>
    </div>
@endsection
