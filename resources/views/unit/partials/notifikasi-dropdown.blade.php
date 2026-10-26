{{-- Dropdown Notifikasi Unit (Partial) --}}
@if ($unit !== null && isset($notifikasiUnit))
    @php
        $jumlahNotif = $notifikasiUnit->jumlahNotifikasiBelumDibaca();
        $adaNudge = $notifikasiUnit->adaNudgeBelumDibaca();
        $notifikasi = $notifikasiUnit->notifikasiTerbaru(10);
    @endphp

    <div class="relative ml-space-md" id="notifikasi-wrapper">
        <button
            type="button"
            id="btn-notifikasi"
            data-mark-read-url="{{ route('unit.notifikasi.tandai-dibaca', $unit) }}"
            class="relative p-2 rounded-full text-on-surface-variant hover:text-brand-800 transition-colors"
            aria-label="Notifikasi"
            aria-expanded="false"
            aria-haspopup="true"
        >
            <x-symbol nama="notifications" class="text-[24px]" />
            @if ($jumlahNotif > 0)
                <span
                    id="badge-notifikasi"
                    class="absolute -top-0.5 -right-0.5 w-5 h-5 min-w-5 rounded-full bg-error text-on-error text-[10px] font-bold flex items-center justify-center"
                    aria-label="{{ $jumlahNotif }} notifikasi belum dibaca"
                >
                    {{ $jumlahNotif > 9 ? '9+' : $jumlahNotif }}
                </span>
            @endif
            @if ($adaNudge && $jumlahNotif == 0)
                <span
                    class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-warning ring-2 ring-white"
                    aria-label="Ada follow up (nudge) dari admin humas"
                ></span>
            @endif
        </button>

        {{-- Dropdown Notifikasi --}}
        <div
            id="dropdown-notifikasi"
            class="hidden absolute right-0 top-full mt-2 w-96 max-h-[420px] overflow-y-auto bg-white rounded-lg shadow-lg border border-outline-variant z-[60]"
            role="menu"
            aria-orientation="vertical"
        >
            <div class="p-3 border-b border-outline-variant flex items-center justify-between">
                <h3 class="font-title-sm text-title-sm font-bold text-on-surface">Notifikasi</h3>
                @if ($jumlahNotif > 0)
                    <form method="POST" action="{{ route('unit.notifikasi.tandai-semua-dibaca', $unit) }}" id="form-tandai-semua" class="inline">
                        @csrf
                        <button
                            type="submit"
                            class="text-label-sm text-primary hover:text-primary/80 font-semibold transition-colors"
                        >
                            Tandai semua dibaca
                        </button>
                    </form>
                @endif
            </div>

            <div id="daftar-notifikasi" class="max-h-[360px] overflow-y-auto">
                @if ($notifikasi->isEmpty())
                    <div class="p-4 text-center text-on-surface-variant text-body-sm">
                        Tidak ada notifikasi baru
                    </div>
                @else
                    <div class="divide-y divide-outline-variant">
                        @foreach ($notifikasi as $notif)
                            @php
                                $data = $notif->data ?? [];
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
                            @endphp
                            <div class="p-3 hover:bg-surface-container-low transition-colors cursor-pointer notif-item"
                                data-notif-id="{{ $notif->id }}"
                                data-notif-url="{{ $url }}"
                                data-notif-type="{{ $type }}"
                                role="menuitem"
                                tabindex="0"
                            >
                                <div class="flex gap-2">
                                    <span class="mt-0.5 text-primary">
                                        <x-symbol :nama="$icon" class="text-[18px]" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="font-label-md text-label-md font-semibold text-on-surface">{{ $title }}</p>
                                            <span class="text-[11px] text-on-surface-variant whitespace-nowrap shrink-0">{{ $notif->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="font-body-sm text-body-sm text-on-surface-variant line-clamp-2 mt-0.5">{{ $message }}</p>
                                        @if ($kodeTiket)
                                            <div class="mt-1.5">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-brand-100 text-brand-800 font-mono font-body-xs text-body-xs font-bold">
                                                    <x-symbol nama="confirmation_number" class="text-[10px]" />
                                                    {{ $kodeTiket }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
