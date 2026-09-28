@props(['nama', 'class' => 'h-4 w-4'])

@php
    $jalur = [
        'peringatan' => '<path d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'jam' => '<circle cx="12" cy="12" r="8.25" stroke="currentColor" stroke-width="1.6"/><path d="M12 7.5V12l3 1.75" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'kartu' => '<rect x="2.75" y="5" width="18.5" height="14" rx="2.25" stroke="currentColor" stroke-width="1.6"/><path d="M2.75 9.75h18.5" stroke="currentColor" stroke-width="1.6"/>',
        'dokumen' => '<path d="M13.5 3.25H7a2 2 0 0 0-2 2v13.5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8.75l-5.5-5.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M13.25 3.5V8.5h5.5M8.5 12.5h7M8.5 16h4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        'pesan' => '<path d="M20.25 12c0 3.87-3.7 7-8.25 7-.93 0-1.83-.12-2.66-.34L4.5 20.25l1.32-3.53A6.6 6.6 0 0 1 3.75 12c0-3.87 3.7-7 8.25-7s8.25 3.13 8.25 7Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'unit' => '<path d="M3.75 20.25h16.5M5.25 20.25V8.5L12 3.75l6.75 4.75v11.75M9.75 20.25v-6h4.5v6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'cari' => '<circle cx="10.75" cy="10.75" r="6.5" stroke="currentColor" stroke-width="1.6"/><path d="m15.5 15.5 4.75 4.75" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        'filter' => '<path d="M3.75 6.25h16.5M6.75 12h10.5M10 17.75h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        'panah' => '<path d="M13.5 5.25 6.75 12l6.75 6.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'kotak' => '<rect x="4" y="4" width="16" height="16" rx="2.5" stroke="currentColor" stroke-width="1.6"/>',
        'centang' => '<path d="m5 12.75 4.5 4.5L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'tautan' => '<path d="M10.5 13.5a3.5 3.5 0 0 0 5.25.38l2.5-2.5a3.54 3.54 0 1 0-5-5l-1.44 1.43M13.5 10.5a3.5 3.5 0 0 0-5.25-.38l-2.5 2.5a3.54 3.54 0 1 0 5 5l1.44-1.43" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'login' => '<path d="M14.25 16.5v1.75a2 2 0 0 1-2 2H5.75a2 2 0 0 1-2-2V5.75a2 2 0 0 1 2-2h6.5a2 2 0 0 1 2 2v1.75M18.75 12H9.75m9 0-3-3m3 3-3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'petir' => '<path d="M13 2.75 4.75 13.5h6.5l-.25 7.75L19.25 10.5h-6.5l.25-7.75Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
        'lonceng' => '<path d="M18 8.75a6 6 0 1 0-12 0c0 4.5-1.5 5.5-1.5 5.5h15S18 13.25 18 8.75ZM13.75 18.25a2 2 0 0 1-3.5 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'orang' => '<circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.6"/><path d="M5 20.25a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        'grafik' => '<path d="M4.25 19.75V4.25M4.25 19.75h15.5M8.5 16v-4M12.5 16V8.5M16.5 16v-6.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'keluar' => '<path d="M9.75 7.5V5.75a2 2 0 0 1 2-2h5.5a2 2 0 0 1 2 2v12.5a2 2 0 0 1-2 2h-5.5a2 2 0 0 1-2-2V16.5M14.25 12H4.5m0 0 3-3m-3 3 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
    ];
@endphp

<svg
    class="{{ $class }}"
    viewBox="0 0 24 24"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
>{!! $jalur[$nama] ?? $jalur['kotak'] !!}</svg>
