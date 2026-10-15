@extends('unit.layout')

@section('title', 'Beranda Unit')

@use('App\Enums\StatusPengaduan')

@section('content')

    <div class="w-full px-margin py-space-lg flex flex-col gap-space-lg">

        <!-- Strip Konteks: identitas unit yang sedang dibuka. -->
        <div class="flex flex-col">
            <div class="flex items-center gap-space-xs text-secondary font-label-md text-label-md uppercase tracking-wider">
                <span class="w-2 h-2 rounded-full bg-secondary animate-ping" aria-hidden="true"></span>
                Dashboard Unit Kerja &bull; {{ $unit->kategori->label() }}
            </div>

            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight mt-0.5">
                {{ $unit->namaLengkap() }}
            </h1>

            <p class="font-body-md text-body-md text-on-surface-variant max-w-4xl">
                Ringkasan pengaduan yang ditugaskan ke unit ini, dihitung dari SLA
                {{ $dashboard->hariKerja }} hari kerja Permenkes No. 4/2018.
                @if ($unit->pic !== null)
                    PIC: {{ $unit->pic }}{{ $unit->jabatan_pic !== null ? ' — '.$unit->jabatan_pic : '' }}.
                @endif
            </p>
        </div>
        <!-- End of Strip Konteks -->

        <!-- Baris Statistik -->
        {{-- Empat kartu memakai tahap pengaduan yang sama dengan konsol admin,
             jadi angka di sini tidak pernah berbeda dari daftar yang dibaca
             humas. --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-md">
            @foreach (StatusPengaduan::cases() as $tahap)
                <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col justify-between gap-space-md">
                    <div class="flex items-center justify-between gap-space-sm">
                        <span class="font-label-md text-label-md text-on-surface-variant font-semibold uppercase tracking-wider">{{ $tahap->label() }}</span>

                        <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $tahap->warnaIkon() }}">
                            <x-symbol :nama="$tahap->ikon()" class="text-[18px]" />
                        </span>
                    </div>

                    <div>
                        <div class="font-display-lg text-display-lg font-bold leading-none {{ $tahap->warnaAngka() }}">
                            {{ number_format($dashboard->jumlah($tahap), 0, ',', '.') }}
                        </div>

                        <span class="font-body-sm text-body-sm text-on-surface-variant">
                            {{ $tahap->subStatus() }}
                        </span>
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
                        <p class="font-body-sm text-body-sm text-on-error-container/80">{{ $dashboard->kalimatSla() }}</p>
                    </div>
                </div>
            </div>
            <!-- End of Peringatan Kritis -->

        @elseif ($dashboard->adaPeringatan())
            <!-- Peringatan Dini -->
            <div class="w-full rounded-lg bg-tertiary-container/40 border border-tertiary-container p-space-md shadow-sm">
                <div class="flex items-start gap-space-sm">
                    <span class="w-10 h-10 rounded-full bg-tertiary-container text-on-tertiary-container flex items-center justify-center shrink-0 mt-0.5">
                        <x-symbol nama="timelapse" class="text-[22px]" />
                    </span>

                    <div class="flex flex-col gap-space-xs">
                        <h2 class="font-title-lg text-title-lg font-bold text-on-tertiary-container">Peringatan Dini Kepatuhan SLA</h2>
                        <p class="font-body-sm text-body-sm text-on-tertiary-container/80">{{ $dashboard->kalimatSla() }}</p>
                    </div>
                </div>
            </div>
            <!-- End of Peringatan Dini -->

        @else
            <!-- SLA Aman -->
            <div class="w-full rounded-lg bg-secondary-container/30 border border-secondary/40 p-space-md shadow-sm">
                <div class="flex items-start gap-space-sm">
                    <span class="w-10 h-10 rounded-full bg-secondary text-on-secondary flex items-center justify-center shrink-0 mt-0.5">
                        <x-symbol nama="check_circle" class="text-[22px]" />
                    </span>

                    <div class="flex flex-col gap-space-xs">
                        <h2 class="font-title-lg text-title-lg font-bold text-on-secondary-container">Semua Dalam Batas</h2>
                        <p class="font-body-sm text-body-sm text-on-secondary-container/80">{{ $dashboard->kalimatSla() }}</p>
                    </div>
                </div>
            </div>
            <!-- End of SLA Aman -->
        @endif
        <!-- End of Banner Kepatuhan SLA -->

        <!-- Status Beban & SLA di Unit Layanan -->
        <div class="w-full rounded-lg bg-surface-container-low p-space-md shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-xs mb-space-sm pb-space-xs">
                <div class="flex items-center gap-space-xs">
                    <x-symbol nama="query_stats" class="text-secondary text-[20px]" />
                    <h2 class="font-title-lg text-title-lg font-bold text-on-surface">Status Beban &amp; SLA di Unit Layanan</h2>
                </div>

                <span class="font-body-sm text-body-sm text-on-surface-variant">
                    Angka diperbarui saat halaman ini dibuka
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-md">
                @foreach ($dashboard->kartuTelaah() as $kartu)
                    <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col justify-between gap-space-sm">
                        <div class="flex items-start justify-between gap-space-sm">
                            <div class="flex flex-col">
                                <span class="font-label-sm text-label-sm font-bold uppercase tracking-wider text-on-surface-variant">{{ $kartu['eyebrow'] }}</span>
                                <span class="font-title-sm text-title-sm text-on-surface font-semibold mt-0.5">{{ $kartu['judul'] }}</span>
                            </div>

                            <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $kartu['warna_ikon'] }}">
                                <x-symbol :nama="$kartu['ikon']" class="text-[18px]" />
                            </span>
                        </div>

                        <p class="text-[11px] text-on-surface-variant leading-tight flex items-center gap-1">
                            @if ($kartu['ikon_catatan'])
                                <x-symbol :nama="$kartu['ikon_catatan']" class="text-[14px] text-secondary" />
                            @endif
                            {{ $kartu['catatan'] }}
                        </p>

                        <div class="font-headline-lg text-headline-lg font-bold leading-none {{ $kartu['warna_angka'] }}">
                            {{ $kartu['nilai'] }}
                            <span class="font-body-sm text-body-sm text-on-surface-variant font-normal">{{ $kartu['satuan'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <!-- End of Status Beban & SLA di Unit Layanan -->

        <!-- Isi Utama -->
        <div class="w-full grid grid-cols-1 lg:grid-cols-12 gap-space-lg">

            <!-- Kolom Kiri: daftar pengaduan yang mendesak. -->
            <div class="lg:col-span-8 flex flex-col gap-space-md">

                <!-- Daftar Pengaduan Butuh Tindakan Segera -->
                <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col gap-space-md">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md pb-space-sm border-b border-outline-variant/30">
                        <div class="flex items-start gap-space-sm">
                            <span class="w-10 h-10 rounded-full bg-error-container text-on-error-container flex items-center justify-center shrink-0 mt-0.5">
                                <x-symbol nama="notification_important" class="text-[22px]" />
                            </span>

                            <div class="flex flex-col">
                                <h2 class="font-title-lg text-title-lg font-bold text-on-surface">Daftar Pengaduan Butuh Tindakan Segera</h2>
                                <p class="font-body-sm text-body-sm text-on-surface-variant">
                                    Hanya pengaduan yang sudah memasuki dua hari kerja terakhir sebelum batas SLA,
                                    dan pengaduan yang ditandai kasus berat.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-space-sm">
                        @forelse ($dashboard->mendesak as $pengaduan)

                            @php $tiket = $dashboard->kartuMendesak($pengaduan); @endphp

                            <article class="p-space-md rounded-lg {{ $tiket['kartu'] }} flex flex-col gap-space-sm shadow-sm">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-xs">
                                    <div class="flex items-center gap-space-sm flex-wrap">
                                        <span class="font-title-md text-title-md font-bold text-on-surface">#{{ $tiket['kode'] }}</span>

                                        <span class="px-space-xs py-0.5 rounded-xl {{ $tiket['status']->badgeAdmin() }} font-label-sm text-label-sm font-bold">
                                            {{ $tiket['status']->label() }}
                                        </span>

                                        @if ($tiket['kasusBerat'])
                                            <span class="px-space-xs py-0.5 rounded-xl bg-error text-on-error font-label-sm text-label-sm font-bold">
                                                Kasus Berat
                                            </span>
                                        @endif
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
                                                <x-symbol nama="badge" class="text-[15px]" />
                                                {{ $tiket['pelapor'] }} (NRM: {{ $tiket['nrm'] }})
                                            </span>
                                            <span aria-hidden="true">&bull;</span>
                                            <span class="flex items-center gap-1">
                                                <x-symbol nama="task" class="text-[15px]" />
                                                {{ $tiket['investigasi']->label() }}
                                            </span>
                                        </div>
                                    </div>

                                    <a
                                        href="{{ $tiket['url'] }}"
                                        class="px-space-md py-2 rounded-sm bg-secondary text-on-secondary font-label-sm text-label-sm font-bold shadow-sm flex items-center gap-space-xs shrink-0 hover:bg-secondary/90 transition-colors"
                                    >
                                        <x-symbol nama="visibility" class="text-[16px]" />
                                        Buka Detail
                                    </a>
                                </div>
                            </article>
                        @empty
                            <p class="rounded-sm bg-surface-container-low px-space-md py-space-lg text-center text-body-sm text-on-surface-variant">
                                Tidak ada pengaduan yang mendekati batas SLA. Seluruh tiket unit ini masih
                                dalam batas {{ $dashboard->hariKerja }} hari kerja.
                            </p>
                        @endforelse
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-space-sm pt-space-xs text-on-surface-variant font-body-sm text-body-sm">
                        <span>{{ $dashboard->ringkasMendesak() }}</span>
                    </div>
                </div>
                <!-- End of Daftar Pengaduan Butuh Tindakan Segera -->

                <!-- Daftar Menunggu Racikan Humas -->
                <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col gap-space-md">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md pb-space-sm border-b border-outline-variant/30">
                        <div class="flex items-start gap-space-sm">
                            <span class="w-10 h-10 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center shrink-0 mt-0.5">
                                <x-symbol nama="hourglass_bottom" class="text-[22px]" />
                            </span>

                            <div class="flex flex-col">
                                <h2 class="font-title-lg text-title-lg font-bold text-on-surface">Menunggu Racikan Humas</h2>
                                <p class="font-body-sm text-body-sm text-on-surface-variant">
                                    Unit sudah mengirim jawaban, menunggu humas memproses lanjutan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-space-sm">
                        @forelse ($dashboard->menungguRacikan() as $pengaduan)

                            @php $tiket = $dashboard->kartuMendesak($pengaduan); @endphp

                            <article class="p-space-md rounded-lg {{ $tiket['kartu'] }} flex flex-col gap-space-sm shadow-sm">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-xs">
                                    <div class="flex items-center gap-space-sm flex-wrap">
                                        <span class="font-title-md text-title-md font-bold text-on-surface">#{{ $tiket['kode'] }}</span>

                                        <span class="px-space-xs py-0.5 rounded-xl {{ $tiket['status']->badgeAdmin() }} font-label-sm text-label-sm font-bold">
                                            {{ $tiket['status']->label() }}
                                        </span>

                                        @if ($tiket['kasusBerat'])
                                            <span class="px-space-xs py-0.5 rounded-xl bg-error text-on-error font-label-sm text-label-sm font-bold">
                                                Kasus Berat
                                            </span>
                                        @endif
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
                                                <x-symbol nama="badge" class="text-[15px]" />
                                                {{ $tiket['pelapor'] }} (NRM: {{ $tiket['nrm'] }})
                                            </span>
                                            <span aria-hidden="true">&bull;</span>
                                            <span class="flex items-center gap-1">
                                                <x-symbol nama="task" class="text-[15px]" />
                                                {{ $tiket['investigasi']->label() }}
                                            </span>
                                        </div>
                                    </div>

                                    <a
                                        href="{{ $tiket['url'] }}"
                                        class="px-space-md py-2 rounded-sm bg-secondary text-on-secondary font-label-sm text-label-sm font-bold shadow-sm flex items-center gap-space-xs shrink-0 hover:bg-secondary/90 transition-colors"
                                    >
                                        <x-symbol nama="visibility" class="text-[16px]" />
                                        Buka Detail
                                    </a>
                                </div>
                            </article>
                        @empty
                            <p class="rounded-sm bg-surface-container-low px-space-md py-space-lg text-center text-body-sm text-on-surface-variant">
                                Tidak ada pengaduan yang menunggu racikan humas.
                            </p>
                        @endforelse
                    </div>
                </div>
                <!-- End of Daftar Menunggu Racikan Humas -->

                <!-- Daftar Sedang Telaah Unit -->
                <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col gap-space-md">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md pb-space-sm border-b border-outline-variant/30">
                        <div class="flex items-start gap-space-sm">
                            <span class="w-10 h-10 rounded-full bg-tertiary-container text-on-tertiary-container flex items-center justify-center shrink-0 mt-0.5">
                                <x-symbol nama="search" class="text-[22px]" />
                            </span>

                            <div class="flex flex-col">
                                <h2 class="font-title-lg text-title-lg font-bold text-on-surface">Sedang Telaah Unit</h2>
                                <p class="font-body-sm text-body-sm text-on-surface-variant">
                                    Pengaduan yang sudah didisposisikan ke unit ini tapi belum dikirim jawaban.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-space-sm">
                        @forelse ($dashboard->sedangTelaah() as $pengaduan)

                            @php $tiket = $dashboard->kartuMendesak($pengaduan); @endphp

                            <article class="p-space-md rounded-lg {{ $tiket['kartu'] }} flex flex-col gap-space-sm shadow-sm">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-xs">
                                    <div class="flex items-center gap-space-sm flex-wrap">
                                        <span class="font-title-md text-title-md font-bold text-on-surface">#{{ $tiket['kode'] }}</span>

                                        <span class="px-space-xs py-0.5 rounded-xl {{ $tiket['status']->badgeAdmin() }} font-label-sm text-label-sm font-bold">
                                            {{ $tiket['status']->label() }}
                                        </span>

                                        @if ($tiket['kasusBerat'])
                                            <span class="px-space-xs py-0.5 rounded-xl bg-error text-on-error font-label-sm text-label-sm font-bold">
                                                Kasus Berat
                                            </span>
                                        @endif
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
                                                <x-symbol nama="badge" class="text-[15px]" />
                                                {{ $tiket['pelapor'] }} (NRM: {{ $tiket['nrm'] }})
                                            </span>
                                            <span aria-hidden="true">&bull;</span>
                                            <span class="flex items-center gap-1">
                                                <x-symbol nama="task" class="text-[15px]" />
                                                {{ $tiket['investigasi']->label() }}
                                            </span>
                                        </div>
                                    </div>

                                    <a
                                        href="{{ $tiket['url'] }}"
                                        class="px-space-md py-2 rounded-sm bg-secondary text-on-secondary font-label-sm text-label-sm font-bold shadow-sm flex items-center gap-space-xs shrink-0 hover:bg-secondary/90 transition-colors"
                                    >
                                        <x-symbol nama="visibility" class="text-[16px]" />
                                        Buka Detail
                                    </a>
                                </div>
                            </article>
                        @empty
                            <p class="rounded-sm bg-surface-container-low px-space-md py-space-lg text-center text-body-sm text-on-surface-variant">
                                Tidak ada pengaduan yang sedang telaah di unit ini.
                            </p>
                        @endforelse
                    </div>
                </div>
                <!-- End of Daftar Sedang Telaah Unit -->

            </div>
            <!-- End of Kolom Kiri -->

            <!-- Kolom Kanan: keadaan unit. -->
            <div class="lg:col-span-4 flex flex-col gap-space-lg">

                <!-- Kartu Status Unit: posisi koneksi dan disposisi terakhir. -->
                <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col gap-space-md">
                    <div class="flex items-center gap-space-sm pb-space-sm border-b border-outline-variant/30">
                        <x-symbol nama="domain" class="text-secondary text-[22px]" />
                        <div>
                            <h2 class="font-title-lg text-title-lg font-bold text-on-surface">Keadaan Unit</h2>

                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                Dibaca dari master unit, bukan dari tiket yang sedang berjalan.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-space-sm">
                        <span class="w-10 h-10 rounded-lg {{ $unit->disposisi->badge() }} flex items-center justify-center shrink-0">
                            <x-symbol nama="hub" class="text-[22px]" />
                        </span>

                        <div class="flex flex-col">
                            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">
                                Disposisi Terakhir
                            </span>
                            <span class="font-title-sm text-title-sm font-bold text-on-surface">{{ $unit->disposisi->label() }}</span>
                            <span class="font-label-sm text-label-sm text-on-surface-variant">{{ $unit->disposisi->ringkas() }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-sm text-on-surface font-body-sm text-body-sm">
                        <div>
                            <span class="text-on-surface-variant text-[11px] block">Koneksi SIMRS</span>
                            <strong>{{ $unit->koneksi_simrs ? 'Terhubung' : 'Tidak terhubung' }}</strong>
                        </div>
                        <div>
                            <span class="text-on-surface-variant text-[11px] block">Pemeriksaan terakhir</span>
                            <strong>
                                {{ $unit->koneksi_simrs_terakhir !== null ? $unit->koneksi_simrs_terakhir->translatedFormat('d M Y, H:i') : 'Belum pernah' }}
                            </strong>
                        </div>
                        <div>
                            <span class="text-on-surface-variant text-[11px] block">Status akses akun</span>
                            <strong>{{ $unit->status_akses->label() }}</strong>
                        </div>
                        <div>
                            <span class="text-on-surface-variant text-[11px] block">Jam layanan</span>
                            <strong>{{ $unit->jam_layanan }}</strong>
                        </div>
                    </div>
                </div>
                <!-- End of Kartu Status Unit -->

            </div>
            <!-- End of Kolom Kanan -->
        </div>
        <!-- End of Isi Utama -->

        <!-- Pengaduan Terbaru Unit -->
        <div class="w-full rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col gap-space-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-xs pb-space-sm border-b border-outline-variant/30">
                <div class="flex items-center gap-space-xs">
                    <x-symbol nama="history" class="text-secondary text-[20px]" />
                    <h2 class="font-title-lg text-title-lg font-bold text-on-surface">Pengaduan Terbaru</h2>
                </div>

                <a
                    href="{{ $dashboard->urlLihatSemua() }}"
                    class="px-space-md py-2 rounded-sm bg-secondary text-on-secondary font-label-sm text-label-sm font-bold shadow-sm flex items-center gap-space-xs hover:bg-secondary/90 transition-colors self-start md:self-auto"
                    title="Buka seluruh disposisi milik unit ini"
                >
                    <x-symbol nama="open_in_new" class="text-[16px]" />
                    Lihat Semua
                </a>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full min-w-[900px] text-left border-collapse">
                    <caption class="sr-only">
                        Pengaduan terbaru yang ditugaskan ke {{ $unit->namaLengkap() }}
                    </caption>

                    {{-- Lebar kolom dikunci di sini supaya isi tiap kolom tidak
                         saling-desak, sedangkan tinggi baris tetap mengikuti isi. --}}
                    <colgroup>
                        <col class="w-[14%]">
                        <col class="w-[24%]">
                        <col class="w-[17%]">
                        <col class="w-[16%]">
                        <col class="w-[13%]">
                        <col class="w-[16%]">
                    </colgroup>

                    <thead>
                        <tr class="bg-surface-container text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider border-b border-outline-variant/20">
                            <th scope="col" class="py-space-sm px-space-md">No. Tiket</th>
                            <th scope="col" class="py-space-sm px-space-md">Pokok Keluhan</th>
                            <th scope="col" class="py-space-sm px-space-md">Pelapor</th>
                            <th scope="col" class="py-space-sm px-space-md">Status &amp; Di Unit</th>
                            <th scope="col" class="py-space-sm px-space-md">Monitoring SLA</th>
                            <th scope="col" class="py-space-sm px-space-md">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-outline-variant/20 text-body-sm text-on-surface">
                        @forelse ($dashboard->terbaru as $pengaduan)

                            @php $baris = $dashboard->barisTerbaru($pengaduan); @endphp

                            <tr @class([
                                'align-top transition-colors',
                                'bg-surface-container-low/50' => $baris['redup'],
                                'hover:bg-surface-container-low' => ! $baris['redup'],
                            ])>
                                <td class="py-space-sm px-space-md align-top">
                                    <div class="flex flex-col gap-1">
                                        <span class="font-title-sm text-title-sm font-bold text-on-surface">{{ $baris['kode'] }}</span>
                                        <span class="text-outline font-label-sm text-label-sm">{{ $baris['tanggal'] }} WIB</span>
                                    </div>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <p class="font-title-sm text-title-sm font-semibold text-on-surface line-clamp-2 leading-snug">
                                        {{ $baris['subjek'] }}
                                    </p>
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
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl {{ $baris['status']->badgeAdmin() }} font-label-md text-label-md font-bold w-fit shadow-xs">
                                            <x-symbol :nama="$baris['status']->ikon()" class="text-[14px]" />
                                            {{ $baris['status']->label() }}
                                        </span>

                                        <span class="text-outline font-label-sm text-label-sm leading-snug">
                                            {{ $baris['investigasi']->label() }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <div class="flex items-start gap-1.5 {{ $baris['sla']['nada'] }}">
                                        <x-symbol :nama="$baris['sla']['ikon']" class="text-[15px] shrink-0" />
                                        <span class="font-label-md text-label-md font-semibold leading-snug">
                                            {{ $baris['sla']['label'] }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <a
                                        href="{{ $baris['url'] }}"
                                        class="inline-flex items-center justify-center gap-space-xs px-space-md py-2 rounded-sm bg-secondary text-on-secondary font-label-sm text-label-sm font-bold shadow-sm hover:bg-secondary/90 transition-colors whitespace-nowrap"
                                    >
                                        <x-symbol nama="visibility" class="text-[16px]" />
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-space-lg px-space-md text-center text-on-surface-variant">
                                    Belum ada pengaduan yang ditugaskan ke unit ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <!-- End of Pengaduan Terbaru Unit -->

    </div>
@endsection
