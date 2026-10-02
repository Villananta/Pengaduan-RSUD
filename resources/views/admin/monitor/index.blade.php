@extends('admin.layout')

@section('title', 'Monitor Disposisi & SLA')

@section('content')

    <div class="w-full px-margin py-space-lg flex flex-col gap-space-lg">

        {{-- Hasil tindakan: muncul setelah admin mengembalikan tiket yang
             berstatus Perlu Revisi ke tahap Diproses. --}}
        @if (session('sukses'))
            <div
                role="status"
                class="w-full bg-secondary-container text-on-secondary-container rounded-lg px-space-md py-space-sm flex items-start gap-space-sm"
            >
                <x-symbol nama="check_circle" class="text-[20px] shrink-0" />
                <span class="text-body-sm">{{ session('sukses') }}</span>
            </div>
        @endif

        <!-- Strip Konteks: posisi halaman, aturan status, dan metrik cepat. -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md bg-surface-container-lowest p-space-lg rounded-lg shadow-sm">
            <div class="flex items-start gap-space-md">
                <span class="w-12 h-12 rounded-lg bg-primary-container text-on-primary flex items-center justify-center shadow-sm shrink-0">
                    <x-symbol nama="timelapse" class="text-[26px]" />
                </span>

                <div class="flex flex-col">
                    <div class="flex items-center gap-space-sm flex-wrap">
                        <span class="font-label-sm text-label-sm uppercase tracking-wider font-bold bg-secondary-container/60 text-on-secondary-container px-space-xs py-0.5 rounded-xl">
                            Sistem Pengawasan Internal Lapis 2
                        </span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-secondary animate-ping" aria-hidden="true"></span>
                            Live Tracking SIMRS &amp; Humas
                        </span>
                    </div>

                    <h1 class="font-headline-md text-headline-md text-on-surface font-bold tracking-tight mt-0.5">
                        Monitor Disposisi &amp; SLA Unit
                    </h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        Memastikan unit merespon telaah medis maksimal {{ $monitor->hariInvestigasi }} hari kerja sesuai Permenkes No. 4/2018.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-space-sm flex-wrap self-end lg:self-center">
                <div class="flex items-center gap-space-sm px-space-sm py-1 rounded-xl bg-tertiary-container/40 border border-tertiary-fixed-dim/40 text-tertiary-fixed">
                    <x-symbol nama="timer" class="text-[18px] text-tertiary-fixed-dim animate-pulse" />
                    <div class="flex flex-col text-left">
                        <span class="font-label-sm text-label-sm font-bold leading-none">Unit Terhubung</span>
                        <span class="font-body-sm text-body-sm text-on-primary-container leading-none mt-0.5">
                            {{ $monitor->unitTerhubung }}/{{ $monitor->unitTotal }} SIMRS
                        </span>
                    </div>
                </div>

                {{-- Sinkronisasi SIMRS memakai mesin pengingat di luar aplikasi
                     ini, jadi tombolnya tidak ditampilkan sebagai aksi yang
                     bisa dijalankan dari sini. --}}
                <a
                    href="{{ route('admin.monitor.index') }}"
                    class="px-space-md py-2 rounded-lg bg-secondary text-on-secondary font-label-lg text-label-lg hover:bg-secondary/90 transition-colors flex items-center gap-1.5 shadow-sm"
                    title="Muat ulang angka dan daftar disposisi unit"
                >
                    <x-symbol nama="refresh" class="text-[18px]" />
                    Refresh Data
                </a>
            </div>
        </div>
        <!-- End of Strip Konteks -->

        <!-- Empat Kartu Metrik: kepatuhan, tiket lewat, antrean racik, dan kecepatan. -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-space-md">
            @foreach ($monitor->kartu as $k)
                <div class="bg-surface-container-lowest p-space-md rounded-lg shadow-sm flex flex-col justify-between gap-space-sm">
                    <div class="flex items-center justify-between gap-space-sm">
                        <span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">{{ $k['label'] }}</span>
                        <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $k['nadaIkon'] }}">
                            <x-symbol :nama="$k['ikon']" class="text-[18px]" />
                        </span>
                    </div>

                    <div class="flex items-baseline gap-space-xs">
                        <span class="font-display-lg text-display-lg font-bold leading-none {{ $k['nadaAngka'] }}">
                            {{ $k['nilai'] === null ? '—' : number_format($k['nilai'], $k['desimal'], ',', '.') }}
                        </span>
                        @if ($k['nilai'] !== null)
                            <span class="font-title-sm text-title-sm text-on-surface-variant font-medium">{{ $k['satuan'] }}</span>
                        @endif
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

        <!-- Papan Disposisi: empat kolom status investigasi unit. -->
        <div class="flex flex-col gap-space-sm">
            <div class="flex items-center justify-between gap-space-sm">
                <div class="flex items-center gap-space-xs">
                    <x-symbol nama="view_kanban" class="text-secondary text-[22px]" />
                    <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">Ringkasan Status Disposisi Unit Aktif (Lapis 2)</h2>
                </div>

                <span class="font-label-sm text-label-sm text-on-surface-variant bg-surface-container px-space-sm py-1 rounded-xl">
                    Total Ditugaskan: {{ number_format($monitor->ditugaskan, 0, ',', '.') }} Pengaduan Aktif
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-space-md items-start">
                @foreach ($monitor->papan as $kolom)
                    <div class="bg-surface-container-lowest rounded-lg p-space-md shadow-sm flex flex-col gap-space-md h-full">
                        <div class="flex items-center justify-between pb-space-xs {{ $kolom['latar'] }} p-space-sm rounded-lg">
                            <div class="flex items-center gap-space-xs">
                                <span class="w-3 h-3 rounded-full {{ $kolom['titik'] }}" aria-hidden="true"></span>
                                <span class="font-title-sm text-title-sm font-bold {{ $kolom['nadaJudul'] }}">{{ $kolom['judul'] }}</span>
                            </div>
                            <span class="font-label-sm text-label-sm font-bold px-2 py-0.5 rounded-xl {{ $kolom['nadaAngka'] }}">
                                {{ number_format($kolom['total'], 0, ',', '.') }} Tiket
                            </span>
                        </div>

                        <p class="font-body-sm text-body-sm text-on-surface-variant flex items-start gap-1">
                            <x-symbol :nama="$kolom['ikon']" class="text-[16px] shrink-0 mt-0.5" />
                            {{ $kolom['ringkas'] }}
                        </p>

                        <div class="flex flex-col gap-space-sm flex-1">
                            @forelse ($kolom['tiket'] as $tiket)
                                <div class="p-space-sm rounded-lg bg-surface-container-low shadow-sm flex flex-col gap-space-xs relative overflow-hidden">
                                    <div class="absolute left-0 top-0 bottom-0 w-1.5 {{ $tiket['nadaGaris'] }}" aria-hidden="true"></div>

                                    <div class="flex items-center justify-between gap-space-sm pl-1">
                                        <span class="font-label-sm text-label-sm font-bold text-on-surface">{{ $tiket['kode'] }}</span>
                                        <span class="font-label-sm text-label-sm font-semibold px-1.5 py-0.5 rounded {{ $tiket['nadaPosisi'] }}">
                                            {{ $tiket['posisi'] }}
                                        </span>
                                    </div>

                                    <span class="font-title-sm text-title-sm text-on-surface font-semibold pl-1">{{ $tiket['unit'] }}</span>
                                    <span class="text-body-sm text-on-surface-variant font-medium pl-1">Pelapor: {{ $tiket['pelapor'] }}</span>
                                    <p class="font-body-sm text-body-sm text-on-surface-variant line-clamp-2 pl-1">{{ $tiket['subjek'] }}</p>

                                    @if ($tiket['kasus_berat'])
                                        <span class="inline-flex items-center gap-1 self-start font-label-sm text-label-sm font-bold bg-primary-fixed text-primary-container px-1.5 py-0.5 rounded">
                                            <x-symbol nama="shield" class="text-[12px]" />
                                            Kasus Berat, target digeser
                                        </span>
                                    @endif

                                    <div class="flex flex-wrap items-center justify-between gap-1 pt-1 pl-1">
                                        @foreach ($tiket['tombol'] as $tombol)
                                            @if ($tombol['url'])
                                                <a
                                                    href="{{ $tombol['url'] }}"
                                                    class="px-2 py-1 rounded text-label-sm font-label-sm font-bold transition-colors flex items-center gap-1 {{ $tombol['nada'] }}"
                                                >
                                                    <x-symbol :nama="$tombol['ikon']" class="text-[14px]" />
                                                    {{ $tombol['label'] }}
                                                </a>
                                            @else
                                                <span
                                                    class="px-2 py-1 rounded text-label-sm font-label-sm font-semibold cursor-not-allowed select-none flex items-center gap-1 {{ $tombol['nada'] }}"
                                                    title="Belum ada kanal otomatis ke unit, tangani lewat halaman detail tiket"
                                                >
                                                    <x-symbol :nama="$tombol['ikon']" class="text-[14px]" />
                                                    {{ $tombol['label'] }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="flex-1 flex items-center justify-center py-space-md">
                                    <p class="font-body-sm text-body-sm text-outline text-center">
                                        Tidak ada tiket pada kolom ini
                                    </p>
                                </div>
                            @endforelse

                            @if ($kolom['sisa'] > 0)
                                <p class="text-center py-1 mt-auto">
                                    <span class="font-body-sm text-body-sm text-outline">
                                        + {{ number_format($kolom['sisa'], 0, ',', '.') }} Tiket Lainnya pada Kolom Ini
                                    </span>
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <!-- End of Papan Disposisi -->

        <!-- Panel Permintaan Keputusan: tiket yang menunggu langkah humas. -->
        <div class="w-full rounded-lg bg-surface-container-lowest p-space-lg shadow-sm flex flex-col gap-space-md">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm">
                <div class="flex items-center gap-space-xs">
                    <x-symbol nama="contact_support" class="text-primary-container text-[24px]" />
    </div>

@endsection
