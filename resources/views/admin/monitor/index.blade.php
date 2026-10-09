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

        <!-- Strip Konteks: posisi halaman dan aturan tenggat investigasi. -->
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
                {{-- Kartu "Unit Terhubung X/Y SIMRS" sengaja tidak ditampilkan.
                     Angkanya berasal dari heartbeat yang belum pernah ditulis
                     aplikasi ini, jadi begitu integrasi SIMRS datang kartu ini
                     tinggal dipasang kembali. --}}
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

        <!-- Papan Disposisi: empat kolom status investigasi unit. -->
        {{-- Kartu metrik sengaja tidak digandakan di sini. Kepatuhan, tiket
             lewat batas, antrean racik, dan kecepatan sudah tersaji di
             dasbor utama, jadi angkanya cukup punya satu sumber saja. --}}
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
                    <div data-monitor-kolom class="bg-surface-container-lowest rounded-lg p-space-md shadow-sm flex flex-col gap-space-md h-full">

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
                            @forelse ($kolom['tiket'] as $i => $tiket)
                                {{-- Kartu di atas batas tetap dirender supaya admin bisa
                                     membukanya di tempat; class hidden hanya menyembunyikan. --}}
                                <div class="p-space-sm rounded-lg bg-surface-container-low shadow-sm flex flex-col gap-space-xs relative overflow-hidden {{ $i >= $kolom['batas'] ? 'hidden monitor-ekstra' : '' }}">

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
                                                    title="Tautan belum tersedia untuk tiket ini"
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
                                <button
                                    type="button"
                                    class="text-center py-1 mt-auto rounded-sm hover:bg-surface-container transition-colors"
                                    data-monitor-toggle
                                    aria-expanded="false"
                                >
                                    <span class="font-body-sm text-body-sm text-outline monitor-toggle-label">
                                        + {{ number_format($kolom['sisa'], 0, ',', '.') }} Tiket Lainnya pada Kolom Ini
                                    </span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <!-- End of Papan Disposisi -->

    </div>

@endsection

{{-- Penanda kolom yang menyembunyikan tiket sisanya sampai admin menekan
     "+ N Tiket Lainnya". Tiap tombol hanya membuka kolomnya sendiri supaya
     satu kolom panjang tidak ikut memanjangkan kolom lain. --}}
@push('scripts')
    <script>
        document.querySelectorAll('[data-monitor-toggle]').forEach(function (tombol) {
            var kolom = tombol.closest('[data-monitor-kolom]');
            var label = tombol.querySelector('.monitor-toggle-label');
            var teksBuka = label.textContent.trim();

            tombol.addEventListener('click', function () {
                var terbuka = tombol.getAttribute('aria-expanded') === 'true';

                kolom.querySelectorAll('.monitor-ekstra').forEach(function (kartu) {
                    kartu.classList.toggle('hidden', terbuka);
                });

                tombol.setAttribute('aria-expanded', String(!terbuka));
                label.textContent = terbuka ? teksBuka : 'Sembunyikan Tiket';
            });
        });
    </script>
@endpush
