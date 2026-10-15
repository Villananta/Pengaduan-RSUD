{{-- Dropdown Notifikasi Unit (Partial) --}}
@if ($unit !== null && isset($dashboard))
    @php
        $jumlahNotif = $dashboard->jumlahNotifikasiBelumDibaca() ?? 0;
        $adaNudge = $dashboard->adaNudgeBelumDibaca() ?? false;
        $notifikasi = $dashboard->notifikasiTerbaru(10);
    @endphp

    <div class="relative ml-space-md" id="notifikasi-wrapper">
        <button
            type="button"
            id="btn-notifikasi"
            class="relative p-2 rounded-full text-ink-muted hover:text-ink hover:bg-surface-container transition-colors"
            aria-label="Notifikasi"
            aria-expanded="false"
            aria-haspopup="true"
        >
            <x-symbol nama="notifications" class="text-[24px]" />
            @if ($jumlahNotif > 0)
                <span
                    id="badge-notifikasi"
                    class="absolute -top-0.5 -right-0.5 w-5 h-5 min-w-5 rounded-full bg-error text-on-error text-[10px] font-bold flex items-center justify-center animate-pulse"
                    aria-label="{{ $jumlahNotif }} notifikasi belum dibaca"
                >
                    {{ $jumlahNotif > 9 ? '9+' : $jumlahNotif }}
                </span>
            @endif
            @if ($adaNudge)
                <span
                    class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-warning ring-2 ring-white"
                    aria-label="Ada follow up (nudge) dari admin humas"
                ></span>
            @endif
        </button>

        {{-- Dropdown Notifikasi --}}
        <div
            id="dropdown-notifikasi"
            class="hidden absolute right-0 top-full mt-2 w-96 max-h-[500px] overflow-y-auto bg-white rounded-lg shadow-lg border border-outline-variant z-50"
            role="menu"
            aria-orientation="vertical"
        >
            <div class="p-3 border-b border-outline-variant flex items-center justify-between">
                <h3 class="font-title-sm text-title-sm font-bold text-ink">Notifikasi</h3>
                @if ($jumlahNotif > 0)
                    <form method="POST" action="{{ route('unit.notifikasi.tandai-semua-dibaca', $unit) }}" class="inline">
                        @csrf
                        <button
                            type="submit"
                            class="text-label-sm text-secondary hover:text-secondary/80 font-semibold transition-colors"
                        >
                            Tandai semua dibaca
                        </button>
                    </form>
                @endif
            </div>

            <div id="daftar-notifikasi" class="max-h-[400px] overflow-y-auto">
                @if ($notifikasi->isEmpty())
                    <div class="p-4 text-center text-ink-muted text-body-sm">
                        <x-symbol nama="notifications_none" class="text-[32px] mx-auto mb-2 block" />
                        Tidak ada notifikasi baru
                    </div>
                @else
                    <div class="divide-y divide-outline-variant">
                        @foreach ($notifikasi as $notif)
                            @php
                                $data = $notif->data;
                                $type = $data['type'] ?? 'unknown';
                                $title = $data['title'] ?? 'Notifikasi';
                                $message = $data['message'] ?? '';
                                $kodeTiket = $data['kode_tiket'] ?? null;
                                $url = $data['url'] ?? null;
                                $icon = match ($type) {
                                    'pengaduan_nudge_unit' => 'notifications_active',
                                    'pengaduan_sla_mendekati' => 'timelapse',
                                    'pengaduan_sla_lewat' => 'warning',
                                    'pengaduan_jawaban_unit' => 'reply',
                                    'pengaduan_disposisi' => 'forward_to_inbox',
                                    'pengaduan_tahap_berubah' => 'swap_vert',
                                    'pengaduan_kasus_berat' => 'warning',
                                    default => 'notifications',
                                };
                                $badgeClass = match ($type) {
                                    'pengaduan_nudge_unit' => 'bg-warning text-on-warning',
                                    'pengaduan_sla_lewat' => 'bg-error-container text-on-error-container',
                                    'pengaduan_sla_mendekati' => 'bg-tertiary-container text-on-tertiary-container',
                                    'pengaduan_jawaban_unit' => 'bg-secondary-container text-on-secondary-container',
                                    'pengaduan_disposisi' => 'bg-primary-container text-on-primary-container',
                                    default => 'bg-surface-container-highest text-on-surface-variant',
                                };
                            @endphp
                            <div class="p-3 hover:bg-surface-container-low transition-colors cursor-pointer notif-item"
                                data-notif-id="{{ $notif->id }}"
                                data-notif-url="{{ $url }}"
                                data-notif-type="{{ $type }}"
                                role="menuitem"
                                tabindex="0"
                            >
                                <div class="flex items-start gap-3">
                                    <span class="w-8 h-8 rounded-full {{ $badgeClass }} flex items-center justify-center shrink-0 flex-shrink-0">
                                        <x-symbol :nama="$icon" class="text-[18px]" />
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <h4 class="font-title-sm text-title-sm font-bold text-ink truncate">{{ $title }}</h4>
                                            <span class="text-[11px] text-ink-muted whitespace-nowrap shrink-0">{{ $notif->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-body-sm text-ink-muted mt-0.5 line-clamp-2">{{ $message }}</p>
                                        @if ($kodeTiket)
                                            <div class="mt-1.5 flex items-center gap-1.5">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-brand-100 text-brand-800 font-mono font-body-xs text-body-xs font-bold">
                                                    <x-symbol nama="confirmation_number" class="text-[10px]" />
                                                    {{ $kodeTiket }}
                                                </span>
                                                @if ($url)
                                                    <a
                                                        href="{{ $url }}"
                                                        class="text-label-sm text-secondary hover:text-secondary/80 font-semibold transition-colors whitespace-nowrap"
                                                    >
                                                        <x-symbol nama="open_in_new" class="text-[12px]" />
                                                        Buka
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                    </div>
                @endif
            </div>

            @if ($jumlahNotif > 0)
                <div class="p-2 border-t border-outline-variant text-center">
                    <a
                        href="#" id="btn-load-more-notif"
                        class="text-label-sm text-secondary hover:text-secondary/80 font-semibold"
                    >
                        Muat lebih banyak...
                    </a>
                </div>
            @endif
        </div>
    </div>
@endif