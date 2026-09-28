@extends('admin.layout')

@section('title', 'Daftar Pengaduan')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold leading-8 text-admin-ink">Daftar Pengaduan Masuk</h1>
            <p class="mt-1 text-[13px] leading-[18px] text-admin-ink-muted">
                Kelola tahap penanganan dan balas pesan pelapor dari halaman detail tiket.
            </p>
        </div>
        <span class="rounded-full bg-admin-success-soft px-3 py-1 text-xs font-semibold text-admin-success-strong">
            {{ $daftar->total() }} pengaduan
        </span>
    </div>

    <div class="mt-5 flex flex-wrap items-center gap-2">
        <a
            href="{{ route('admin.pengaduan.index', array_filter(['telaah' => $telaah ? 1 : null])) }}"
            @class([
                'rounded-lg px-4 py-2 text-xs font-semibold',
                'bg-admin-brand-800 text-white' => $status === null,
                'bg-white text-admin-ink-muted shadow-[0_1px_2px_rgba(0,0,0,0.05)]' => $status !== null,
            ])
        >Semua</a>

        @foreach ($tahap as $item)
            <a
                href="{{ route('admin.pengaduan.index', array_filter(['status' => $item->value, 'telaah' => $telaah ? 1 : null])) }}"
                @class([
                    'rounded-lg px-4 py-2 text-xs font-semibold',
                    'bg-admin-brand-800 text-white' => $status === $item,
                    'bg-white text-admin-ink-muted shadow-[0_1px_2px_rgba(0,0,0,0.05)]' => $status !== $item,
                ])
            >{{ $item->label() }}</a>
        @endforeach

        <a
            href="{{ route('admin.pengaduan.index', array_filter(['telaah' => 1])) }}"
            @class([
                'rounded-lg px-4 py-2 text-xs font-semibold',
                'bg-admin-danger-base text-white' => $telaah,
                'bg-white text-admin-danger-strong shadow-[0_1px_2px_rgba(0,0,0,0.05)]' => ! $telaah,
            ])
        >Telaah jawaban unit</a>
    </div>

    <form method="GET" action="{{ route('admin.pengaduan.index') }}" class="mt-4 flex flex-wrap items-center gap-2">
        @if ($status)
            <input type="hidden" name="status" value="{{ $status->value }}">
        @endif
        @if ($telaah)
            <input type="hidden" name="telaah" value="1">
        @endif

        <div class="relative">
            <x-icon nama="cari" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-admin-ink-subtle" />
            <input
                name="q"
                type="search"
                value="{{ $cari }}"
                placeholder="Cari nomor tiket, NRM, unit, atau kata kunci..."
                class="h-9 w-full rounded-lg border border-admin-border bg-white py-1 pl-9 pr-3 text-xs text-admin-ink placeholder:text-admin-ink-subtle focus:border-admin-brand-700 focus:outline-none focus:ring-1 focus:ring-admin-brand-700 sm:w-80"
            >
        </div>

        <button type="submit" class="h-9 rounded-lg bg-admin-brand-800 px-4 text-xs font-semibold text-white">Cari</button>

        @if ($cari !== '')
            <a href="{{ route('admin.pengaduan.index', array_filter(['status' => $status?->value, 'telaah' => $telaah ? 1 : null])) }}" class="text-xs font-semibold text-admin-ink-muted hover:underline">
                Reset
            </a>
        @endif
    </form>

    <div class="mt-5 overflow-x-auto rounded-lg bg-white shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
        <table class="w-full min-w-[880px] text-left text-sm">
            <thead class="bg-admin-surface-alt text-[11px] uppercase tracking-[0.33px] text-admin-ink-muted">
                <tr>
                    <th class="px-4 py-3 font-semibold">Nomor Tiket</th>
                    <th class="px-4 py-3 font-semibold">Pelapor</th>
                    <th class="px-4 py-3 font-semibold">Unit</th>
                    <th class="px-4 py-3 font-semibold">Subjek</th>
                    <th class="px-4 py-3 font-semibold">Tahap</th>
                    <th class="px-4 py-3 font-semibold">Chat</th>
                    <th class="px-4 py-3 font-semibold">Masuk</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftar as $item)
                    <tr class="border-t border-admin-border/40">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-admin-brand-800">{{ $item->kode_tiket }}</td>
                        <td class="px-4 py-3 text-xs font-semibold text-admin-ink">{{ $item->nama_lengkap }}</td>
                        <td class="px-4 py-3 text-xs text-admin-ink-muted">{{ $item->namaUnit() }}</td>
                        <td class="px-4 py-3 text-xs text-admin-ink-muted">{{ $item->subjek }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-3 py-1 text-[11px] font-semibold {{ $item->status->badgeAdmin() }}">
                                {{ $item->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-admin-ink-muted">{{ $item->pesan_count }} pesan</td>
                        <td class="px-4 py-3 text-xs text-admin-ink-muted">{{ $item->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.pengaduan.show', $item->kode_tiket) }}" class="rounded-lg bg-admin-brand-800 px-3 py-1.5 text-[11px] font-semibold text-white">
                                Tangani
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-xs text-admin-ink-muted">
                            @if ($cari !== '' || $telaah)
                            Tidak ada pengaduan yang cocok dengan filter ini.
                            @else
                            Belum ada pengaduan masuk.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $daftar->links() }}
    </div>
@endsection
