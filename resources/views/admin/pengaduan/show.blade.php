@extends('admin.layout')

@section('title', 'Tangani '.$pengaduan->kode_tiket)

@section('content')
    @if (session('sukses'))
        <p class="mb-5 rounded-xl bg-brand-100 px-4 py-3 text-xs font-semibold text-brand-800">{{ session('sukses') }}</p>
    @endif

    <a href="{{ route('admin.pengaduan.index') }}" class="text-xs font-semibold text-brand-800 hover:underline">&larr; Kembali ke daftar pengaduan</a>

    <div class="mt-4 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="flex flex-col gap-6 lg:col-span-2">
            {{-- Detail pengaduan --}}
            <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-brand-200/40 pb-4">
                    <div>
                        <p class="text-xs font-medium text-ink-muted">Nomor Tiket</p>
                        <p class="font-mono text-base font-bold text-brand-800">{{ $pengaduan->kode_tiket }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $pengaduan->status->badge() }}">
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
                        'Unit Terdampak' => $pengaduan->unit,
                        'Waktu Kejadian' => $pengaduan->waktu_kejadian->format('d M Y, H:i'),
                        'Tanggal Pengajuan' => $pengaduan->created_at->format('d M Y'),
                    ] as $label => $value)
                        <div>
                            <p class="text-xs text-ink-muted">{{ $label }}</p>
                            <p class="text-sm font-semibold text-ink">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.6px] text-ink-muted">Isi Pengaduan</p>
                    <p class="mt-2 text-[13px] leading-5 text-ink">{{ $pengaduan->deskripsi }}</p>
                </div>
            </div>

            {{-- Chat dengan pelapor --}}
            <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                <h3 class="text-base font-semibold text-ink">Chat dengan Pelapor</h3>

                <div class="mt-4 flex max-h-96 flex-col gap-3 overflow-y-auto rounded-xl bg-brand-light p-4">
                    @forelse ($pengaduan->pesan as $pesan)
                        <div class="flex flex-col {{ $pesan->dariAdmin() ? 'items-end' : 'items-start' }}">
                            <p class="mb-1 text-[11px] font-semibold uppercase tracking-[0.33px] text-ink-muted">
                                {{ $pesan->dariAdmin() ? 'Admin Humas' : $pengaduan->nama_lengkap }}
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
                        <p class="text-[13px] leading-[18px] text-ink-muted">Belum ada pesan dari pelapor.</p>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('admin.pengaduan.balas', $pengaduan->kode_tiket) }}" class="mt-4 flex flex-col gap-3">
                    @csrf
                    <textarea
                        name="isi"
                        rows="3"
                        placeholder="Tuliskan balasan untuk pelapor..."
                        class="w-full rounded-xl border border-brand-200 bg-brand-light px-4 py-3 text-[13px] text-ink placeholder-placeholder focus:border-brand-600 focus:outline-none"
                    >{{ old('isi') }}</textarea>

                    @error('isi')
                        <p class="text-xs font-semibold text-alert">{{ $message }}</p>
                    @enderror

                    <button type="submit" class="self-end rounded-xl bg-brand-800 px-5 py-2.5 text-xs font-semibold text-white">
                        Kirim Balasan
                    </button>
                </form>
            </div>
        </div>

        <div class="flex flex-col gap-6">
            {{-- Ubah tahap pengaduan --}}
            <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                <h3 class="text-base font-semibold text-ink">Ubah Tahap Pengaduan</h3>
                <p class="mt-1 text-[13px] leading-[18px] text-ink-muted">
                    Tahap yang dipilih akan langsung terlihat oleh pelapor di halaman lacak tiket.
                </p>

                <form method="POST" action="{{ route('admin.pengaduan.status', $pengaduan->kode_tiket) }}" class="mt-4 flex flex-col gap-2">
                    @csrf
                    @foreach ($tahap as $item)
                        <button
                            type="submit"
                            name="status"
                            value="{{ $item->value }}"
                            @class([
                                'rounded-lg px-4 py-2 text-xs font-semibold',
                                'bg-brand-800 text-white' => $pengaduan->status === $item,
                                'bg-brand-50 text-ink-muted hover:bg-brand-100' => $pengaduan->status !== $item,
                            ])
                        >
                            {{ $loop->iteration }}. {{ $item->label() }}
                        </button>
                    @endforeach
                </form>
            </div>

            {{-- Posisi prosedur --}}
            <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                <h3 class="text-base font-semibold text-ink">Alur Prosedur 12 Hari Kerja</h3>
                <p class="mt-1 text-[13px] leading-[18px] text-ink-muted">
                    Posisi saat ini: {{ $pengaduan->status->label() }} ({{ $pengaduan->status->persen() }}% dari SLA).
                </p>
                <ol class="mt-4 flex flex-col gap-3">
                    @foreach ($prosedur as $no => $langkah)
                        <li class="flex gap-3" @class(['opacity-60' => $no > $pengaduan->status->tahap()])>
                            <span @class([
                                'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                'bg-brand-800 text-white' => $no === $pengaduan->status->tahap(),
                                'bg-brand-100 text-brand-800' => $no !== $pengaduan->status->tahap(),
                            ])>{{ $no + 1 }}</span>
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.6px] text-brand-800">{{ $langkah['hari'] }}</p>
                                <p class="text-sm font-semibold text-ink">{{ $langkah['judul'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
@endsection
