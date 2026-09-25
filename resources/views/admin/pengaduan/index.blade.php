@extends('admin.layout')

@section('title', 'Daftar Pengaduan')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold leading-8 text-ink">Daftar Pengaduan Masuk</h1>
            <p class="mt-1 text-[13px] leading-[18px] text-ink-muted">
                Kelola tahap penanganan dan balas pesan pelapor dari halaman detail tiket.
            </p>
        </div>
        <span class="rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold text-brand-800">
            {{ $daftar->total() }} pengaduan
        </span>
    </div>

    <div class="mt-5 flex flex-wrap gap-2 rounded-xl bg-brand-section p-2">
        <a
            href="{{ route('admin.pengaduan.index') }}"
            @class([
                'rounded-lg px-4 py-2 text-xs font-semibold',
                'bg-brand-800 text-white' => $status === null,
                'bg-brand-50 text-ink-muted' => $status !== null,
            ])
        >Semua</a>

        @foreach ($tahap as $item)
            <a
                href="{{ route('admin.pengaduan.index', ['status' => $item->value]) }}"
                @class([
                    'rounded-lg px-4 py-2 text-xs font-semibold',
                    'bg-brand-800 text-white' => $status === $item,
                    'bg-brand-50 text-ink-muted' => $status !== $item,
                ])
            >{{ $item->label() }}</a>
        @endforeach
    </div>

    <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
        <table class="w-full text-left text-sm">
            <thead class="bg-brand-50 text-[11px] uppercase tracking-[0.33px] text-ink-muted">
                <tr>
                    <th class="px-4 py-3 font-semibold">Nomor Tiket</th>
                    <th class="px-4 py-3 font-semibold">Pelapor</th>
                    <th class="px-4 py-3 font-semibold">Subjek</th>
                    <th class="px-4 py-3 font-semibold">Tahap</th>
                    <th class="px-4 py-3 font-semibold">Chat</th>
                    <th class="px-4 py-3 font-semibold">Masuk</th>
                    <th class="px-4 py-3 font-semibold"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftar as $item)
                    <tr class="border-t border-brand-200/40">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-brand-800">{{ $item->kode_tiket }}</td>
                        <td class="px-4 py-3 text-xs font-semibold text-ink">{{ $item->nama_lengkap }}</td>
                        <td class="px-4 py-3 text-xs text-ink-muted">{{ $item->subjek }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-3 py-1 text-[11px] font-semibold {{ $item->status->badge() }}">
                                {{ $item->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-ink-muted">{{ $item->pesan_count }} pesan</td>
                        <td class="px-4 py-3 text-xs text-ink-muted">{{ $item->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.pengaduan.show', $item->kode_tiket) }}" class="rounded-lg bg-brand-800 px-3 py-1.5 text-[11px] font-semibold text-white">
                                Tangani
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-xs text-ink-muted">Belum ada pengaduan masuk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $daftar->links() }}
    </div>
@endsection
