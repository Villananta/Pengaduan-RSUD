{{--
    Satu baris notifikasi untuk dropdown lonceng.

    Dipakai server (isi awal) dan sengaja dibuat sama bentuknya dengan
    yang dibangun JavaScript saat polling, supaya penanda kelas dan
    atribut data yang dibaca skrip tetap konsisten.
--}}
@props(['notif'])

@php
    $data = $notif->data ?? [];
    $tipe = $data['type'] ?? 'unknown';
    $url = $data['url'] ?? null;
    $kodeTiket = $data['kode_tiket'] ?? null;

    $ikon = match ($tipe) {
        'pengaduan_nudge_unit' => 'notifications_active',
        'pengaduan_sla_mendekati' => 'timelapse',
        'pengaduan_sla_lewat' => 'warning',
        'pengaduan_jawaban_unit' => 'reply',
        'pengaduan_disposisi' => 'forward_to_inbox',
        'pengaduan_tahap_berubah' => 'swap_vert',
        'pengaduan_kasus_berat' => 'warning',
        'pengaduan_eskalasi_sla' => 'notification_important',
        'pengaduan_eskalasi_nudge' => 'campaign',
        default => 'notifications',
    };

    $tag = $url ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($url) href="{{ $url }}" @endif
    {{ $attributes->merge(['class' => 'block p-3 hover:bg-surface-container-low transition-colors cursor-pointer notif-item']) }}
    data-notif-id="{{ $notif->id }}"
    data-notif-url="{{ $url }}"
    data-notif-type="{{ $tipe }}"
    role="menuitem"
    tabindex="0"
>
    <div class="flex gap-2">
        <span class="mt-0.5 text-primary">
            <x-symbol :nama="$ikon" class="text-[18px]" />
        </span>
        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
                <p class="font-label-md text-label-md font-semibold text-on-surface">{{ $data['title'] ?? 'Notifikasi' }}</p>
                <span class="text-[11px] text-on-surface-variant whitespace-nowrap shrink-0">{{ $notif->created_at->diffForHumans() }}</span>
            </div>
            <p class="font-body-sm text-body-sm text-on-surface-variant line-clamp-2 mt-0.5">{{ $data['message'] ?? '' }}</p>
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
</{{ $tag }}>
