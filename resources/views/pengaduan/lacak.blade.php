@extends('layouts.guest')

@section('title', 'Lacak Status Tiket')

@section('content')
    @php
        // Tahap prosedur diambil dari config, daftar status memakai enum.
        $prosedur = config('pengaduan.prosedur');

        // Tahap yang sedang berjalan pada tiket yang dicari.
        $tahapSekarang = $tiket?->status->tahap();
    @endphp



    @if (session('sukses'))
        <div class="w-full bg-white px-8 pt-8">
            <p class="rounded-xl bg-brand-100 px-4 py-3 text-xs font-semibold text-brand-800">
                {{ session('sukses') }}
            </p>
        </div>
    @endif

    {{-- Pencarian tiket --}}
    <section class="w-full bg-brand-section px-8 py-10">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <nav class="flex items-center gap-2 text-xs text-ink-muted">
                <a href="{{ route('pengaduan.create') }}" class="font-semibold hover:text-brand-800">Beranda</a>
                <span>/</span>
                <span class="font-semibold text-ink">Lacak Status Tiket</span>
            </nav>
            <span class="flex items-center gap-2 rounded-full bg-white px-3 py-1 text-[11px] font-bold uppercase tracking-[0.6px] text-brand-800">
                <span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>
                Layanan Aktif 24 Jam
            </span>
        </div>

        <div class="mt-6 rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold leading-8 text-ink">Lacak Status Pengaduan</h1>
                    <p class="mt-1 text-[13px] leading-[18px] text-ink-muted">
                        Masukkan kode tiket untuk melihat kronologi dan jawaban dari tim pengaduan RSUD.
                    </p>
                </div>
                <span class="rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold text-brand-800">Data Terverifikasi</span>
            </div>

            <form method="GET" action="{{ route('pengaduan.lacak') }}" class="mt-5 flex flex-col gap-3 sm:flex-row">
                <input
                    type="text"
                    name="kode"
                    value="{{ $kode }}"
                    placeholder="Contoh: ADUAN-20260925-ABCDE"
                    class="flex-1 rounded-xl border border-brand-200 bg-brand-light px-4 py-3 text-sm text-ink placeholder-placeholder focus:border-brand-600 focus:outline-none"
                >
                <button type="submit" class="rounded-xl bg-brand-800 px-6 py-3 text-sm font-semibold text-white">
                    Cek Status Sekarang
                </button>
            </form>

            <p class="mt-3 text-xs text-ink-muted">
                Kode tiket tersedia pada halaman konfirmasi pengajuan dan pesan konfirmasi WhatsApp.
            </p>
        </div>
    </section>

    {{-- Empat pilar status, hanya tampil bila kode tiket sudah dimasukkan --}}
    @if ($tiket)
        <section class="w-full bg-brand-light px-8 py-4">
            <div class="flex flex-col gap-2">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-[11px] font-bold uppercase tracking-[0.55px] text-brand-600">Status Pengaduan</h2>
                    <p class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">Penyelesaian Rata-rata 6.8 Hari Kerja</p>
                </div>

                <div class="flex flex-wrap gap-1 rounded-xl bg-brand-section p-1.5 shadow-[inset_0_2px_4px_rgba(0,0,0,0.05)]">
                    @foreach ($tahap as $status)
                        <span
                            @class([
                                'flex min-w-56 flex-1 items-center gap-2 rounded-lg px-4 py-2',
                                'bg-brand-800 text-white' => $tiket->status === $status,
                                'bg-brand-50 text-ink-muted shadow-[0_1px_2px_rgba(0,0,0,0.05)]' => $tiket->status !== $status,
                            ])
                        >
                            {{-- Ikon tiap tahap --}}
                            @if ($status->diterima())
                                <svg viewBox="0 0 15 15" class="h-[15px] w-[15px] shrink-0" aria-hidden="true">
                                    <circle cx="7.5" cy="7.5" r="7.5" fill="currentColor" />
                                    <path d="M4 7.6 6.2 9.8 11 5" fill="none" stroke="#fff" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            @elseif ($status->diproses())
                                <svg viewBox="0 0 17 12" class="h-3 w-[17px] shrink-0" aria-hidden="true">
                                    <circle cx="8.5" cy="6" r="5.5" fill="none" stroke="currentColor" stroke-width="1.3" />
                                    <path d="M8.5 2.8v3.4l2.2 1.3" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
                                </svg>
                            @elseif ($status->perluAksi())
                                <svg viewBox="0 0 14 12" class="h-3 w-[14px] shrink-0" aria-hidden="true">
                                    <path d="M7 .6 13.2 11.4H.8L7 .6Z" fill="currentColor" />
                                    <path d="M7 4.1v3" fill="none" stroke="#fff" stroke-width="1.2" stroke-linecap="round" />
                                    <circle cx="7" cy="9.1" r=".8" fill="#fff" />
                                </svg>
                            @else
                                <svg viewBox="0 0 15 15" class="h-[15px] w-[15px] shrink-0" aria-hidden="true">
                                    <circle cx="7.5" cy="7.5" r="6" fill="none" stroke="currentColor" stroke-width="1.5" />
                                    <path d="M4.8 7.7 6.7 9.6 10.2 5.9" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            @endif

                            <span class="text-sm font-semibold">{{ $loop->iteration }}. {{ $status->label() }}</span>

                            @if ($status->perluAksi())
                                <span class="ml-auto rounded-full bg-alert px-2 py-0.5 text-[11px] font-bold tracking-[0.33px] text-white">Perlu Aksi</span>
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="w-full px-8 pb-10">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="flex flex-col gap-6 lg:col-span-2">
                @if ($tiket)
                    {{-- Pemberitahuan sesuai tahap pengaduan --}}
                    @php
                        $perluAksi = $tiket->status->perluAksi();
                        $tuntas = $tiket->status->selesai();
                    @endphp
                    <div @class([
                        'flex flex-wrap items-center justify-between gap-3 rounded-2xl p-5',
                        'bg-alert-light' => $perluAksi,
                        'bg-brand-700' => $tuntas,
                        'bg-brand-100' => ! $perluAksi && ! $tuntas,
                    ])>
                        <div>
                            <p @class([
                                'text-sm font-bold uppercase tracking-[0.33px]',
                                'text-alert' => $perluAksi,
                                'text-white' => $tuntas,
                                'text-brand-800' => ! $perluAksi && ! $tuntas,
                            ])>
                                @if ($perluAksi)
                                    Butuh Revisi / Kelengkapan Data
                                @elseif ($tuntas)
                                    Selesai / Tuntas
                                @elseif ($tiket->status->diproses())
                                    Sedang Diproses
                                @else
                                    Diterima
                                @endif
                            </p>
                            <h3 @class([
                                'mt-1 text-lg font-semibold leading-7',
                                'text-alert' => $perluAksi,
                                'text-white' => $tuntas,
                                'text-ink' => ! $perluAksi && ! $tuntas,
                            ])>
                                @if ($perluAksi)
                                    Tindakan Diperlukan: Unggah Bukti Tambahan
                                @elseif ($tuntas)
                                    Solusi &amp; Klarifikasi Resmi Diterbitkan
                                @elseif ($tiket->status->diproses())
                                    Laporan Sedang Diinvestigasi
                                @else
                                    Pengaduan Anda Diterima
                                @endif
                            </h3>
                            <p @class([
                                'mt-1 text-[13px] leading-[18px]',
                                'text-alert' => $perluAksi,
                                'text-white/90' => $tuntas,
                                'text-ink-muted' => ! $perluAksi && ! $tuntas,
                            ])>
                                @if ($perluAksi)
                                    Admin Humas memerlukan bukti pendukung agar audit internal dapat dilanjutkan.
                                @elseif ($tuntas)
                                    Surat tanggapan resmi sudah diterbitkan. Silakan tinjau hasilnya.
                                @elseif ($tiket->status->diproses())
                                    Berkas telah didisposisikan kepada Kepala Instalasi dan Komite Mutu Pelayanan.
                                @else
                                    Permintaan Anda sudah tercatat dan menunggu verifikasi petugas.
                                @endif
                            </p>
                        </div>
                        <a
                            href="tel:+62311500995"
                            @class([
                                'rounded-lg px-4 py-2 text-xs font-semibold',
                                'bg-alert text-white' => $perluAksi,
                                'bg-white text-brand-800' => ! $perluAksi,
                            ])
                        >
                            Hubungi Call Center
                        </a>
                    </div>

                    {{-- Detail tiket --}}
                    <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-brand-200/40 pb-4">
                            <div>
                                <p class="text-xs font-medium text-ink-muted">Nomor Tiket</p>
                                <p class="font-mono text-base font-bold text-brand-800">{{ $tiket->kode_tiket }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-ink-muted">
                                    {{ $tiket->kategori->label() }}
                                </span>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $tiket->status->badge() }}">
                                    {{ $tiket->status->label() }}
                                </span>
                            </div>
                        </div>

                        <h3 class="mt-4 text-lg font-semibold leading-7 text-ink">{{ $tiket->subjek }}</h3>

                        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
                            @foreach ([
                                'Nama Pelapor' => $tiket->nama_lengkap,
                                'No. Rekam Medis' => $tiket->nrm,
                                'No. WhatsApp' => $tiket->no_wa,
                                'Unit Terdampak' => $tiket->unit,
                                'Waktu Kejadian' => $tiket->waktu_kejadian->format('d M Y, H:i'),
                                'Tanggal Pengajuan' => $tiket->created_at->format('d M Y'),
                            ] as $label => $value)
                                <div>
                                    <p class="text-xs text-ink-muted">{{ $label }}</p>
                                    <p class="text-sm font-semibold text-ink">{{ $value }}</p>
                                </div>
                            @endforeach
                        </div>

                        {{-- Progres SLA --}}
                        <div class="mt-6 rounded-xl bg-brand-50 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-ink">Prosedur &amp; SLA 12 Hari Kerja</p>
                                <p class="text-xs font-bold text-brand-800">
                                    {{ $prosedur[$tahapSekarang]['hari'] ?? 'Hari ke-1 s.d 2' }}
                                </p>
                            </div>

                            <div class="mt-3 h-2.5 rounded-full bg-brand-200">
                                <div class="h-2.5 rounded-full bg-brand-800" style="width: {{ $tiket->status->persen() }}%"></div>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @foreach ($prosedur as $no => $langkah)
                                    <p @class([
                                        'text-[11px] leading-4',
                                        'font-bold text-brand-800' => $no <= $tahapSekarang,
                                        'text-ink-muted' => $no > $tahapSekarang,
                                    ])>
                                        {{ $langkah['hari'] }}<br>
                                        {{ $langkah['judul'] }}
                                    </p>
                                @endforeach
                            </div>
                        </div>

                        {{-- Isi aduan --}}
                        <div class="mt-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.6px] text-ink-muted">Isi Pengaduan</p>
                            <p class="mt-2 text-[13px] leading-5 text-ink">{{ $tiket->deskripsi }}</p>
                        </div>

                        @if (filled($tiket->lampiran))
                            <p class="mt-4 text-xs text-ink-muted">
                                Lampiran: {{ count($tiket->lampiran) }} berkas
                            </p>
                        @endif
                    </div>

                    {{-- Kolom chat dengan admin humas --}}
                    <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-base font-semibold text-ink">Kolom Chat Pengaduan</h3>
                            <span class="rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold text-ink-muted">
                                Admin humas: {{ $tiket->pesan->where('peran', 'admin')->count() }} balasan
                            </span>
                        </div>

                        <div class="mt-4 flex max-h-96 flex-col gap-3 overflow-y-auto rounded-xl bg-brand-light p-4">
                            @forelse ($tiket->pesan as $pesan)
                                <div class="flex flex-col {{ $pesan->dariAdmin() ? 'items-end' : 'items-start' }}">
                                    <p class="mb-1 text-[11px] font-semibold uppercase tracking-[0.33px] text-ink-muted">
                                        {{ $pesan->dariAdmin() ? 'Admin Humas' : 'Anda' }}
                                        &middot; {{ $pesan->created_at->format('d M Y H:i') }}
                                    </p>
                                    <div @class([
                                        'max-w-[85%] rounded-2xl px-4 py-2.5 text-[13px] leading-[19px]',
                                        'rounded-br-sm bg-brand-800 text-white' => $pesan->dariAdmin(),
                                        'rounded-bl-sm bg-white text-ink shadow-[0_1px_2px_rgba(0,0,0,0.05)]' => ! $pesan->dariAdmin(),
                                    ])>
                                        {{ $pesan->isi }}
                                    </div>
                                </div>
                            @empty
                                <p class="text-[13px] leading-[18px] text-ink-muted">
                                    Belum ada percakapan. Sampaikan pertanyaan atau informasi tambahan di sini, admin humas akan membalas.
                                </p>
                            @endforelse
                        </div>

                        <form method="POST" action="{{ route('pengaduan.pesan', ['kode' => $tiket->kode_tiket]) }}" class="mt-4 flex flex-col gap-3">
                            @csrf
                            <textarea
                                name="isi"
                                rows="3"
                                placeholder="Tuliskan pesan untuk admin humas..."
                                class="w-full rounded-xl border border-brand-200 bg-brand-light px-4 py-3 text-[13px] text-ink placeholder-placeholder focus:border-brand-600 focus:outline-none"
                            >{{ old('isi') }}</textarea>

                            @error('isi')
                                <p class="text-xs font-semibold text-alert">{{ $message }}</p>
                            @enderror

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="text-[11px] text-ink-muted">Status pengaduan hanya dapat diubah oleh admin humas.</p>
                                <button type="submit" class="rounded-xl bg-brand-800 px-5 py-2.5 text-xs font-semibold text-white">
                                    Kirim Pesan
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    {{-- Tiket hanya tampil bila kode-nya diketahui --}}
                    <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                        <h3 class="text-lg font-semibold text-ink">
                            {{ $kode === '' ? 'Masukkan Kode Tiket Anda' : 'Tiket Tidak Ditemukan' }}
                        </h3>
                        <p class="mt-1 text-[13px] leading-[18px] text-ink-muted">
                            @if ($kode === '')
                                Isi kolom kode tiket di atas. Hanya tiket dengan kode yang Anda masukkan yang dapat dilihat, jadi pengaduan orang lain tidak akan muncul di halaman ini.
                            @else
                                Tidak ada tiket dengan kode <span class="font-semibold text-ink">{{ $kode }}</span>. Pastikan kode disalin dengan benar dari halaman konfirmasi pengajuan atau pesan WhatsApp.
                            @endif
                        </p>
                    </div>
                @endif
            </div>

            {{-- Panel pendukung --}}
            <div class="flex flex-col gap-6">
                @if ($tiket)
                    {{-- Daftar tahap prosedur, hanya tampil bila kode tiket sudah dimasukkan --}}
                    <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                        <h3 class="text-base font-semibold text-ink">Alur Prosedur 12 Hari Kerja</h3>
                        <ol class="mt-4 flex flex-col gap-4">
                            @foreach ($prosedur as $no => $langkah)
                                <li class="flex gap-3" @class(['opacity-60' => $no > $tahapSekarang])>
                                    <div class="flex flex-col items-center">
                                        <span @class([
                                            'flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold',
                                            'bg-brand-800 text-white shadow-[0_0_0_4px_rgba(38,80,15,0.15)]' => $tahapSekarang === $no,
                                            'bg-brand-100 text-brand-800' => $tahapSekarang !== $no,
                                        ])>{{ $no + 1 }}</span>
                                        @if (! $loop->last)
                                            <span class="mt-1 h-full w-px bg-brand-200"></span>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-[11px] font-bold uppercase tracking-[0.6px] text-brand-800">{{ $langkah['hari'] }}</p>
                                            @if ($tahapSekarang === $no && $tiket->status->perluAksi())
                                                <span class="rounded-full bg-alert px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.33px] text-white">Perlu Aksi</span>
                                            @endif
                                        </div>
                                        <p @class([
                                            'text-sm font-semibold',
                                            'text-brand-800' => $tahapSekarang === $no,
                                            'text-ink' => $tahapSekarang !== $no,
                                        ])>{{ $langkah['judul'] }}</p>
                                        <p class="text-xs leading-[18px] text-ink-muted">{{ $langkah['ket'] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                    <h3 class="text-base font-semibold text-ink">Hak Pelapor &amp; Jaminan Pelayanan</h3>
                    <ul class="mt-4 flex flex-col gap-3">
                        @foreach ([
                            'Menyertakan bukti dan kronologi yang lengkap.',
                            'Mendapatkan jawaban tanpa dipungut biaya.',
                            'Menyampaikan keberatan atas hasil pemeriksaan.',
                        ] as $hak)
                            <li class="flex gap-2 text-[13px] leading-[18px] text-ink-muted">
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-700"></span>
                                {{ $hak }}
                            </li>
                        @endforeach
                    </ul>
                    <a href="#" class="mt-5 block rounded-lg bg-brand-800 py-2.5 text-center text-xs font-semibold text-white">
                        Ajukan Mediasi
                    </a>
                </div>

                <div class="rounded-2xl bg-brand-700 p-6 text-white">
                    <h3 class="text-base font-semibold">Hotline Bantuan Pengaduan</h3>
                    <p class="mt-2 text-[13px] leading-[18px] text-white/80">
                        Belum menemukan tiket? Petugas tersedia 24 jam.
                    </p>
                    <p class="mt-4 text-xl font-bold tracking-[-0.5px]">(031) 1500995</p>
                    <a href="tel:+62311500995" class="mt-4 block rounded-lg bg-white py-2.5 text-center text-xs font-semibold text-brand-800">
                        Telepon Sekarang
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
