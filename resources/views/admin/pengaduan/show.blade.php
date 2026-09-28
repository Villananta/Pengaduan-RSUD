@extends('admin.layout')

@section('title', 'Tangani '.$pengaduan->kode_tiket)

@section('content')
    @if (session('sukses'))
        <p class="mb-5 rounded-lg bg-admin-success-soft px-4 py-3 text-xs font-semibold text-admin-success-strong">{{ session('sukses') }}</p>
    @endif

    <a href="{{ route('admin.pengaduan.index') }}" class="text-xs font-semibold text-admin-brand-800 hover:underline">&larr; Kembali ke daftar pengaduan</a>

    <div class="mt-4 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="flex flex-col gap-6 lg:col-span-2">
            {{-- Detail pengaduan --}}
            <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-brand-200/40 pb-4">
                    <div>
                        <p class="text-xs font-medium text-ink-muted">Nomor Tiket</p>
                        <p class="font-mono text-base font-bold text-brand-800">{{ $pengaduan->kode_tiket }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $pengaduan->status->badgeAdmin() }}">
                        {{ $pengaduan->status->label() }}
                    </span>
                </div>

                <h2 class="mt-4 text-lg font-semibold leading-7 text-ink">{{ $pengaduan->subjek }}</h2>

                <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @foreach ([
                        'Kategori' => $pengaduan->kategori->label(),
                        'Nama Pelapor' => $pengaduan->nama_lengkap,
                        'No. Rekam Medis' => $pengaduan->nrm,
                        'No. WhatsApp' => $pengaduan->no_wa,
                        'Email' => $pengaduan->email,
                        'Alamat' => $pengaduan->alamat,
                        'Unit Terdampak' => $pengaduan->namaUnit(),
                        'Waktu Kejadian' => $pengaduan->waktu_kejadian->format('d M Y, H:i'),
                        'Tanggal Pengajuan' => $pengaduan->created_at->format('d M Y'),
                    ] as $label => $value)
                        <div>
                            <p class="text-xs text-ink-muted">{{ $label }}</p>
                            <p class="text-sm font-semibold text-ink">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- Lampiran bukti yang diunggah pelapor --}}
                @if (filled($pengaduan->daftarLampiran()))
                    <div class="mt-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.6px] text-ink-muted">
                            Lampiran Bukti ({{ count($pengaduan->daftarLampiran()) }} berkas)
                        </p>
                        <ul class="mt-2 flex flex-col gap-2">
                            @foreach ($pengaduan->daftarLampiran() as $path)
                                <li>
                                    <a href="{{ $pengaduan->urlLampiran($path) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-admin-info-soft px-3 py-2 text-xs font-semibold text-ink hover:bg-admin-info-mid">
                                        <svg class="h-3.5 w-3.5 shrink-0 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        {{ $pengaduan->namaLampiran($path) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.6px] text-ink-muted">Isi Pengaduan</p>
                    <p class="mt-2 text-[13px] leading-5 text-ink">{{ $pengaduan->deskripsi }}</p>
                </div>
            </div>

            {{-- Chat dengan pelapor --}}
            <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                <h3 class="text-xl font-semibold leading-7 text-ink">Riwayat Tanggapan Dua Arah</h3>

                <div class="mt-4 flex max-h-[640px] flex-col gap-4 overflow-y-auto pr-1">
                    @forelse ($pengaduan->pesan as $pesan)
                        @if ($pesan->dariAdmin())
                            <div class="flex flex-col items-end gap-1 pl-10">
                                <div class="flex items-center gap-1">
                                    <span class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">{{ $pesan->created_at->format('d M Y, H:i') }}</span>
                                    <span class="text-xs font-bold tracking-[0.24px] text-ink">Tim Humas</span>
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-800 text-[11px] font-bold tracking-[0.33px] text-white">H</span>
                                </div>

                                <div class="max-w-[672px] rounded-tr-none rounded-2xl bg-brand-800 px-4 py-4 text-sm leading-[23px] text-white shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                                    {{ $pesan->isi }}

                                    @if ($pesan->adaLampiran())
                                        @if ($pesan->lampiranGambar())
                                            <img src="{{ $pesan->urlLampiran() }}" alt="Lampiran balasan" class="mt-3 w-full max-w-[280px] rounded-lg">
                                        @else
                                            <a href="{{ $pesan->urlLampiran() }}" target="_blank" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-[11px] font-semibold text-white">Unduh lampiran</a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="flex flex-col items-start gap-1 pr-10">
                                <div class="flex items-center gap-1">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-700 text-[11px] font-medium tracking-[0.33px] text-white">{{ Str::upper(Str::substr($pengaduan->nama_lengkap, 0, 1)) }}</span>
                                    <span class="text-xs font-bold tracking-[0.24px] text-brand-800">{{ $pengaduan->nama_lengkap }}</span>
                                    <span class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">{{ $pesan->created_at->format('d M Y, H:i') }}</span>
                                </div>

                                <div class="max-w-[672px] rounded-tl-none rounded-2xl bg-chat-admin px-4 py-4 text-sm leading-[23px] text-ink shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                                    {{ $pesan->isi }}

                                    @if ($pesan->adaLampiran())
                                        @if ($pesan->lampiranGambar())
                                            <img src="{{ $pesan->urlLampiran() }}" alt="Lampiran pesan pelapor" class="mt-3 w-full max-w-[280px] rounded-lg">
                                        @else
                                            <a href="{{ $pesan->urlLampiran() }}" target="_blank" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-white/70 px-3 py-2 text-[11px] font-semibold text-ink">Unduh lampiran</a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endif
                    @empty
                        <p class="text-[13px] leading-[18px] text-ink-muted">Belum ada pesan dari pelapor.</p>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('admin.pengaduan.balas', $pengaduan->kode_tiket) }}" enctype="multipart/form-data" class="mt-6 flex flex-col gap-4 rounded-xl bg-brand-section p-4">
                    @csrf
                    <textarea
                        name="isi"
                        rows="3"
                        placeholder="Tuliskan balasan untuk pelapor..."
                        class="w-full resize-none rounded-lg bg-white px-4 py-4 text-sm leading-[23px] text-ink shadow-[0_1px_2px_rgba(0,0,0,0.05)] placeholder:text-[#9CA3AF] focus:ring-2 focus:ring-brand-600 focus:outline-none"
                    >{{ old('isi') }}</textarea>

                    @error('isi')
                        <p class="text-xs font-semibold text-alert">{{ $message }}</p>
                    @enderror

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-brand-100 px-4 py-2 text-xs font-semibold tracking-[0.24px] text-brand-deep">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="m21.4 11.6-8.9 8.9a6 6 0 0 1-8.5-8.5l9.6-9.6a4 4 0 0 1 5.7 5.7l-9.6 9.6a2 2 0 0 1-2.8-2.8l8.9-8.9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Lampirkan Berkas
                            <input type="file" name="lampiran" accept=".jpg,.jpeg,.png,.pdf" class="sr-only">
                        </label>

                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-brand-800 px-6 py-2.5 text-sm font-semibold text-white shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                            Kirim Balasan
                        </button>
                    </div>

                    @error('lampiran')
                        <p class="text-xs font-semibold text-alert">{{ $message }}</p>
                    @enderror
                </form>
            </div>
        </div>

        <div class="flex flex-col gap-6">
            {{-- Ubah tahap pengaduan --}}
            <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                <h3 class="text-base font-semibold text-ink">Ubah Tahap Pengaduan</h3>
                <p class="mt-1 text-[13px] leading-[18px] text-ink-muted">
                    Tahap yang dipilih akan langsung terlihat oleh pelapor di halaman lacak tiket. Hanya tahap lanjutan yang sesuai alur yang dapat dipilih.
                </p>

                @error('status')
                    <p class="mt-3 rounded-lg bg-alert-light px-3 py-2 text-xs font-semibold text-alert">{{ $message }}</p>
                @enderror

                <form method="POST" action="{{ route('admin.pengaduan.status', $pengaduan->kode_tiket) }}" class="mt-4 flex flex-col gap-2">
                    @csrf
                    @foreach ($tahap as $item)
                        @php $boleh = $pengaduan->status->bisaBerpindahKe($item); @endphp
                        <button
                            type="submit"
                            name="status"
                            value="{{ $item->value }}"
                            @disabled(! $boleh)
                            @class([
                                'rounded-lg px-4 py-2 text-xs font-semibold',
                                'bg-admin-brand-800 text-white' => $pengaduan->status === $item,
                                'bg-admin-surface-alt text-ink-muted hover:bg-admin-info-soft' => $pengaduan->status !== $item && $boleh,
                                'cursor-not-allowed bg-admin-surface-alt text-ink-muted/50' => ! $boleh,
                            ])
                        >
                            {{ $loop->iteration }}. {{ $item->label() }}
                        </button>
                    @endforeach

                    <label for="catatan" class="mt-3 text-xs font-semibold text-ink">Catatan Perubahan (opsional)</label>
                    <input
                        id="catatan"
                        type="text"
                        name="catatan"
                        value="{{ old('catatan') }}"
                        maxlength="500"
                        placeholder="Contoh: Menunggu dokumen tambahan dari pelapor"
                        class="rounded-lg bg-admin-info-soft px-3 py-2 text-xs text-ink placeholder:text-ink-muted/50 focus:outline-none focus:ring-2 focus:ring-admin-brand-800"
                    >
                </form>
            </div>

            {{-- Jejak audit perubahan tahap --}}
            <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                <h3 class="text-base font-semibold text-ink">Riwayat Tahap</h3>
                <p class="mt-1 text-[13px] leading-[18px] text-ink-muted">Setiap perubahan tahap tercatat permanen pada tiket ini.</p>

                <ol class="mt-4 flex flex-col gap-3">
                    @forelse ($pengaduan->riwayatStatus as $riwayat)
                        <li class="flex flex-col gap-0.5 border-l-2 border-brand-200 pl-3">
                            <span class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">{{ $riwayat->created_at->format('d M Y, H:i') }}</span>
                            <span class="text-xs font-bold tracking-[0.24px] text-ink">
                                {{ $riwayat->dari?->label() ?? 'Pengaduan masuk' }} &rarr; {{ $riwayat->ke->label() }}
                            </span>
                            @if (filled($riwayat->catatan))
                                <span class="text-[13px] leading-[18px] text-ink-muted">{{ $riwayat->catatan }}</span>
                            @endif
                        </li>
                    @empty
                        <li class="text-[13px] leading-[18px] text-ink-muted">Belum ada riwayat perubahan tahap.</li>
                    @endforelse
                </ol>
            </div>

            {{-- Posisi prosedur --}}
            <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                <h3 class="text-base font-semibold text-ink">Alur Prosedur 12 Hari Kerja</h3>
                <p class="mt-1 text-[13px] leading-[18px] text-ink-muted">
                    Posisi saat ini: {{ $pengaduan->status->label() }} ({{ $pengaduan->progresPersen() }}% dari SLA).
                </p>
                <p @class(['mt-2 inline-flex w-fit rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.33px]', $zona->badgeAdmin()])>
                    {{ $zona->label() }} &middot; {{ $pengaduan->ringkasSla() }}
                </p>
                <ol class="mt-4 flex flex-col gap-3">
                    @foreach ($prosedur as $no => $langkah)
                        <li class="flex gap-3" @class(['opacity-60' => $no > $pengaduan->status->tahap()])>
                            <span @class([
                                'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                'bg-admin-brand-800 text-white' => $no === $pengaduan->status->tahap(),
                                'bg-admin-info-soft text-admin-brand-800' => $no !== $pengaduan->status->tahap(),
                            ])>{{ $no + 1 }}</span>
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.6px] text-admin-brand-800">{{ $langkah['hari'] }}</p>
                                <p class="text-sm font-semibold text-ink">{{ $langkah['judul'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
@endsection
