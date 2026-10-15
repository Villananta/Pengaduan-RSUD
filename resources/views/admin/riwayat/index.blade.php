@extends('admin.layout')

@section('title', 'Riwayat Aksi Admin Humas')

@use('App\Enums\StatusPengaduan')
@use('App\Support\RiwayatAdmin')

@section('content')

    <div class="w-full px-margin py-space-md flex flex-col gap-space-lg">

        <!-- Strip Konteks -->
        <div class="flex flex-col">
            <div class="flex items-center gap-space-xs text-secondary font-label-md text-label-md uppercase tracking-wider">
                <span class="w-2 h-2 rounded-full bg-secondary animate-ping" aria-hidden="true"></span>
                Audit Trail &harr; Riwayat Aksi Admin
            </div>

            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight mt-0.5">
                Riwayat Aksi Admin Humas
            </h1>

            <p class="font-body-md text-body-md text-on-surface-variant max-w-4xl">
                Jejak audit lengkap seluruh perubahan tahap pengaduan, penandaan kasus berat,
                dan aksi administratif yang tertulis oleh sistem.
            </p>
        </div>
        <!-- End of Strip Konteks -->

        <!-- Kartu Statistik Ringkas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-space-md">
            @foreach ([
                ['eyebrow' => 'Total Riwayat', 'nilai' => number_format($statistik['total_riwayat'], 0, ',', '.'), 'ikon' => 'history', 'warna_ikon' => 'bg-surface-container text-primary-container', 'warna_angka' => 'text-on-surface', 'satuan' => 'Baris', 'catatan' => 'Semua jejak audit'],
                ['eyebrow' => 'Pengaduan Unik', 'nilai' => number_format($statistik['pengaduan_unik'], 0, ',', '.'), 'ikon' => 'assignment', 'warna_ikon' => 'bg-secondary-container text-on-secondary-container', 'warna_angka' => 'text-secondary', 'satuan' => 'Tiket', 'catatan' => 'Tiket yang punya riwayat'],
                ['eyebrow' => 'Pindah Tahap', 'nilai' => number_format($statistik['perubahan_tahap'], 0, ',', '.'), 'ikon' => 'swap_vert', 'warna_ikon' => 'bg-brand-100 text-brand-800', 'warna_angka' => 'text-brand-800', 'satuan' => 'Kali', 'catatan' => 'Perpindahan status'],
                ['eyebrow' => 'Kasus Berat', 'nilai' => number_format($statistik['kasus_berat'], 0, ',', '.'), 'ikon' => 'warning', 'warna_ikon' => 'bg-error-container text-on-error-container', 'warna_angka' => 'text-error', 'satuan' => 'Kali', 'catatan' => 'Aktifkan/nonaktifkan'],
                ['eyebrow' => 'Dengan Admin', 'nilai' => number_format($statistik['dengan_admin'], 0, ',', '.'), 'ikon' => 'verified_user', 'warna_ikon' => 'bg-surface-container text-on-tertiary-container', 'warna_angka' => 'text-on-tertiary-container', 'satuan' => 'Baris', 'catatan' => 'Sudah tercatat admin'],
            ] as $kartu)
                <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col justify-between gap-space-sm">
                    <div class="flex items-start justify-between gap-space-sm">
                        <div class="flex flex-col">
                            <span class="font-label-sm text-label-sm font-bold uppercase tracking-wider text-on-surface-variant">{{ $kartu['eyebrow'] }}</span>
                            <span class="font-title-sm text-title-sm text-on-surface font-semibold mt-0.5">{{ $kartu['nilai'] }} <span class="font-body-sm text-body-sm font-normal">{{ $kartu['satuan'] }}</span></span>
                        </div>
                        <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $kartu['warna_ikon'] }}">
                            <x-symbol :nama="$kartu['ikon']" class="text-[18px]" />
                        </span>
                    </div>
                    <p class="text-[11px] text-on-surface-variant leading-tight">{{ $kartu['catatan'] }}</p>
                    <div class="font-headline-lg text-headline-lg font-bold leading-none {{ $kartu['warna_angka'] }}">{{ $kartu['nilai'] }}</div>
                </div>
            @endforeach
        </div>
        <!-- End of Kartu Statistik -->

        <!-- Filter & Pencarian -->
        <form method="GET" action="{{ route('admin.riwayat.index') }}" class="flex flex-col gap-space-md bg-surface-container-lowest p-space-md rounded-lg shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-end gap-space-md">
                <div class="lg:col-span-2 flex flex-col gap-space-sm">
                    <label for="cari" class="font-label-sm text-label-sm font-bold text-on-surface">Pencarian</label>
                    <div class="relative">
                        <x-symbol nama="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]" />
                        <input
                            type="text"
                            id="cari"
                            name="cari"
                            value="{{ $riwayat->filterAktif['cari'] ?? '' }}"
                            placeholder="Kode tiket, nama pelapor, NRM..."
                            class="w-full pl-10 pr-3 py-2.5 rounded-sm bg-surface-container text-on-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                    </div>
                </div>

                <div class="lg:col-span-2 flex flex-col gap-space-sm">
                    <label for="tanggal_mulai" class="font-label-sm text-label-sm font-bold text-on-surface">Tanggal Mulai</label>
                    <input
                        type="date"
                        id="tanggal_mulai"
                        name="tanggal_mulai"
                        value="{{ $riwayat->filterAktif['tanggal_mulai'] ?? '' }}"
                        class="w-full py-2.5 px-3 rounded-sm bg-surface-container text-on-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-secondary/40"
                    >
                </div>

                <div class="lg:col-span-2 flex flex-col gap-space-sm">
                    <label for="tanggal_selesai" class="font-label-sm text-label-sm font-bold text-on-surface">Tanggal Selesai</label>
                    <input
                        type="date"
                        id="tanggal_selesai"
                        name="tanggal_selesai"
                        value="{{ $riwayat->filterAktif['tanggal_selesai'] ?? '' }}"
                        class="w-full py-2.5 px-3 rounded-sm bg-surface-container text-on-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-secondary/40"
                    >
                </div>

                <div class="flex flex-col gap-space-sm">
                    <label for="dari" class="font-label-sm text-label-sm font-bold text-on-surface">Status Asal</label>
                    <div class="relative">
                        <select
                            id="dari"
                            name="dari"
                            class="w-full pl-3 pr-8 py-2.5 rounded-sm bg-surface-container text-on-surface font-body-sm text-body-sm appearance-none focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                            <option value="">Semua Status</option>
                            @foreach ($pilihanStatus as $value => $label)
                                <option value="{{ $value }}" @selected($riwayat->filterAktif['dari'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-symbol nama="expand_more" class="absolute right-3 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none" />
                    </div>
                </div>

                <div class="flex flex-col gap-space-sm">
                    <label for="ke" class="font-label-sm text-label-sm font-bold text-on-surface">Status Tujuan</label>
                    <div class="relative">
                        <select
                            id="ke"
                            name="ke"
                            class="w-full pl-3 pr-8 py-2.5 rounded-sm bg-surface-container text-on-surface font-body-sm text-body-sm appearance-none focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                            <option value="">Semua Status</option>
                            @foreach ($pilihanStatus as $value => $label)
                                <option value="{{ $value }}" @selected($riwayat->filterAktif['ke'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-symbol nama="expand_more" class="absolute right-3 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none" />
                    </div>
                </div>

                <div class="flex flex-col gap-space-sm">
                    <label for="kasus_berat" class="font-label-sm text-label-sm font-bold text-on-surface">Jenis Aksi</label>
                    <div class="relative">
                        <select
                            id="kasus_berat"
                            name="kasus_berat"
                            class="w-full pl-3 pr-8 py-2.5 rounded-sm bg-surface-container text-on-surface font-body-sm text-body-sm appearance-none focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                            <option value="">Semua Jenis</option>
                            <option value="1" @selected($riwayat->filterAktif['kasus_berat'] === '1')>Kasus Berat</option>
                            <option value="0" @selected($riwayat->filterAktif['kasus_berat'] === '0')>Pindah Tahap / Lainnya</option>
                        </select>
                        <x-symbol nama="expand_more" class="absolute right-3 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none" />
                    </div>
                </div>

                <div class="flex flex-col gap-space-sm">
                    <label for="ada_admin" class="font-label-sm text-label-sm font-bold text-on-surface">Admin</label>
                    <div class="relative">
                        <select
                            id="ada_admin"
                            name="ada_admin"
                            class="w-full pl-3 pr-8 py-2.5 rounded-sm bg-surface-container text-on-surface font-body-sm text-body-sm appearance-none focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                            <option value="">Semua</option>
                            <option value="1" @selected($riwayat->filterAktif['ada_admin'] === '1')>Punya Admin</option>
                            <option value="0" @selected($riwayat->filterAktif['ada_admin'] === '0')>Belum Punya Admin</option>
                        </select>
                        <x-symbol nama="expand_more" class="absolute right-3 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none" />
                    </div>
                </div>

                <div class="flex flex-col gap-space-sm">
                    <label for="per_halaman" class="font-label-sm text-label-sm font-bold text-on-surface">Baris per Halaman</label>
                    <select
                        id="per_halaman"
                        name="per_halaman"
                        onchange="this.form.submit()"
                        class="w-full py-2.5 px-3 rounded-sm bg-surface-container text-on-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-secondary/40"
                    >
                        @foreach ([10, 25, 50, 100] as $ukuran)
                            <option value="{{ $ukuran }}" @selected(($riwayat->filterAktif['per_halaman'] ?? 25) === $ukuran)>{{ $ukuran }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="px-2 py-1 rounded-sm bg-surface-container-high text-on-surface font-label-sm text-label-sm">Terapkan</button></noscript>
                </div>

                <div class="flex flex-col gap-space-sm lg:self-end">
                    <button
                        type="submit"
                        class="w-full lg:w-auto py-2.5 px-4 rounded-sm bg-primary text-on-primary font-label-md text-label-md font-bold flex items-center justify-center gap-1.5 hover:bg-primary/90 transition-colors"
                    >
                        <x-symbol nama="filter_list" class="text-[18px]" />
                        Terapkan
                    </button>

                    @if (! empty($riwayat->filterAktif))
                        <a
                            href="{{ route('admin.riwayat.index') }}"
                            class="w-full lg:w-auto py-2.5 px-4 rounded-sm bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-bold flex items-center justify-center gap-1.5 transition-colors"
                        >
                            <x-symbol nama="close" class="text-[18px]" />
                            Hapus Filter
                        </a>
                    @endif
                </div>
            </div>
        </form>
        <!-- End of Filter & Pencarian -->

        <!-- Tabel Riwayat -->
        <div class="w-full bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden flex flex-col">
            <div class="overflow-x-auto w-full">
                <table class="w-full min-w-[1200px] text-left border-collapse">
                    <caption class="sr-only">
                        Riwayat aksi admin humas pada pengaduan
                    </caption>

                    <colgroup>
                        <col class="w-[8%]">
                        <col class="w-[10%]">
                        <col class="w-[10%]">
                        <col class="w-[10%]">
                        <col class="w-[10%]">
                        <col class="w-[10%]">
                        <col class="w-[12%]">
                        <col class="w-[12%]">
                        <col class="w-[10%]">
                        <col class="w-[8%]">
                    </colgroup>

                    <thead>
                        <tr class="bg-surface-container text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider border-b border-outline-variant/20">
                            <th scope="col" class="py-space-sm px-space-md">Waktu</th>
                            <th scope="col" class="py-space-sm px-space-md">Jenis Aksi</th>
                            <th scope="col" class="py-space-sm px-space-md">No. Tiket</th>
                            <th scope="col" class="py-space-sm px-space-md">Pelapor / NRM</th>
                            <th scope="col" class="py-space-sm px-space-md">Unit</th>
                            <th scope="col" class="py-space-sm px-space-md">Dari &rarr; Ke</th>
                            <th scope="col" class="py-space-sm px-space-md">Catatan</th>
                            <th scope="col" class="py-space-sm px-space-md">Admin</th>
                            <th scope="col" class="py-space-sm px-space-md">Tiket</th>
                            <th scope="col" class="py-space-sm px-space-md">ID</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-outline-variant/20 text-body-sm text-on-surface">
                        @forelse ($riwayat->riwayat as $item)
                            @php $baris = $riwayat->baris($item); @endphp

                            <tr @class([
                                'align-top transition-colors',
                                'bg-surface-container-low/50' => $loop->odd,
                                'hover:bg-surface-container-low' => true,
                            ])>
                                <td class="py-space-sm px-space-md align-top whitespace-nowrap">
                                    <span class="font-mono font-body-xs text-body-xs text-on-surface-variant">{{ $baris['waktu'] }} WIB</span>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-xl {{ $baris['label_jenis'] }} font-label-sm text-label-sm font-bold w-fit">
                                        <x-symbol :nama="$baris['ikon_jenis']" class="text-[12px]" />
                                        {{ $baris['jenis'] }}
                                    </span>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <a href="{{ $baris['url_tiket'] }}" class="font-title-sm text-title-sm font-bold text-on-surface hover:text-secondary transition-colors">
                                        {{ $baris['kode_tiket'] }}
                                    </a>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <div class="flex flex-col gap-1">
                                        <span class="font-title-sm text-title-sm font-semibold text-on-surface">{{ $baris['pelapor'] }}</span>
                                        <div class="flex items-center gap-1 text-on-surface-variant font-label-md text-label-md">
                                            <x-symbol nama="badge" class="text-[14px] text-outline" />
                                            <span class="font-mono font-body-xs text-body-xs">NRM: {{ $baris['nrm'] }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <span class="truncate block max-w-[180px] font-body-sm text-body-sm text-on-surface">{{ $baris['unit'] }}</span>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <div class="flex items-center gap-1.5 font-label-sm text-label-sm">
                                        <span class="text-on-surface-variant">{{ $baris['dari'] }}</span>
                                        <x-symbol nama="arrow_forward" class="text-[12px] text-outline" />
                                        <span class="font-bold text-on-surface">{{ $baris['ke'] }}</span>
                                    </div>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    <span class="truncate block max-w-[200px] text-on-surface-variant font-body-sm text-body-sm">{{ $baris['catatan'] }}</span>
                                </td>

                                <td class="py-space-sm px-space-md align-top">
                                    @if (str_starts_with($baris['admin'], '—'))
                                        <span class="text-outline font-body-sm text-body-sm">{{ $baris['admin'] }}</span>
                                    @else
                                        <span class="font-body-sm text-body-sm text-on-surface font-medium">{{ $baris['admin'] }}</span>
                                    @endif
                                </td>

                                <td class="py-space-sm px-space-md align-top text-center">
                                    <a
                                        href="{{ $baris['url_tiket'] }}"
                                        class="inline-flex items-center justify-center gap-1 px-2 py-1 rounded-sm bg-primary-container text-on-primary font-label-sm text-label-sm font-bold hover:bg-primary transition-colors"
                                        title="Buka detail tiket"
                                    >
                                        <x-symbol nama="visibility" class="text-[14px]" />
                                    </a>
                                </td>

                                <td class="py-space-sm px-space-md align-top text-center font-mono font-body-xs text-body-xs text-outline">
                                    #{{ $baris['id'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-space-md py-space-xl text-center">
                                    <p class="font-title-sm text-title-sm font-semibold text-on-surface">Tidak ada riwayat yang cocok</p>
                                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">
                                        @if (! empty($riwayat->filterAktif))
                                            Ubah filter atau hapus filter untuk melihat riwayat lain.
                                        @else
                                            Belum ada riwayat aksi yang tercatat.
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Footer: Paginasi -->
            <div class="bg-surface-container-lowest px-space-md py-space-sm flex flex-col md:flex-row items-center justify-between gap-space-md">
                <div class="flex items-center gap-space-md text-body-sm text-on-surface-variant flex-wrap">
                    <form method="GET" action="{{ route('admin.riwayat.index') }}" class="flex items-center gap-2">
                        @foreach ($riwayat->filterAktif as $nama => $nilai)
                            @if ($nama !== 'per_halaman')
                                <input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">
                            @endif
                        @endforeach

                        <label for="per_halaman_footer" class="font-label-sm text-label-sm">Baris per halaman:</label>
                        <select
                            id="per_halaman_footer"
                            name="per_halaman"
                            onchange="this.form.submit()"
                            class="px-2 py-1 rounded-sm bg-surface-container text-on-surface font-label-md text-label-md focus:outline-none"
                        >
                            @foreach ([10, 25, 50, 100] as $ukuran)
                                <option value="{{ $ukuran }}" @selected(($riwayat->filterAktif['per_halaman'] ?? 25) === $ukuran)>{{ $ukuran }}</option>
                            @endforeach
                        </select>
                        <noscript><button type="submit" class="px-2 py-1 rounded-sm bg-surface-container-high text-on-surface font-label-sm text-label-sm">Terapkan</button></noscript>
                    </form>

                    <span>
                        Menampilkan <strong>{{ $riwayat->riwayat->firstItem() ?? 0 }} &ndash; {{ $riwayat->riwayat->lastItem() ?? 0 }}</strong>
                        dari <strong>{{ number_format($riwayat->riwayat->total(), 0, ',', '.') }}</strong> riwayat
                    </span>
                </div>

                @if ($riwayat->riwayat->lastPage() > 1)
                    <nav class="flex items-center gap-1" aria-label="Navigasi halaman riwayat">
                        @if ($riwayat->riwayat->onFirstPage())
                            <span class="p-1.5 rounded-sm bg-surface-container text-outline opacity-40" aria-hidden="true">
                                <x-symbol nama="first_page" class="text-[20px]" />
                            </span>
                            <span class="p-1.5 rounded-sm bg-surface-container text-outline opacity-40" aria-hidden="true">
                                <x-symbol nama="chevron_left" class="text-[20px]" />
                            </span>
                        @else
                            <a href="{{ $riwayat->riwayat->url(1) }}" class="p-1.5 rounded-sm bg-surface-container text-outline hover:text-on-surface transition-colors" aria-label="Halaman pertama">
                                <x-symbol nama="first_page" class="text-[20px]" />
                            </a>
                            <a href="{{ $riwayat->riwayat->previousPageUrl() }}" class="p-1.5 rounded-sm bg-surface-container text-outline hover:text-on-surface transition-colors" rel="prev" aria-label="Halaman sebelumnya">
                                <x-symbol nama="chevron_left" class="text-[20px]" />
                            </a>
                        @endif

                        @for ($halaman = max(1, $riwayat->riwayat->currentPage() - 2); $halaman <= min($riwayat->riwayat->lastPage(), $riwayat->riwayat->currentPage() + 2); $halaman++)
                            @if ($halaman === $riwayat->riwayat->currentPage())
                                <span
                                    class="w-8 h-8 rounded-sm bg-primary-container text-on-primary font-label-md text-label-md font-bold flex items-center justify-center"
                                    aria-current="page"
                                >{{ $halaman }}</span>
                            @else
                                <a
                                    href="{{ $riwayat->riwayat->url($halaman) }}"
                                    class="w-8 h-8 rounded-sm bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-medium flex items-center justify-center transition-colors"
                                >{{ $halaman }}</a>
                            @endif
                        @endfor

                        @if ($riwayat->riwayat->hasMorePages())
                            <span class="px-1 text-outline" aria-hidden="true">...</span>
                            <a href="{{ $riwayat->riwayat->nextPageUrl() }}" class="p-1.5 rounded-sm bg-surface-container text-on-surface hover:bg-surface-container-high transition-colors" rel="next" aria-label="Halaman berikutnya">
                                <x-symbol nama="chevron_right" class="text-[20px]" />
                            </a>
                            <a href="{{ $riwayat->riwayat->url($riwayat->riwayat->lastPage()) }}" class="p-1.5 rounded-sm bg-surface-container text-on-surface hover:bg-surface-container-high transition-colors" aria-label="Halaman terakhir">
                                <x-symbol nama="last_page" class="text-[20px]" />
                            </a>
                        @endif
                    </nav>
                @endif
            </div>
            <!-- End of Footer Tabel -->
        </div>
        <!-- End of Tabel Riwayat -->

    </div>
@endsection