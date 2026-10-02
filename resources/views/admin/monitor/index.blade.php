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
                    <div class="bg-surface-container-lowest rounded-lg p-space-md shadow-sm flex flex-col gap-space-md">
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

                        <div class="flex flex-col gap-space-sm">
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
                                                {{-- Belum ada endpoint yang menghubungi unit, jadi
                                                     tombolnya nonaktif dan diberi penjelasan. --}}
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
                                <p class="font-body-sm text-body-sm text-outline text-center py-2">
                                    Tidak ada tiket pada kolom ini
                                </p>
                            @endforelse

                            @if ($kolom['sisa'] > 0)
                                <p class="text-center py-1">
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
                    <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">Permintaan Keputusan Humas</h2>
                </div>
                <span class="font-label-sm text-label-sm text-outline">Menunggu Tindakan Admin</span>
            </div>

            @if ($monitor->permintaan->isEmpty())
                <p class="font-body-md text-body-md text-on-surface-variant">
                    Tidak ada tiket yang menunggu kelengkapan pelapor saat ini.
                </p>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-space-md">
                    @foreach ($monitor->permintaan as $tiket)
                        <div class="p-space-md rounded-lg bg-tertiary-container/20 border-l-4 border-tertiary-fixed-dim flex flex-col gap-space-xs">
                            <div class="flex items-center justify-between gap-space-sm">
                                <span class="font-title-sm text-title-sm font-bold text-on-surface">{{ $tiket['kode'] }}</span>
                                <span class="font-label-sm text-label-sm text-on-tertiary-container font-semibold">
                                    Hari ke-{{ $tiket['hari'] }}
                                </span>
                            </div>

                            <span class="font-label-md text-label-md text-tertiary font-semibold">{{ $tiket['unit'] }}</span>
                            <span class="text-body-sm text-on-surface-variant font-medium">Pelapor: {{ $tiket['pelapor'] }}</span>
                            <p class="font-body-sm text-body-sm text-on-surface-variant line-clamp-2">{{ $tiket['subjek'] }}</p>

                            <div class="flex items-start gap-1 text-on-tertiary-container">
                                <x-symbol nama="help" class="text-[16px] shrink-0 mt-0.5" />
                                <span class="font-body-sm text-body-sm">{{ $tiket['jeda'] }}</span>
                            </div>

                            <div class="flex flex-wrap items-center gap-1 pt-1">
                                <a
                                    href="{{ $tiket['kembali'] }}"
                                    class="px-space-sm py-1.5 rounded-lg bg-primary-container text-on-primary font-label-sm text-label-sm font-bold hover:bg-primary transition-colors inline-flex items-center gap-1"
                                >
                                    <x-symbol nama="visibility" class="text-[14px]" />
                                    Buka Detail
                                </a>

                                {{-- Kembalikan ke Diproses memakai endpoint tahap yang
                                     sudah dipakai panel tahap pada halaman detail. --}}
                                <form action="{{ $tiket['aksi'] }}" method="post" class="contents">
                                    @csrf
                                    <input type="hidden" name="tujuan" value="{{ $tiket['tujuan'] }}">
                                    <button
                                        type="submit"
                                        class="px-space-sm py-1.5 rounded-lg bg-secondary text-on-secondary font-label-sm text-label-sm font-bold hover:bg-secondary/90 transition-colors inline-flex items-center gap-1"
                                    >
                                        <x-symbol nama="play_arrow" class="text-[14px]" />
                                        Kembalikan ke Diproses
                                    </button>
                                </form>

                                {{-- Kanal disposisi ke unit belum berisi apa pun, jadi
                                     mengirim dokumen dari sini tidak bisa dikerjakan. --}}
                                <span
                                    class="px-space-sm py-1.5 rounded-lg bg-surface-container-highest text-on-surface-variant font-label-sm text-label-sm font-semibold cursor-not-allowed select-none inline-flex items-center gap-1"
                                    title="Kanal disposisi ke unit belum dibangun, kirim lewat halaman detail tiket"
                                >
                                    <x-symbol nama="send" class="text-[14px]" />
                                    Kirim Dokumen ke Unit
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <!-- End of Panel Permintaan Keputusan -->

        <!-- Layout Dua Kolom: tabel eskalasi dan rapor kepatuhan unit. -->
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-space-lg items-start">
            <div class="xl:col-span-8 flex flex-col gap-space-md bg-surface-container-lowest p-space-lg rounded-lg shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm">
                    <div>
                        <div class="flex items-center gap-space-xs">
                            <x-symbol nama="crisis_alert" class="text-error text-[24px]" />
                            <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">
                                Daftar Eskalasi &amp; Kritis Batas {{ $monitor->hariInvestigasi }} Hari Kerja
                            </h2>
                        </div>
                        <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">
                            Prioritas tindakan eskalasi internal direksi untuk unit dengan respon tertunda.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="font-label-sm text-label-sm text-outline">Filter:</span>
                        @foreach ($monitor->pilihanSaring() as $nilai => $label)
                            @if ($monitor->eskalasi['saring'] === $nilai)
                                <span class="bg-surface-container-highest text-on-surface font-label-md font-label-md rounded px-2.5 py-1 font-semibold">
                                    {{ $label }}
                                </span>
                            @else
                                <a
                                    href="{{ $monitor->urlSaring($nilai) }}"
                                    class="bg-surface-container text-on-surface font-label-md font-label-md rounded px-2.5 py-1 hover:bg-surface-container-high transition-colors"
                                >
                                    {{ $label }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>

                @if ($monitor->eskalasi['baris'] === [])
                    <p class="font-body-md text-body-md text-on-surface-variant py-space-md text-center">
                        Tidak ada tiket yang cocok dengan pilihan saring ini.
                    </p>
                @else
                    <div class="overflow-x-auto w-full">
                        <table class="w-full text-left font-body-sm text-body-sm border-collapse">
                            <thead>
                                <tr class="bg-surface-container-low text-on-surface font-label-md text-label-md">
                                    <th class="py-3 px-space-md rounded-lg">ID &amp; Pelapor</th>
                                    <th class="py-3 px-space-md">Unit / Instalasi Terkait</th>
                                    <th class="py-3 px-space-md">Durasi Disposisi</th>
                                    <th class="py-3 px-space-md">Kendala</th>
                                    <th class="py-3 px-space-md text-right rounded-lg">Tindakan Humas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-container">
                                @foreach ($monitor->eskalasi['baris'] as $baris)
                                    <tr class="{{ $baris['lewat'] ? 'hover:bg-error-container/20' : 'hover:bg-surface-container/60' }} transition-colors">
                                        <td class="py-space-md px-space-md">
                                            <div class="flex flex-col">
                                                <span class="font-title-sm text-title-sm font-bold {{ $baris['nadaAngka'] }}">{{ $baris['kode'] }}</span>
                                                <span class="font-body-sm text-body-sm text-on-surface-variant">{{ $baris['pelapor'] }}</span>
                                                <span class="font-label-sm text-label-sm text-outline">{{ $baris['kategori'] }}</span>
                                            </div>
                                        </td>
                                        <td class="py-space-md px-space-md">
                                            <div class="flex flex-col">
                                                <span class="font-title-sm text-title-sm font-bold text-on-surface">{{ $baris['unit'] }}</span>
                                                <span class="font-body-sm text-body-sm text-on-surface-variant line-clamp-2">{{ $baris['subjek'] }}</span>
                                            </div>
                                        </td>
                                        <td class="py-space-md px-space-md">
                                            <div class="flex flex-col gap-1">
                                                <div class="flex items-center gap-1">
                                                    <span class="font-label-md text-label-md font-bold {{ $baris['nadaAngka'] }}">Hari ke-{{ $baris['hari'] }}</span>
                                                    <span class="font-label-sm text-label-sm font-bold px-1.5 py-0.2 rounded {{ $baris['nadaLencana'] }}">
                                                        {{ $baris['lencana'] }}
                                                    </span>
                                                </div>
                                                <div class="w-24 bg-surface-container rounded-full h-1.5 overflow-hidden">
                                                    <div class="{{ $baris['nadaAngka'] === 'text-error' ? 'bg-error' : 'bg-secondary' }} h-full rounded-full" style="width: {{ $baris['persen'] }}%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-space-md px-space-md max-w-[220px]">
                                            <p class="font-body-sm text-body-sm {{ $baris['lewat'] ? 'text-error font-medium' : 'text-on-surface-variant' }} leading-snug">
                                                {{ $baris['kendala'] }}
                                            </p>
                                        </td>
                                        <td class="py-space-md px-space-md text-right">
                                            <div class="flex flex-col items-end gap-1">
                                                @foreach ($baris['tombol'] as $tombol)
                                                    @if ($tombol['url'])
                                                        <a
                                                            href="{{ $tombol['url'] }}"
                                                            class="px-space-sm py-1.5 rounded-lg font-label-sm text-label-sm font-bold transition-all shadow-sm inline-flex items-center gap-1 whitespace-nowrap {{ $tombol['nada'] }}"
                                                        >
                                                            <x-symbol :nama="$tombol['ikon']" class="text-[16px]" />
                                                            {{ $tombol['label'] }}
                                                        </a>
                                                    @else
                                                        {{-- Teguran ke atasan butuh catatan dan notifikasi yang
                                                             belum ada di aplikasi ini, jadi tombolnya
                                                             nonaktif, bukan tautan yang tidak bekerja. --}}
                                                        <span
                                                            class="px-space-sm py-1.5 rounded-lg font-label-sm text-label-sm font-semibold cursor-not-allowed select-none inline-flex items-center gap-1 whitespace-nowrap {{ $tombol['nada'] }}"
                                                            title="Eskalasi ke pimpinan belum punya catatan dan notifikasi, tangani lewat halaman detail tiket"
                                                        >
                                                            <x-symbol :nama="$tombol['ikon']" class="text-[16px]" />
                                                            {{ $tombol['label'] }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Aturan siklus diambil dari config pengaduan supaya catatan
                     kaki ini tidak jadi sumber angka SLA yang terpisah. --}}
                <div class="p-space-sm rounded-lg bg-surface-container-low flex flex-col gap-space-sm">
                    <div class="flex items-start gap-space-xs text-on-surface-variant">
                        <x-symbol nama="info" class="text-[18px] text-tertiary mt-0.5 shrink-0" />
                        <div class="flex flex-col">
                            <span class="font-label-sm text-label-sm font-semibold">SLA Unit: {{ $monitor->hariInvestigasi }} Hari Kerja</span>

                            <ol class="flex flex-col gap-1 mt-1 list-decimal list-inside">
                                @foreach ($monitor->aturan as $aturan)
                                    <li class="font-body-sm text-body-sm">
                                        <span class="font-semibold text-on-surface">{{ $aturan['hari'] }} — {{ $aturan['judul'] }}</span>
                                        <span class="text-on-surface-variant">{{ $aturan['ket'] }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-space-sm">
                    <span class="font-body-sm text-body-sm text-outline">
                        Menampilkan {{ count($monitor->eskalasi['baris']) }} dari {{ $monitor->totalEskalasi() }} tiket terpantau.
                    </span>
                    <span class="font-label-sm text-label-sm text-outline whitespace-nowrap">Angka di-cache 60 detik</span>
                </div>
            </div>

            <div class="xl:col-span-4 flex flex-col gap-space-md bg-surface-container-lowest p-space-lg rounded-lg shadow-sm">
                <div class="flex items-center justify-between gap-space-sm">
                    <div class="flex items-center gap-space-xs">
                        <x-symbol nama="leaderboard" class="text-primary-container text-[24px]" />
                        <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">Rapor Kepatuhan Unit</h2>
                    </div>
                    <span class="font-label-sm text-label-sm text-outline">Bulan Berjalan</span>
                </div>

                <p class="font-body-sm text-body-sm text-on-surface-variant">
                    Persentase penyelesaian pengaduan yang tepat waktu terhadap SLA {{ $monitor->hariKerja }} hari kerja.
                </p>

                @if ($monitor->rapor === [])
                    <p class="font-body-md text-body-md text-on-surface-variant py-space-sm">
                        Belum ada unit yang punya pengaduan selesai, jadi rapor belum bisa dihitung.
                    </p>
                @else
                    <div class="flex flex-col gap-space-md mt-space-xs">
                        @foreach ($monitor->rapor as $unit)
                            <div class="flex flex-col gap-1.5 p-space-sm rounded-lg bg-surface-container-low">
                                <div class="flex items-center justify-between gap-space-sm">
                                    <div class="flex items-center gap-space-xs">
                                        <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $unit['nadaTitik'] }}" aria-hidden="true"></span>
                                        <span class="font-title-sm text-title-sm text-on-surface font-bold">{{ $unit['nama'] }}</span>
                                    </div>
                                    <span class="font-title-sm text-title-sm font-bold {{ $unit['nadaAngka'] }}">{{ number_format($unit['persen'], 1, ',', '.') }}%</span>
                                </div>

                                <div class="w-full bg-surface-container rounded-full h-2 overflow-hidden">
                                    <div class="{{ $unit['nadaBar'] }} h-full rounded-full transition-all duration-500" style="width: {{ $unit['persen'] }}%"></div>
                                </div>

                                <div class="flex justify-between gap-space-sm font-label-sm text-label-sm text-outline">
                                    <span>{{ $unit['status'] }}</span>
                                    <span class="{{ $unit['memenuhi'] ? 'text-secondary' : 'text-error' }} font-semibold">{{ $unit['keterangan'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="p-space-sm rounded-lg bg-primary-container text-on-primary flex items-start gap-space-sm mt-space-xs">
                    <x-symbol nama="verified_user" class="text-[20px] text-primary-fixed shrink-0" />
                    <div class="flex flex-col">
                        <span class="font-label-sm text-label-sm font-bold text-primary-fixed uppercase tracking-wider">Ambang Kemenkes No. 4/2018</span>
                        <span class="font-body-sm text-body-sm text-on-primary-container mt-0.5">
                            Unit dengan skor di bawah {{ $monitor->standarKepatuhan }}% ditandai untuk telaah Komite Mutu &amp; Keselamatan Pasien pada sidang berikutnya.
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Layout Dua Kolom -->

    </div>

@endsection