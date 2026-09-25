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

                    {{-- Riwayat tanggapan dua arah --}}
                    <div class="flex flex-col gap-6 rounded-xl bg-white p-6 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.1),0_2px_4px_-2px_rgba(0,0,0,0.1)]">
                        <div class="flex flex-wrap items-start justify-between gap-4 pb-1">
                            <div class="flex items-start gap-2">
                                <svg class="mt-1 h-5 w-5 shrink-0 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <div>
                                    <h3 class="text-xl font-semibold leading-7 text-ink">Riwayat Tanggapan Dua Arah</h3>
                                    <p class="text-[13px] leading-[18px] text-ink-muted">
                                        Seluruh percakapan dengan tim humas tercatat pada tiket ini.
                                    </p>
                                </div>
                            </div>

                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-brand-100 px-3 py-1">
                                <svg class="h-3.5 w-3.5 text-brand-500" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span class="text-[11px] font-semibold tracking-[0.33px] text-brand-500">Balasan Admin: {{ $tiket->pesan->where('peran', 'admin')->count() }}</span>
                            </span>
                        </div>

                        <div class="flex max-h-[640px] flex-col gap-4 overflow-y-auto pr-1">
                            <div class="mx-auto flex w-fit items-center gap-2 rounded-full bg-brand-50 px-4 py-1.5">
                                <svg class="h-3 w-3 text-ink-muted" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">
                                    Pengaduan diterima sistem pada {{ $tiket->created_at->format('d M Y, H:i') }}
                                </span>
                            </div>

                            @forelse ($tiket->pesan as $pesan)
                                @if ($pesan->dariAdmin())
                                    <div class="flex flex-col items-start gap-1 pr-10">
                                        <div class="flex items-center gap-1">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-800 text-[11px] font-bold tracking-[0.33px] text-white">H</span>
                                            <span class="text-xs font-bold tracking-[0.24px] text-brand-800">Tim Humas</span>
                                            <span class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">{{ $pesan->created_at->format('d M Y, H:i') }}</span>
                                        </div>

                                        <div class="max-w-[672px] rounded-tl-none rounded-2xl bg-chat-admin px-4 py-4 text-sm leading-[23px] text-ink shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                                            {{ $pesan->isi }}

                                            @if ($pesan->adaLampiran())
                                                @if ($pesan->lampiranGambar())
                                                    <img src="{{ $pesan->urlLampiran() }}" alt="Lampiran balasan admin" class="mt-3 w-full max-w-[280px] rounded-lg">
                                                @else
                                                    <a href="{{ $pesan->urlLampiran() }}" target="_blank" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-white/70 px-3 py-2 text-[11px] font-semibold text-ink">
                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        Unduh lampiran
                                                    </a>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="flex flex-col items-end gap-1 pl-10">
                                        <div class="flex items-center gap-1">
                                            <span class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">{{ $pesan->created_at->format('d M Y, H:i') }}</span>
                                            <span class="text-xs font-bold tracking-[0.24px] text-ink">Anda (Pelapor)</span>
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-700 text-[11px] font-medium tracking-[0.33px] text-white">{{ strtoupper(substr($tiket->nama_lengkap, 0, 1)) }}</span>
                                        </div>

                                        <div class="max-w-[672px] rounded-tr-none rounded-2xl bg-brand-800 px-4 py-4 text-sm leading-[23px] text-white shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                                            {{ $pesan->isi }}

                                            @if ($pesan->adaLampiran())
                                                @if ($pesan->lampiranGambar())
                                                    <img src="{{ $pesan->urlLampiran() }}" alt="Lampiran pesan Anda" class="mt-3 w-full max-w-[280px] rounded-lg">
                                                @else
                                                    <a href="{{ $pesan->urlLampiran() }}" target="_blank" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-[11px] font-semibold text-white">
                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        Unduh lampiran
                                                    </a>
                                                @endif
                                            @endif
                                        </div>

                                        <span class="flex items-center gap-2 pt-1 text-[11px] font-medium tracking-[0.33px] text-chat-accent">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="m3 11 18-8-8 18-2-8-8-2Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Terkirim
                                        </span>
                                    </div>
                                @endif
                            @empty
                                <p class="text-[13px] leading-[18px] text-ink-muted">
                                    Belum ada tanggapan. Sampaikan pertanyaan atau informasi tambahan di sini, admin humas akan membalas.
                                </p>
                            @endforelse
                        </div>

                        <form method="POST" action="{{ route('pengaduan.pesan', ['kode' => $tiket->kode_tiket]) }}" enctype="multipart/form-data" class="flex flex-col gap-4 rounded-xl bg-brand-section p-4">
                            @csrf

                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <h4 class="text-base font-bold leading-[22px] text-ink">Tulis Balasan atau Unggah Lampiran</h4>
                                </div>
                                <p class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">Balasan hanya terlihat oleh Anda dan admin humas.</p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <textarea
                                    name="isi"
                                    rows="4"
                                    placeholder="Tuliskan keterangan tambahan atau tanggapan Anda di sini..."
                                    class="w-full resize-none rounded-lg bg-white px-4 py-4 text-sm leading-[23px] text-ink shadow-[0_1px_2px_rgba(0,0,0,0.05)] placeholder:text-[#9CA3AF] focus:ring-2 focus:ring-brand-600 focus:outline-none"
                                >{{ old('isi') }}</textarea>

                                @error('isi')
                                    <p class="text-xs font-semibold text-alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border-2 border-dashed border-brand-200 bg-white p-4">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50">
                                        <svg class="h-5 w-5 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="m21.4 11.6-8.9 8.9a6 6 0 0 1-8.5-8.5l9.6-9.6a4 4 0 0 1 5.7 5.7l-9.6 9.6a2 2 0 0 1-2.8-2.8l8.9-8.9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                    <div>
                                        <p class="text-xs font-semibold tracking-[0.24px] text-ink">Unggah Lampiran</p>
                                        <p class="text-[13px] leading-[18px] text-ink-muted">Format JPG, PNG, atau PDF maksimal 5 MB.</p>
                                    </div>
                                </div>

                                <label class="cursor-pointer rounded-lg bg-brand-100 px-4 py-2 text-xs font-semibold tracking-[0.24px] text-brand-deep">
                                    Pilih File
                                    <input
                                        type="file"
                                        name="lampiran"
                                        accept=".jpg,.jpeg,.png,.pdf"
                                        class="sr-only"
                                        onchange="this.parentNode.textContent = this.files[0] ? this.files[0].name : 'Pilih File'"
                                    >
                                </label>
                            </div>

                            @error('lampiran')
                                <p class="text-xs font-semibold text-alert">{{ $message }}</p>
                            @enderror

                            <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                                <p class="flex items-center gap-1.5 text-[11px] font-medium tracking-[0.33px] text-ink-muted">
                                    <svg class="h-3.5 w-3.5 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="4" y="11" width="16" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/>
                                        <path d="M8 11V7a4 4 0 0 1 8 0v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Isi pesan dan lampiran hanya dapat dilihat oleh tim humas.
                                </p>

                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-brand-800 px-6 py-2.5 text-sm font-semibold text-white shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="m3 11 18-8-8 18-2-8-8-2Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Kirim Tanggapan
                                </button>
                            </div>
                        </form>

                        <div class="flex flex-col gap-1 pt-1">
                            <p class="text-[11px] font-bold uppercase tracking-[0.55px] text-brand-600">Catatan Disposisi Struktural Terbuka</p>

                            <div class="flex flex-wrap justify-center gap-1">
                                <div class="flex min-w-[248px] flex-1 flex-col gap-0.5 rounded-lg bg-brand-50 p-2">
                                    <span class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">{{ $tiket->created_at->format('d M Y, H:i') }}</span>
                                    <span class="pt-0.5 text-xs font-bold tracking-[0.24px] text-ink">Disposisi Kepala Humas</span>
                                    <span class="text-[13px] leading-[18px] text-brand-600">Diteruskan ke Tim Humas</span>
                                </div>

                                <div class="flex min-w-[248px] flex-1 flex-col gap-0.5 rounded-lg bg-brand-50 p-2">
                                    <span class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">{{ $tiket->updated_at->format('d M Y, H:i') }}</span>
                                    <span class="pt-0.5 text-xs font-bold tracking-[0.24px] text-ink">Tindak Lanjut</span>
                                    <span class="text-[13px] leading-[18px] text-brand-600">Status terakhir: {{ $tiket->status->label() }}</span>
                                </div>

                                <div class="flex min-w-[248px] flex-1 flex-col gap-0.5 rounded-lg bg-brand-50 p-2">
                                    <span class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">Status SLA</span>
                                    <span class="pt-0.5 text-xs font-bold tracking-[0.24px] text-brand-800">On Schedule (Zona Hijau)</span>
                                    <span class="text-[13px] leading-[18px] text-brand-600">Target tanggapan: {{ $tiket->created_at->copy()->addDays(12)->format('d M Y') }}</span>
                                </div>
                            </div>
                        </div>
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
