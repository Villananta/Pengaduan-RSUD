@extends('unit.layout')

@section('title', 'Dashboard '.$unit->nama)

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

        <!-- Banner Kondisi SLA Unit -->
        {{-- Banner didorong ke paling atas karena tiga kondisinya saling
             menutupi: begitu ada pelanggaran, kartu angka di bawahnya jadi
             penjelas, bukan berita utama. --}}
        @if ($dashboard->adaPelanggaran())
            <!-- Peringatan Kritis -->
            <div class="w-full rounded-lg bg-error-container/25 border border-error/40 p-space-md shadow-sm">
                <div class="flex items-start gap-space-sm">
                    <span class="w-10 h-10 rounded-full bg-error text-on-error flex items-center justify-center shrink-0 mt-0.5">
                        <x-symbol nama="warning" class="text-[22px]" />
                    </span>

                    <div class="flex flex-col gap-space-xs">
                        <h2 class="font-title-lg text-title-lg font-bold text-on-error-container">Peringatan: Pengaduan Melewati Batas</h2>
                        <p class="font-body-sm text-body-sm text-on-error-container/80">{{ $dashboard->kalimatSla() }}</p>
                    </div>
                </div>
            </div>
            <!-- End of Peringatan Kritis -->

        @elseif ($dashboard->adaPeringatan())
            <!-- Peringatan Mendek Batas -->
            <div class="w-full rounded-lg bg-tertiary-container/40 border border-tertiary-container p-space-md shadow-sm">
                <div class="flex items-start gap-space-sm">
                    <span class="w-10 h-10 rounded-full bg-tertiary-container text-on-tertiary-container flex items-center justify-center shrink-0 mt-0.5">
                        <x-symbol nama="timelapse" class="text-[22px]" />
                    </span>

                    <div class="flex flex-col gap-space-xs">
                        <h2 class="font-title-lg text-title-lg font-bold text-on-tertiary-container">Pengaduan Mendek Batas SLA</h2>
                        <p class="font-body-sm text-body-sm text-on-tertiary-container/80">{{ $dashboard->kalimatSla() }}</p>
                    </div>
                </div>
            </div>
            <!-- End of Peringatan Mendek Batas -->

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
        <!-- End of Banner Kondisi SLA Unit -->

        <!-- Bento Metrik: beban kerja unit dalam satu deret kartu. -->
        <div class="w-full grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-md">

            <!-- Kartu 1: total beban, sengaja lebar supaya jadi acuan angka lain. -->
            <div class="sm:col-span-2 rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col justify-between gap-space-md">
                <div class="flex items-center justify-between gap-space-sm">
                    <span class="font-label-md text-label-md text-on-surface-variant font-semibold uppercase tracking-wider">
                        Total Beban Pengaduan
                    </span>

                    <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-surface-container text-primary-container">
                        <x-symbol nama="inventory_2" class="text-[18px]" />
                    </span>
                </div>

                <div class="flex flex-wrap items-end justify-between gap-space-sm">
                    <div class="font-display-lg text-display-lg font-bold leading-none text-on-surface">
                        {{ number_format($dashboard->total, 0, ',', '.') }}
                    </div>

                    <span class="font-body-sm text-body-sm text-on-surface-variant">
                        {{ $dashboard->aktif }} masih aktif &bull; {{ $dashboard->selesai() }} selesai
                    </span>
                </div>
            </div>
            <!-- End of Kartu 1 -->

            <!-- Kartu 2: belum dibuka unit. -->
            <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col justify-between gap-space-md">
                <div class="flex items-center justify-between gap-space-sm">
                    <span class="font-label-md text-label-md text-on-surface-variant font-semibold uppercase tracking-wider">
                        Belum Dibuka
                    </span>

                    <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ StatusPengaduan::Diterima->warnaIkon() }}">
                        <x-symbol :nama="StatusPengaduan::Diterima->ikon()" class="text-[18px]" />
                    </span>
                </div>

                <div>
                    <div class="font-display-lg text-display-lg font-bold leading-none {{ StatusPengaduan::Diterima->warnaAngka() }}">
                        {{ number_format($dashboard->jumlah(StatusPengaduan::Diterima), 0, ',', '.') }}
                    </div>

                    <span class="font-body-sm text-body-sm text-on-surface-variant">
                        {{ StatusPengaduan::Diterima->subStatus() }}
                    </span>
                </div>
            </div>
            <!-- End of Kartu 2 -->

            <!-- Kartu 3: sedang diinvestigasi unit. -->
            <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col justify-between gap-space-md">
                <div class="flex items-center justify-between gap-space-sm">
                    <span class="font-label-md text-label-md text-on-surface-variant font-semibold uppercase tracking-wider">
                        Sedang Investigasi
                    </span>

                    <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ StatusPengaduan::Diproses->warnaIkon() }}">
                        <x-symbol :nama="StatusPengaduan::Diproses->ikon()" class="text-[18px]" />
                    </span>
                </div>

                <div>
                    <div class="font-display-lg text-display-lg font-bold leading-none {{ StatusPengaduan::Diproses->warnaAngka() }}">
                        {{ number_format($dashboard->jumlah(StatusPengaduan::Diproses), 0, ',', '.') }}
                    </div>

                    <span class="font-body-sm text-body-sm text-on-surface-variant">
                        {{ StatusPengaduan::Diproses->subStatus() }}
                    </span>
                </div>
            </div>
            <!-- End of Kartu 3 -->

            <!-- Kartu 4: butuh info tambahan dari pelapor. -->
            <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col justify-between gap-space-md">
                <div class="flex items-center justify-between gap-space-sm">
                    <span class="font-label-md text-label-md text-on-surface-variant font-semibold uppercase tracking-wider">
                        Butuh Info Tambahan
                    </span>

                    <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ StatusPengaduan::Revisi->warnaIkon() }}">
                        <x-symbol :nama="StatusPengaduan::Revisi->ikon()" class="text-[18px]" />
                    </span>
                </div>

                <div>
                    <div class="font-display-lg text-display-lg font-bold leading-none {{ StatusPengaduan::Revisi->warnaAngka() }}">
                        {{ number_format($dashboard->jumlah(StatusPengaduan::Revisi), 0, ',', '.') }}
                    </div>

                    <span class="font-body-sm text-body-sm text-on-surface-variant">
                        {{ StatusPengaduan::Revisi->subStatus() }}
                    </span>
                </div>
            </div>
            <!-- End of Kartu 4 -->

            <!-- Kartu 5: tuntas dan menunggu racikan humas. -->
            <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col justify-between gap-space-md">
                <div class="flex items-center justify-between gap-space-sm">
                    <span class="font-label-md text-label-md text-on-surface-variant font-semibold uppercase tracking-wider">
                        Terkirim ke Humas
                    </span>

                    <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ StatusPengaduan::Selesai->warnaIkon() }}">
                        <x-symbol :nama="StatusPengaduan::Selesai->ikon()" class="text-[18px]" />
                    </span>
                </div>

                <div>
                    <div class="font-display-lg text-display-lg font-bold leading-none {{ StatusPengaduan::Selesai->warnaAngka() }}">
                        {{ number_format($dashboard->jumlah(StatusPengaduan::Selesai), 0, ',', '.') }}
                    </div>

                    <span class="font-body-sm text-body-sm text-on-surface-variant">
                        {{ StatusPengaduan::Selesai->subStatus() }}
                    </span>
                </div>
            </div>
            <!-- End of Kartu 5 -->

            <!-- Kartu 6: rata-rata waktu investigasi unit. -->
            <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col justify-between gap-space-md">
                <div class="flex items-center justify-between gap-space-sm">
                    <span class="font-label-md text-label-md text-on-surface-variant font-semibold uppercase tracking-wider">
                        Rata-rata Waktu Investigasi
                    </span>

                    <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-secondary-container text-on-secondary-container">
                        <x-symbol nama="hourglass_top" class="text-[18px]" />
                    </span>
                </div>

                <div>
                    <div class="font-headline-sm text-headline-sm font-bold leading-none text-secondary">
                        @if ($dashboard->rataRataHariKerja !== null)
                            {{ number_format($dashboard->rataRataHariKerja, 1, ',', '.') }} hari kerja
                        @else
                            Belum ada data
                        @endif
                    </div>

                    <span class="font-body-sm text-body-sm text-on-surface-variant">
                        Ditutup {{ $dashboard->selesaiBulanIni }} tiket bulan berjalan
                    </span>
                </div>
            </div>
            <!-- End of Kartu 6 -->

        </div>
        <!-- End of Bento Metrik -->

        <!-- Ruang Kerja: antrean tindakan di kiri, keadaan unit di kanan. -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg">

            <!-- Kolom Kiri: antrean disposisi yang mendesak. -->
            <div class="lg:col-span-7 flex flex-col gap-space-lg">

                <!-- Pengaduan Butuh Tindakan Unit -->
                <div class="w-full rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col gap-space-md">
                    <div class="flex items-start gap-space-sm">
                        <span class="w-10 h-10 rounded-full bg-error-container text-on-error-container flex items-center justify-center shrink-0 mt-0.5">
                            <x-symbol nama="notification_important" class="text-[22px]" />
                        </span>

                        <div class="flex flex-col">
                            <h2 class="font-title-lg text-title-lg font-bold text-on-surface">Pengaduan Butuh Tindakan Unit</h2>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                Hanya pengaduan yang sudah memasuki dua hari kerja terakhir sebelum batas SLA
                                atau yang ditandai kasus berat.
                            </p>
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
                                        class="px-space-md py-2 rounded-sm bg-secondary text-on-secondary font-label-sm text-label-sm font-bold flex items-center gap-space-xs shadow-sm shrink-0"
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

                    <div class="text-on-surface-variant font-body-sm text-body-sm pt-space-xs">
                        <span>{{ $dashboard->ringkasMendesak() }}</span>
                    </div>
                </div>
                <!-- End of Pengaduan Butuh Tindakan Unit -->

            </div>
            <!-- End of Kolom Kiri -->

            <!-- Kolom Kanan: keadaan unit dan sisa angka telaah. -->
            <div class="lg:col-span-5 flex flex-col gap-space-lg">

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
                                {{ $unit->koneksi_simrs_terakhir !== null ? $unit->koneksi_simrs_terakhir->format('d M Y, H:i') : 'Belum pernah' }}
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

                <!-- Kartu Sisa Angka: telaah yang belum tuntas. -->
                <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col gap-space-md">
                    <div class="flex items-center gap-space-sm pb-space-sm border-b border-outline-variant/30">
                        <x-symbol nama="query_stats" class="text-secondary text-[22px]" />
                        <div>
                            <h2 class="font-title-lg text-title-lg font-bold text-on-surface">Sisa Telaah Unit</h2>

                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                Angka yang sama dengan banner di atas, dipisah per zona SLA.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-space-sm">
                        <div class="rounded-lg bg-surface-container-low p-space-sm flex flex-col">
                            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider font-bold">Masih Aktif</span>
                            <span class="font-headline-sm text-headline-sm font-bold text-on-surface">
                                {{ number_format($dashboard->aktif, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="rounded-lg bg-surface-container-low p-space-sm flex flex-col">
                            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider font-bold">Selesai Bulan Ini</span>
                            <span class="font-headline-sm text-headline-sm font-bold text-secondary">
                                {{ number_format($dashboard->selesaiBulanIni, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="rounded-lg bg-surface-container-low p-space-sm flex flex-col">
                            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider font-bold">Lewat Batas</span>
                            <span class="font-headline-sm text-headline-sm font-bold {{ $dashboard->terlambat > 0 ? 'text-error' : 'text-on-surface' }}">
                                {{ number_format($dashboard->terlambat, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="rounded-lg bg-surface-container-low p-space-sm flex flex-col">
                            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider font-bold">Mendek Batas</span>
                            <span class="font-headline-sm text-headline-sm font-bold {{ $dashboard->mendek > 0 ? 'text-on-tertiary-container' : 'text-on-surface' }}">
                                {{ number_format($dashboard->mendek, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    <span class="font-label-sm text-label-sm text-on-surface-variant">
                        Batas telaah unit {{ $dashboard->hariInvestigasi }} hari kerja, terpisah dari
                        SLA {{ $dashboard->hariKerja }} hari kerja yang dibaca pelapor.
                    </span>
                </div>
                <!-- End of Kartu Sisa Angka -->

            </div>
            <!-- End of Kolom Kanan -->
        </div>
        <!-- End of Ruang Kerja -->

        <!-- Pengaduan Terbaru Unit -->
        <div class="w-full rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col gap-space-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-xs pb-space-sm border-b border-outline-variant/30">
                <div class="flex items-center gap-space-xs">
                    <x-symbol nama="history" class="text-secondary text-[20px]" />
                    <h2 class="font-title-lg text-title-lg font-bold text-on-surface">Pengaduan Terbaru</h2>
                </div>

                <a
                    href="{{ $dashboard->urlLihatSemua() }}"
                    class="flex items-center gap-1.5 px-3 py-2 rounded-sm bg-secondary text-on-secondary font-label-md text-label-md hover:bg-secondary/90 transition-colors self-start md:self-auto"
                    title="Buka seluruh disposisi milik unit ini"
                >
                    <x-symbol nama="open_in_new" class="text-[16px]" />
                    Lihat Semua
                </a>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full min-w-[860px] text-left border-collapse">
                    <caption class="sr-only">
                        Pengaduan terbaru yang ditugaskan ke {{ $unit->namaLengkap() }}
                    </caption>

                    {{-- Lebar kolom dikunci di sini supaya isi tiap kolom tidak
                         saling-desak, sedangkan tinggi baris tetap mengikuti isi. --}}
                    <colgroup>
                        <col class="w-[15%]">
                        <col class="w-[26%]">
                        <col class="w-[18%]">
                        <col class="w-[17%]">
                        <col class="w-[14%]">
                        <col class="w-[10%]">
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
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-sm bg-primary-container text-on-primary font-label-sm text-label-sm font-bold"
                                    >
                                        <x-symbol nama="visibility" class="text-[14px]" />
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
