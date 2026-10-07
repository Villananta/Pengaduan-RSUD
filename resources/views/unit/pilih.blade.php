@extends('unit.layout')

@section('title', 'Pilih Unit Layanan')

@section('content')

    <div class="w-full px-margin py-space-lg flex flex-col gap-space-lg">

        <!-- Strip Judul: tujuan halaman pemilih unit. -->
        <div class="flex flex-col">
            <div class="flex items-center gap-space-xs text-secondary font-label-md text-label-md uppercase tracking-wider">
                <span class="w-2 h-2 rounded-full bg-secondary animate-ping" aria-hidden="true"></span>
                Pintu Masuk Dashboard Unit &bull; Mode Prototype
            </div>

            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight mt-0.5">
                Pilih Unit Layanan
            </h1>

            <p class="font-body-md text-body-md text-on-surface-variant max-w-4xl">
                Setiap unit punya dashboard sendiri berisi pengaduan yang ditugaskan kepadanya:
                ringkasan tahap tiket, posisi SLA, antrean yang mendesak, dan pengaduan terbaru.
                Pilih salah satu unit di bawah untuk membukanya.
            </p>
        </div>
        <!-- End of Strip Judul -->

        <!-- Daftar Unit -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-space-md">
            @forelse ($daftarUnit as $kartu)

                <div @class([
                    'rounded-lg p-space-md shadow-sm flex flex-col justify-between gap-space-sm',
                    'bg-surface-container-lowest' => $kartu->aktif,
                    'bg-surface-container-low opacity-70 cursor-not-allowed' => ! $kartu->aktif,
                ])>
                    <div class="flex items-start justify-between gap-space-sm">
                        <div class="flex flex-col">
                            <span class="font-label-sm text-label-sm font-bold uppercase tracking-wider text-secondary">
                                {{ $kartu->kategori->label() }}
                            </span>
                            <span class="font-title-md text-title-md font-bold text-on-surface mt-0.5">
                                {{ $kartu->nama }}
                            </span>
                        </div>

                        <span class="px-space-xs py-0.5 rounded-xl bg-primary-container text-on-primary font-label-sm text-label-sm font-bold">
                            {{ $kartu->kode }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-space-xs text-on-surface-variant font-body-sm text-body-sm">
                        <span class="flex items-center gap-1.5">
                            <x-symbol nama="badge" class="text-[15px] text-outline shrink-0" />
                            {{ $kartu->pic ?? 'PIC belum ditentukan' }}
                        </span>

                        <span class="flex items-center gap-1.5">
                            <x-symbol nama="schedule" class="text-[15px] text-outline shrink-0" />
                            Jam layanan {{ $kartu->jam_layanan }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-space-sm pt-space-xs border-t border-outline-variant/30">
                        <span class="font-body-sm text-body-sm text-on-surface-variant">
                            <strong class="text-on-surface">{{ number_format($kartu->beban_aktif, 0, ',', '.') }}</strong>
                            pengaduan aktif
                        </span>

                        @if ($kartu->aktif)
                            <a
                                href="{{ route('unit.dashboard', $kartu) }}"
                                class="px-space-sm py-1.5 rounded-sm bg-secondary text-on-secondary font-label-sm text-label-sm font-bold flex items-center gap-1 hover:bg-secondary/90 transition-colors"
                            >
                                <x-symbol nama="dashboard" class="text-[15px]" />
                                Buka Dashboard
                            </a>
                        @else
                            {{-- Tidak dipakai tautan palsu: unit yang non-aktif
                                 memang tidak punya dashboard yang boleh dibuka. --}}
                            <span
                                class="px-space-sm py-1.5 rounded-sm bg-surface-container-high text-outline font-label-sm text-label-sm font-bold flex items-center gap-1 cursor-not-allowed"
                                title="Unit tidak aktif, dashboardnya dinonaktifkan"
                                aria-disabled="true"
                            >
                                <x-symbol nama="block" class="text-[15px]" />
                                Tidak Aktif
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="sm:col-span-2 lg:col-span-3 rounded-sm bg-surface-container-low px-space-md py-space-lg text-center text-body-sm text-on-surface-variant">
                    Belum ada unit yang terdaftar. Tambahkan unit lewat konsol admin terlebih dulu.
                </p>
            @endforelse
        </div>
        <!-- End of Daftar Unit -->

    </div>
@endsection
