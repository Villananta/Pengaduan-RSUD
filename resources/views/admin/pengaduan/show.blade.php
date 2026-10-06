@extends('admin.layout')

@section('title', 'Detail Tiket '.$detail->kode())

@section('content')

    @php
        // Shorthand supaya template tidak mengulang nama variabel panjang
        // di setiap blok yang sama.
        $tiket = $detail->pengaduan;
        $unit = $detail->unit;
        $sla = $detail->sla;
        $tindakan = $detail->tindakan();
        $kode = $detail->kode();
    @endphp

    <div class="w-full px-margin py-space-md flex flex-col gap-space-lg">

        {{-- Hasil tindakan: muncul setelah admin memindahkan tahap
             pengaduan atau mengubah penandaan kasus berat. --}}
        @if (session('sukses'))
            <div
                role="status"
                class="w-full bg-secondary-container text-on-secondary-container rounded-lg px-space-md py-space-sm flex items-start gap-space-sm"
            >
                <x-symbol nama="check_circle" class="text-[20px] shrink-0" />
                <span class="text-body-sm">{{ session('sukses') }}</span>
            </div>
        @endif

        <!-- Remah Roti: kembali ke daftar, kode tiket, dan posisi SLA singkat. -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-sm">
            <div class="flex flex-wrap items-center gap-space-sm text-label-md text-label-md text-on-surface-variant">
                <a
                    href="{{ route('admin.pengaduan.index') }}"
                    class="flex items-center gap-1 hover:text-secondary transition-colors"
                >
                    <x-symbol nama="arrow_back" class="text-[16px]" />
                    Daftar Pengaduan
                </a>
                <span aria-hidden="true">/</span>
                <span class="font-mono font-bold text-on-surface">#{{ $detail->kode() }}</span>
            </div>

            <div class="flex items-center gap-space-xs">
                <span class="px-space-xs py-0.5 rounded-xl {{ $tiket->status->badgeAdmin() }} font-label-sm text-label-sm font-bold">
                    {{ $tiket->status->label() }}
                </span>
                <span class="px-space-xs py-0.5 rounded-xl {{ $sla['nadaZona'] }} font-label-sm text-label-sm font-bold flex items-center gap-1">
                    <x-symbol nama="schedule" class="text-[13px]" />
                    {{ $sla['zona'] }}
                </span>
            </div>
        </div>

        <!-- End of Remah Roti -->

        <!-- Kartu Identitas: data pengadu, unit terkait, dan penanda Lapis 2. -->
        <section class="w-full bg-surface-container-lowest rounded-lg shadow-sm p-space-md flex flex-col lg:flex-row lg:items-start justify-between gap-space-lg">
            <div class="flex flex-col gap-space-sm min-w-0">
                <div class="flex flex-wrap items-center gap-space-sm">
                    <span class="px-space-sm py-0.5 rounded-sm bg-surface-container-highest text-on-surface font-label-md text-label-md font-bold tracking-wide">
                        #{{ $detail->kode() }}
                    </span>

                    @if ($unit['tertaut'])
                        <span class="px-space-sm py-0.5 rounded-full {{ $unit['nada'] }} font-label-sm text-label-sm font-bold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-secondary" aria-hidden="true"></span>
                            {{ $unit['nama'] }}
                        </span>
                    @else
                        <span class="px-space-sm py-0.5 rounded-full bg-surface-container text-on-surface-variant font-label-sm text-label-sm font-bold flex items-center gap-1">
                            <x-symbol nama="support_agent" class="text-[14px]" />
                            {{ $unit['nama'] }}
                        </span>
                    @endif

                    <span class="text-on-surface-variant font-label-sm text-label-sm">
                        Masuk {{ $tiket->created_at->format('d M Y, H:i') }} WIB
                    </span>
                </div>

                <h1 class="font-headline-sm text-headline-sm text-on-surface font-bold tracking-tight">
                    {{ $tiket->subjek }}
                </h1>

                <div class="flex flex-wrap items-center gap-x-space-md gap-y-1 text-on-surface-variant font-body-sm text-body-sm">
                    <span class="flex items-center gap-1">
                        <x-symbol nama="person" class="text-[16px] text-secondary" />
                        <span>{{ $tiket->nama_lengkap }}</span>
                    </span>
                    <span aria-hidden="true">&bull;</span>
                    <span class="flex items-center gap-1">
                        <x-symbol nama="badge" class="text-[16px] text-outline" />
                        <span>NRM: <strong>{{ $tiket->nrm }}</strong></span>
                    </span>
                    <span aria-hidden="true">&bull;</span>
                    <span class="flex items-center gap-1">
                        <x-symbol nama="calendar_today" class="text-[16px] text-outline" />
                        <span>Kejadian {{ $tiket->waktu_kejadian->format('d M Y') }}</span>
                    </span>
                    <span aria-hidden="true">&bull;</span>
                        <span>{{ $tiket->kategori->label() }}</span>
                    <span aria-hidden="true">&bull;</span>
                    <span class="flex items-center gap-1">
                        <x-symbol nama="call" class="text-[16px] text-outline" />
                        <span>{{ $tiket->no_wa }}</span>
                    </span>
                </div>
            </div>

            {{-- Penanda Lapis 2: unit yang ditugaskan. Titik kondisi koneksi
                 SIMRS disembunyikan karena heartbeat-nya belum pernah ditulis. --}}

            <div class="flex items-center gap-space-sm bg-surface-container-low p-space-sm rounded-lg shrink-0">
                <span class="w-10 h-10 rounded-lg {{ $unit['tertaut'] ? 'bg-secondary text-on-secondary' : 'bg-surface-container-highest text-outline' }} flex items-center justify-center shrink-0">
                    <x-symbol :nama="$unit['tertaut'] ? 'domain' : 'support_agent'" class="text-[22px]" />
                </span>

                <div class="flex flex-col">
                    <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">
                        Status Lapis 2 &bull; Unit Terkait
                    </span>

                    <div class="flex items-center gap-1.5">
                        @if ($unit['tertaut'])
                            <span class="w-2 h-2 rounded-full bg-secondary" aria-hidden="true"></span>
                        @endif

                        <span class="font-title-sm text-title-sm font-bold text-on-surface">{{ $unit['status'] }}</span>
                    </div>

                    <span class="font-label-sm text-label-sm text-on-surface-variant">{{ $unit['keterangan'] }}</span>
                </div>
            </div>
        </section>
        <!-- End of Kartu Identitas -->

        <!-- Stepper Empat Tahap Pengaduan beserta posisi hari kerja -->
        <section class="w-full bg-surface-container-low/40 rounded-lg p-space-md flex flex-col gap-space-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm">
                <span class="font-label-sm text-label-sm uppercase tracking-wider font-bold text-on-surface-variant">
                    Progres Siklus Pengaduan RSUD (Lapis 1 &mdash; Humas Terpadu)
                </span>
                <span class="font-label-sm text-label-sm text-secondary font-semibold">
                    {{ $tiket->ringkasSla() }}
                </span>
            </div>

            <div class="relative flex flex-col sm:flex-row sm:items-start justify-between gap-space-sm">
                {{-- Rail tipis di belakang node. Panjangnya hanya ikut tahap
                     yang sudah dilalui, tahap terakhir tidak dicoret penuh. --}}
                <div class="hidden sm:block absolute inset-x-6 top-4 h-1" aria-hidden="true">
                    <div class="h-1 w-full rounded-full bg-surface-container-highest"></div>
                    <div
                        class="absolute left-0 top-0 h-1 rounded-full bg-secondary"
                        style="width: {{ $sla['persen'] }}%"
                    ></div>
                </div>

                @foreach ($detail->pilar as $pilar)
                    <div class="flex flex-col items-start sm:items-center gap-1 relative z-10 flex-1">
                        <span @class([
                            'w-8 h-8 rounded-full flex items-center justify-center shadow-sm',
                            'bg-secondary text-on-secondary' => $pilar['keadaan'] === 'tuntas',
                            'bg-primary-container text-on-primary ring-4 ring-secondary-container' => $pilar['keadaan'] === 'berjalan',
                            'bg-surface-container-lowest text-outline opacity-75' => $pilar['keadaan'] === 'menunggu',
                        ])>
                            <x-symbol :nama="$pilar['ikon']" class="text-[18px]" />
                        </span>

                        <span @class([
                            'font-label-md text-label-md',
                            'font-bold text-secondary' => $pilar['keadaan'] === 'tuntas',
                            'font-bold text-primary-container' => $pilar['keadaan'] === 'berjalan',
                            'font-semibold text-outline' => $pilar['keadaan'] === 'menunggu',
                        ])>
                            {{ $pilar['urut'] }}. {{ $pilar['label'] }}
                        </span>

                        <span class="font-label-sm text-label-sm text-on-surface-variant">
                            {{ $pilar['waktu'] ?? $pilar['subStatus'] }}
                        </span>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center gap-space-xs font-label-sm text-label-sm text-on-surface-variant">
                <x-symbol :nama="$sla['lewatUnit'] ? 'crisis_alert' : 'verified'" class="text-[16px] {{ $sla['lewatUnit'] ? 'text-error' : 'text-secondary' }}" />

                @if ($sla['lewatUnit'])
                    Investigasi unit sudah melewati batas {{ $sla['hariInvestigasi'] }} hari kerja, tiket masuk eskalasi.
                @else
                    Batas investigasi internal unit {{ $sla['hariInvestigasi'] }} hari kerja, target penyelesaian {{ $sla['target'] }}.
                @endif
            </div>
        </section>
        <!-- End of Stepper Empat Tahap Pengaduan -->

        <!-- Panel Kasus Berat: penandaan yang menggeser target SLA. -->
        {{-- Menyalakannya menambah hari kerja SLA, jadi panel ini menampilkan
             berapa lama target ikut bergeser. --}}

        <div @class([
            'w-full rounded-lg shadow-sm px-space-md py-space-sm flex flex-col md:flex-row md:items-center justify-between gap-space-md',
            'bg-error-container/40 border border-error/30' => $sla['kasusBerat'],
            'bg-surface-container-lowest' => ! $sla['kasusBerat'],
        ])>
            <div class="flex items-start gap-space-sm">
                <span @class([
                    'w-9 h-9 rounded-lg flex items-center justify-center shrink-0',
                    'bg-error text-on-error' => $sla['kasusBerat'],
                    'bg-error-container text-on-error-container' => ! $sla['kasusBerat'],
                ])>
                    <x-symbol nama="emergency" class="text-[20px]" />
                </span>

                <div>
                    <h2 class="font-title-md text-title-md text-on-surface font-bold">
                        @if ($sla['kasusBerat'])
                            Kasus Berat Aktif
                        @else
                            Tandai sebagai Kasus Berat
                        @endif
                    </h2>

                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                        Kasus berat menambah {{ $sla['tambahanHariKerja'] }} hari kerja pada target penyelesaian,
                        jadi standar {{ $sla['hariKerja'] }} hari kerja menjadi
                        <strong>{{ $sla['totalHariKerja'] }} hari kerja</strong>.

                        @if ($sla['kasusBeratAt'])
                            Ditandai sejak {{ $sla['kasusBeratAt'] }} WIB.
                        @endif
                    </p>
                </div>
            </div>

            {{-- Satu form untuk dua arah: tombol mengirim nilai aktif yang
                 kebalikan dari penandaan sekarang. --}}

            <form method="POST" action="{{ route('admin.pengaduan.kasus-berat', $kode) }}" class="shrink-0">
                @csrf

                <input type="hidden" name="aktif" value="{{ $sla['kasusBerat'] ? 0 : 1 }}">

                <button
                    type="submit"
                    aria-pressed="{{ $sla['kasusBerat'] ? 'true' : 'false' }}"
                    @class([
                        'px-space-lg py-2.5 rounded-full font-label-md text-label-md font-bold flex items-center gap-1.5 transition-colors',
                        'bg-surface-container text-on-surface-variant hover:bg-surface-container-highest' => $sla['kasusBerat'],
                        'bg-error text-on-error hover:opacity-90' => ! $sla['kasusBerat'],
                    ])
                >
                    <x-symbol :nama="$sla['kasusBerat'] ? 'toggle_off' : 'toggle_on'" class="text-[18px]" />
                    {{ $sla['kasusBerat'] ? 'Lepas Penandaan' : 'Tandai Kasus Berat' }}
                </button>
            </form>
        </div>
        <!-- End of Panel Kasus Berat -->

        <!-- Kolom Kerja: kiri kronologi dan formulasi, kanan keputusan,
             bawahnya baris penuh buat panel percakapan -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-start">

            <!-- Kolom Kiri: Kronologi Pengaduan -->
            <div class="lg:col-span-7 flex flex-col gap-space-lg">

                <!-- Kartu 1: isi pengaduan dan berkas lampiran dari pelapor -->
                <article class="bg-surface-container-lowest rounded-lg shadow-sm p-space-md flex flex-col gap-space-md">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm pb-space-sm border-b border-outline-variant/30">
                        <div class="flex items-center gap-space-sm">
                            <x-symbol nama="description" class="text-secondary text-[22px]" />
                            <div>
                                <h2 class="font-title-lg text-title-lg text-on-surface font-bold">Kronologi Pengaduan Pelapor</h2>

                                <p class="font-body-sm text-body-sm text-on-surface-variant">
                                    Isi persis seperti dikirim pelapor melalui portal publik, belum melalui telaah humas.
                                </p>
                            </div>
                        </div>

                        <span class="px-space-sm py-0.5 rounded-full bg-surface-container text-on-surface font-label-sm text-label-sm font-semibold">
                            {{ $tiket->kategori->judul() }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-sm text-on-surface font-body-sm text-body-sm">
                        <div>
                            <span class="text-on-surface-variant text-[11px] block">Nama Pelapor</span>
                            <strong>{{ $tiket->nama_lengkap }}</strong>
                        </div>
                        <div>
                            <span class="text-on-surface-variant text-[11px] block">Alamat</span>
                            <strong>{{ $tiket->alamat }}</strong>
                        </div>
                        <div>
                            <span class="text-on-surface-variant text-[11px] block">Surel</span>
                            <strong>{{ $tiket->email }}</strong>
                        </div>
                        <div>
                            <span class="text-on-surface-variant text-[11px] block">Unit yang pelapor sebut</span>
                            <strong>{{ $tiket->unit }}</strong>
                        </div>
                    </div>

                    <div class="bg-surface-container-low/50 p-space-md rounded-lg flex flex-col gap-1">
                        <span class="font-label-sm text-label-sm text-on-surface-variant uppercase font-bold">Isi Pengaduan</span>
                        <p class="text-on-surface leading-relaxed whitespace-pre-line">{{ $tiket->deskripsi }}</p>
                    </div>

                    {{-- Berkas lampiran. Foto langsung dipratinjau, berkas lain hanya nama. --}}

                    @if (count($detail->lampiran) > 0)
                        <div class="flex flex-col gap-space-xs">
                            <span class="font-label-sm text-label-sm uppercase font-bold text-on-surface-variant">
                                Lampiran Dari Pelapor ({{ count($detail->lampiran) }} Berkas)
                            </span>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-sm">
                                @foreach ($detail->lampiran as $berkas)
                                    {{-- Foto pelapor ditampilkan sebagai pratinjau, berkas lain
                                         tetap berupa baris nama supaya daftar tidak terlalu tinggi. --}}
                                    @if ($berkas['gambar'])
                                        <div class="flex flex-col gap-space-xs p-space-sm rounded-lg bg-surface-container">
                                            <a href="{{ $berkas['url'] }}" target="_blank" rel="noopener">
                                                <img
                                                    src="{{ $berkas['url'] }}"
                                                    alt="Lampiran {{ $berkas['nama'] }}"
                                                    class="w-full h-32 object-cover rounded-sm" style="width: 25% height:25% "
                                                >
                                            </a>

                                            <div class="flex items-center justify-between gap-space-sm">
                                                <span class="font-label-md text-label-md font-bold text-on-surface truncate">{{ $berkas['nama'] }}</span>
                                                <a
                                                    href="{{ $berkas['url'] }}"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="shrink-0 px-2 py-1 rounded-sm bg-surface-container-highest text-on-surface font-label-sm text-label-sm font-semibold flex items-center gap-1 hover:bg-surface-container-high transition-colors"
                                                >
                                                    <x-symbol nama="visibility" class="text-[16px]" />
                                                    Buka
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-space-sm p-space-sm rounded-lg bg-surface-container">
                                            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 bg-surface-container-highest text-error">
                                                <x-symbol nama="picture_as_pdf" class="text-[24px]" />
                                            </span>
                                            <div class="flex flex-col min-w-0">
                                                <span class="font-label-md text-label-md font-bold text-on-surface truncate">{{ $berkas['nama'] }}</span>
                                                <span class="font-label-sm text-label-sm text-on-surface-variant">Diunggah bersama pengaduan</span>
                                            </div>
                                            <a
                                                href="{{ $berkas['url'] }}"
                                                target="_blank"
                                                rel="noopener"
                                                class="ml-auto shrink-0 px-2 py-1 rounded-sm bg-surface-container-highest text-on-surface font-label-sm text-label-sm font-semibold flex items-center gap-1 hover:bg-surface-container-high transition-colors"
                                            >
                                                <x-symbol nama="download" class="text-[16px]" />
                                                Buka
                                            </a>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="text-body-sm text-on-surface-variant">Pelapor tidak melampirkan berkas pada pengaduan ini.</p>
                    @endif
                </article>
                <!-- End of Kartu 1 -->
            </div>
            <!-- End of Kolom Kiri -->

            <!-- Kolom Kanan: Keputusan Penanganan Pengaduan -->
            <div class="lg:col-span-5 flex flex-col gap-space-lg">

                <!-- Kartu 3: Jalur penanganan dan status disposisi unit -->
                <article class="bg-surface-container-lowest rounded-lg shadow-sm p-space-md flex flex-col gap-space-md">
                    <div class="flex items-center justify-between gap-space-sm pb-space-xs border-b border-outline-variant/20">
                        <div class="flex items-center gap-2">
                            <span class="w-9 h-9 rounded-lg bg-primary-container text-on-primary flex items-center justify-center shrink-0">
                                <x-symbol nama="alt_route" class="text-[20px]" />
                            </span>
                            <div>
                                <h2 class="font-title-md text-title-md text-on-surface font-bold">Keputusan Penanganan Pengaduan</h2>

                                <p class="font-body-sm text-body-sm text-on-surface-variant">Pilih jalur investigasi Lapis 2 atau penanganan langsung</p>
                            </div>
                        </div>

                        <span class="px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant font-label-sm text-label-sm font-bold">
                            {{ $unit['tertaut'] ? 'Terdisposisi' : 'Tanpa Unit' }}
                        </span>
                    </div>

                    {{-- Dua jalur penanganan. Jalur tanpa unit teknis sengaja
                         dinonaktifkan supaya tidak ada pilihan yang bohong. --}}

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-sm bg-surface-container-low p-space-xs rounded-lg" role="tablist">
                        <button
                            type="button"
                            disabled
                            @class([
                                'py-3 px-space-md rounded-lg font-title-sm text-title-sm font-bold flex items-center justify-center gap-1.5 cursor-not-allowed',
                                'bg-surface-container-lowest text-secondary shadow-sm ring-1 ring-secondary/30' => $unit['tertaut'],
                                'text-on-surface-variant opacity-60' => ! $unit['tertaut'],
                            ])
                        >
                            <x-symbol nama="forward_to_inbox" class="text-[18px]" />
                            Jalur 1: Disposisi ke Unit
                        </button>

                        <button
                            type="button"
                            disabled
                            title="Penanganan langsung humas belum punya alur pengesahan"
                            @class([
                                'py-3 px-space-md rounded-lg font-title-sm text-title-sm font-bold flex items-center justify-center gap-1.5 cursor-not-allowed',
                                'bg-surface-container-lowest text-secondary shadow-sm ring-1 ring-secondary/30' => ! $unit['tertaut'],
                                'text-on-surface-variant opacity-60' => $unit['tertaut'],
                            ])
                        >
                            <x-symbol nama="support_agent" class="text-[18px]" />
                            Jalur 2: Tangani Langsung
                        </button>
                    </div>

                    {{-- Ringkasan unit yang sudah ditugaskan. --}}

                    <div class="p-space-sm rounded-lg bg-surface-container-low border border-outline-variant/30 flex flex-col gap-space-sm">
                        <div class="flex items-center justify-between gap-space-sm">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full {{ $unit['tertaut'] ? 'bg-secondary' : 'bg-outline' }}" aria-hidden="true"></span>
                                <span class="font-label-sm text-label-sm font-bold text-on-surface uppercase tracking-wider">
                                    {{ $unit['tertaut'] ? 'Unit Ditugaskan' : 'Belum Ditugaskan' }}
                                </span>
                            </div>

                            @if ($unit['disposisi'])
                                <span class="px-2 py-0.5 rounded-full {{ $unit['disposisi']->badge() }} font-label-sm text-label-sm font-bold">
                                    {{ $unit['disposisi']->label() }}
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 gap-space-sm text-on-surface font-body-sm text-body-sm pt-1 border-t border-outline-variant/20">
                            <div>
                                <span class="text-on-surface-variant text-[11px] block">Unit Tujuan</span>
                                <strong>{{ $unit['nama'] }}</strong>
                            </div>
                        </div>
                    </div>

                    {{-- Daftar master unit. Formulir disposisi belum punya endpoint,
                         jadi pilihan unit ditampilkan nonaktif. --}}

                    <div class="flex flex-col gap-1 pt-1 border-t border-outline-variant/30">
                        <label for="pilihanUnit" class="font-label-sm text-label-sm font-bold text-on-surface flex items-center justify-between gap-space-sm">
                            <span>Pilih Instalasi / Unit Tujuan (Master Data)</span>
                            <span class="text-secondary text-[11px] font-semibold">Hanya Unit Aktif</span>
                        </label>

                        <select
                            id="pilihanUnit"
                            disabled
                            class="w-full py-2.5 px-space-md rounded-lg bg-surface-container-low font-body-sm text-body-sm text-on-surface border border-outline-variant opacity-70 cursor-not-allowed"
                        >
                            @foreach ($detail->pilihanUnit as $pilihan)
                                <option value="{{ $pilihan->kode }}" @selected($tiket->master_unit_id === $pilihan->id)>
                                    {{ $pilihan->namaLengkap() }}
                                </option>
                            @endforeach
                        </select>

                        <span class="font-label-sm text-label-sm text-outline">
                            Pengalihan disposisi dan instruksi kerja internal ke PIC belum dibangun, jadi kolom ini belum bisa diisi.
                        </span>
                    </div>
                </article>
                <!-- End of Kartu 3 -->
            </div>
            <!-- End of Kolom Kanan -->

            <!-- Kartu 4: percakapan pelapor dan kanal koordinasi unit, dibuat
                 selebar body supaya jadi tempat utama mengetik balasan -->
            <article class="lg:col-span-12 bg-surface-container-lowest rounded-lg shadow-sm flex flex-col overflow-hidden">

                <!-- Tab Kanal: penanda kanal aktif dan judul dua kanal di bawahnya. -->
                <div class="flex items-center bg-surface-container-low p-space-xs gap-space-sm">
                    <button
                        type="button"
                        data-tab="pelapor"
                        class="flex-1 py-3 px-space-md rounded-lg font-title-sm text-title-sm font-bold flex items-center justify-center gap-1.5 bg-surface-container-lowest text-on-surface shadow-sm"
                    >
                        <x-symbol nama="chat" class="text-[18px] text-secondary" />
                        Percakapan Pelapor

                        @if (count($detail->percakapan) > 0)
                            <span class="w-2 h-2 rounded-full bg-secondary" aria-hidden="true"></span>
                        @endif
                    </button>

                    <button
                        type="button"
                        data-tab="unit"
                        class="flex-1 py-3 px-space-md rounded-lg font-title-sm text-title-sm font-semibold text-on-surface-variant hover:text-on-surface flex items-center justify-center gap-1.5"
                    >
                        <x-symbol nama="sync_alt" class="text-[18px]" />
                        Koordinasi Unit
                    </button>
                </div>
                <!-- End of Tab Kanal -->

                <!-- Tab 1: percakapan pelapor dengan admin humas -->
                <div class="p-space-md flex flex-col gap-space-md" data-panel="pelapor">

                    <div class="flex items-center justify-between gap-space-sm pb-space-xs text-on-surface-variant font-label-sm text-label-sm bg-surface-container-low p-space-sm rounded-lg">
                        <span class="flex items-center gap-1">
                            <x-symbol nama="smartphone" class="text-[16px] text-secondary" />
                            Kanal Balasan Pelapor
                        </span>
                        <span class="text-secondary font-bold">{{ count($detail->percakapan) }} Pesan</span>
                    </div>

                    <div class="flex flex-col gap-space-sm max-h-[360px] overflow-y-auto pr-1">
                        @forelse ($detail->percakapan as $pesan)
                            <div @class([
                                'flex flex-col max-w-[85%]',
                                'items-start self-start' => ! $pesan['dariHumas'],
                                'items-end self-end' => $pesan['dariHumas'],
                            ])>
                                <span @class([
                                    'font-label-sm text-label-sm text-on-surface-variant mb-0.5',
                                    'ml-1' => ! $pesan['dariHumas'],
                                    'mr-1' => $pesan['dariHumas'],
                                ])>
                                    {{ $pesan['dariHumas'] ? 'Admin Humas' : $tiket->nama_lengkap }} &bull; {{ $pesan['waktu'] }}
                                </span>

                                <div @class([
                                    'p-space-sm rounded-lg font-body-sm text-body-sm',
                                    'rounded-tl-xs bg-surface-container text-on-surface' => ! $pesan['dariHumas'],
                                    'rounded-tr-xs bg-primary text-on-primary' => $pesan['dariHumas'],
                                ])>
                                    {{ $pesan['isi'] }}

                                    @if (count($pesan['lampiran']) > 0)
                                        <div class="mt-3 flex flex-col gap-2">

                                            @foreach ($pesan['lampiran'] as $berkas)

                                                {{-- Lampiran dibungkus gelembung yang sama seperti di halaman
                                                     pelapor supaya tetap ringkas. Foto dibungkus tautan supaya
                                                     bisa dibuka ukuran penuh di tab baru. --}}
                                                @if ($berkas['gambar'])
                                                    <a href="{{ $berkas['url'] }}" target="_blank" rel="noopener">
                                                        <img src="{{ $berkas['url'] }}" alt="Lampiran {{ $berkas['nama'] }}" class="w-full max-w-[240px] rounded-lg">
                                                    </a>
                                                @else
                                                    <a href="{{ $berkas['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-white/70 px-3 py-2 text-[11px] font-semibold text-ink">
                                                        <x-symbol nama="attach_file" class="text-[14px]" />
                                                        {{ $berkas['nama'] }}
                                                    </a>
                                                @endif

                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-body-sm text-on-surface-variant">
                                Belum ada pesan tambahan dari pelapor maupun balasan admin humas.
                            </p>
                        @endforelse
                    </div>

                    {{-- Form Balasan Admin: kolom chat yang sama dengan halaman
                         lacak pelapor, jadi keduanya tinggal dibaca sebagai
                         percakapan satu arah dua. --}}
                    <form
                        method="POST"
                        action="{{ route('admin.pengaduan.balas', $kode) }}"
                        enctype="multipart/form-data"
                        class="flex flex-col gap-space-sm bg-surface-container-low p-space-md rounded-lg"
                    >
                        @csrf

                        <div class="flex flex-col gap-space-xs">
                            <label for="isiBalasan" class="font-label-sm text-label-sm uppercase font-bold text-on-surface-variant">
                                Tulis Balasan untuk Pelapor
                            </label>

                            <textarea
                                id="isiBalasan"
                                name="isi"
                                rows="3"
                                placeholder="Tuliskan tanggapan atau informasi yang perlu disampaikan ke pelapor..."
                                class="w-full p-space-md rounded-lg bg-surface font-body-md text-body-md text-on-surface outline-none focus:bg-surface-container-lowest transition-all placeholder:text-outline border border-outline-variant/40"
                            >{{ old('isi') }}</textarea>

                            @error('isi')
                                <span class="font-label-sm text-label-sm text-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-space-md">
                            {{-- previousElementSibling menunjuk teks "Lampiran",
                                 bukan ikon x-symbol di sebelahnya, supaya ikon
                                 paperclip tidak hilang saat berkas dipilih. --}}
                            <label class="flex items-center gap-2 px-space-md py-2.5 rounded-lg bg-surface-container text-on-surface font-label-md text-label-md font-semibold cursor-pointer">
                                <x-symbol nama="attach_file" class="text-[16px]" />
                                <span>Lampiran</span>
                                <input
                                    type="file"
                                    name="lampiran[]"
                                    accept=".jpg,.jpeg,.png,.pdf"
                                    multiple
                                    class="sr-only"
                                    onchange="this.previousElementSibling.textContent = this.files.length ? this.files[0].name + (this.files.length > 1 ? ' + ' + (this.files.length - 1) + ' berkas lain' : '') : 'Lampiran'"
                                >
                            </label>

                            <span class="font-label-sm text-label-sm text-on-surface-variant">
                                Maksimal 5 berkas JPG, PNG, atau PDF, masing-masing 4 MB.
                            </span>
                        </div>

                        @error('lampiran')
                            <span class="font-label-sm text-label-sm text-error">{{ $message }}</span>
                        @enderror

                        @error('lampiran.*')
                            <span class="font-label-sm text-label-sm text-error">{{ $message }}</span>
                        @enderror

                        <div class="flex flex-wrap items-center justify-between gap-space-md">
                            <span class="font-label-sm text-label-sm text-on-surface-variant">
                                Balasan ini muncul di halaman lacak tiket milik pelapor.
                            </span>

                            <button
                                type="submit"
                                class="py-3 px-space-xl rounded-lg bg-primary text-on-primary font-label-lg text-label-lg font-bold flex items-center justify-center gap-2 transition-opacity hover:opacity-90"
                            >
                                <x-symbol nama="send" class="text-[18px]" />
                                Kirim Balasan
                            </button>
                        </div>
                    </form>
                    <!-- End of Form Balasan Admin -->
                </div>
                <!-- End of Tab 1 -->

                <!-- Tab 2: kanal disposisi ke unit, belum ada isinya -->
                <div class="hidden p-space-md flex flex-col gap-space-md" data-panel="unit">
                    <div class="flex items-center justify-between gap-space-sm pb-space-xs text-on-surface-variant font-label-sm text-label-sm bg-surface-container-low p-space-sm rounded-lg">
                        <span class="flex items-center gap-1 font-semibold text-on-surface">
                            <x-symbol nama="forum" class="text-[16px] text-secondary" />
                            Kanal Disposisi SIMRS: Humas &harr; Unit
                        </span>
                    </div>

                    <p class="text-body-sm text-on-surface-variant leading-relaxed">
                        Balasan unit tercatat sebagai pesan dengan peran admin pada tab percakapan pelapor. Kanal
                        disposisi dua arah yang terpisah belum dibangun, jadi tidak ada ruang obrolan khusus
                        dengan PIC unit di halaman ini.
                    </p>
                </div>
                <!-- End of Tab 2 -->
            </article>
            <!-- End of Kartu 4 -->

            <!-- Panel Tahap: status berjalan di kiri, tombol tujuan di kanan. -->
            <div class="lg:col-span-12 bg-surface-container-lowest rounded-lg shadow-sm p-space-md">
                <div class="flex flex-wrap items-center justify-between gap-space-md">
                    <div class="flex items-center gap-space-sm">
                        <div>
                            <span class="font-label-sm text-label-sm uppercase font-bold text-on-surface-variant">
                                Tahap Pengaduan: {{ $tiket->status->label() }}
                            </span>
                            <p class="font-body-sm text-body-sm text-on-surface">{{ $tiket->status->subStatus() }}</p>
                        </div>
                    </div>

                    {{-- Satu tombol untuk Diterima, dua tombol untuk Diproses,
                         satu tombol untuk Revisi, dan catatan saja kalau
                         tiketnya sudah selesai. --}}
                    <div class="flex flex-wrap items-center gap-space-md">
                        @forelse ($detail->tujuanTersedia() as $tujuan)

                            @php $tombol = $detail->tombolTujuan($tujuan); @endphp

                            <form method="POST" action="{{ route('admin.pengaduan.tahap', $kode) }}">
                                @csrf
                                <input type="hidden" name="tujuan" value="{{ $tujuan->value }}">

                                <button
                                    type="submit"
                                    class="py-3 px-space-xl rounded-lg {{ $tombol['warna'] }} font-label-lg text-label-lg font-bold flex items-center justify-center gap-2 transition-opacity hover:opacity-90"
                                >
                                    <x-symbol :nama="$tombol['ikon']" class="text-[18px]" />
                                    {{ $tombol['label'] }}
                                </button>
                            </form>
                        @empty
                            <span class="font-body-sm text-body-sm text-on-surface-variant">
                                {{ $tindakan['alasanTutup'] }}
                            </span>
                        @endforelse
                    </div>
                </div>

                @error('tujuan')
                    <span class="mt-space-sm block font-label-sm text-label-sm text-error">{{ $message }}</span>
                @enderror
            </div>
            <!-- End of Panel Tahap -->
        </div>
        <!-- End of Kolom Kerja -->
    </div>
@endsection

@push('scripts')
    {{-- Perpindahan tab pada kartu percakapan. --}}
    <script>
        (function () {
            const tombol = document.querySelectorAll('[data-tab]');
            const panel = document.querySelectorAll('[data-panel]');

            tombol.forEach(function (tombolTab) {
                tombolTab.addEventListener('click', function () {
                    const aktif = tombolTab.dataset.tab;

                    tombol.forEach(function (lain) {
                        const dipilih = lain === tombolTab;

                        lain.className = dipilih
                            ? 'flex-1 py-3 px-space-md rounded-lg font-title-sm text-title-sm font-bold flex items-center justify-center gap-1.5 bg-surface-container-lowest text-on-surface shadow-sm'
                            : 'flex-1 py-3 px-space-md rounded-lg font-title-sm text-title-sm font-semibold text-on-surface-variant hover:text-on-surface flex items-center justify-center gap-1.5';
                    });

                    panel.forEach(function (isi) {
                        isi.classList.toggle('hidden', isi.dataset.panel !== aktif);
                    });
                });
            });
        })();
    </script>
@endpush
