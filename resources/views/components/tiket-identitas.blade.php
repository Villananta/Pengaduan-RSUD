@props([
    'kode',
    'tiket',
    'unit',
    'judulUnit' => 'Status Lapis 2 &bull; Unit Terkait',
    'showKategori' => false,
    'showNoWa' => true,
])

<!-- Kartu Identitas: data pengadu, unit terkait, dan penanda Lapis 2. -->
<section class="w-full bg-surface-container-lowest rounded-lg shadow-sm p-space-md flex flex-col lg:flex-row lg:items-start justify-between gap-space-lg">
    <div class="flex flex-col gap-space-sm min-w-0">
        <div class="flex flex-wrap items-center gap-space-sm">
            <span class="px-space-sm py-0.5 rounded-sm bg-surface-container-highest text-on-surface font-label-md text-label-md font-bold tracking-wide">
                #{{ $kode }}
            </span>

            @if ($unit['tertaut'])
                <span class="px-space-sm py-0.5 rounded-full {{ $unit['nada'] }} font-label-sm text-label-sm font-bold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-secondary" aria-hidden="true"></span>
                    {{ $unit['nama'] }}
                </span>
            @else
                <span class="px-space-sm py-0.5 rounded-full bg-surface-container text-on-surface-variant font-label-sm text-label-sm font-bold flex items-center gap-1">
                    <x-symbol nama="support_agent" class="text-[14px]" />
                    {{ $unit['nama'] }}
                </span>
            @endif

            @if ($showKategori)
                <span class="px-space-sm py-0.5 rounded-full bg-surface-container text-on-surface-variant font-label-sm text-label-sm font-bold">
                    {{ $tiket->labelKategori() }}
                </span>
            @endif

            <span class="text-on-surface-variant font-label-sm text-label-sm">
                Masuk {{ $tiket->created_at->translatedFormat('d M Y, H:i') }} WIB
            </span>
        </div>

        <h1 class="font-headline-sm text-headline-sm text-on-surface font-bold tracking-tight">
            {{ $tiket->subjek }}
        </h1>

        <div class="flex flex-wrap items-center gap-x-space-md gap-y-1 text-on-surface-variant font-body-sm text-body-sm">
            <span class="flex items-center gap-1">
                <x-symbol nama="person" class="text-[16px] text-secondary" />
                <span>{{ $tiket->nama_lengkap }}</span>
            </span>
            <span aria-hidden="true">&bull;</span>
            <span class="flex items-center gap-1">
                <x-symbol nama="badge" class="text-[16px] text-outline" />
                <span>NRM: <strong>{{ $tiket->nrm }}</strong></span>
            </span>
            <span aria-hidden="true">&bull;</span>
            <span class="flex items-center gap-1">
                <x-symbol nama="calendar_today" class="text-[16px] text-outline" />
                <span>Kejadian {{ $tiket->waktu_kejadian->translatedFormat('d M Y') }}</span>
            </span>
            @if (! $showKategori)
                <span aria-hidden="true">&bull;</span>
                <span>{{ $tiket->labelKategori() }}</span>
            @endif
            @if ($showNoWa)
                <span aria-hidden="true">&bull;</span>
                <span class="flex items-center gap-1">
                    <x-symbol nama="call" class="text-[16px] text-outline" />
                    <span>{{ $tiket->no_wa }}</span>
                </span>
            @endif
        </div>
    </div>

    {{-- Penanda Lapis 2: unit yang ditugaskan / status investigasi unit. --}}
    <div class="flex items-center gap-space-sm bg-surface-container-low p-space-sm rounded-lg shrink-0">
        <span class="w-10 h-10 rounded-lg {{ $unit['tertaut'] ? 'bg-secondary text-on-secondary' : 'bg-surface-container-highest text-outline' }} flex items-center justify-center shrink-0">
            <x-symbol :nama="$unit['tertaut'] ? 'domain' : 'support_agent'" class="text-[22px]" />
        </span>

        <div class="flex flex-col">
            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">
                {{ $judulUnit }}
            </span>

            <div class="flex items-center gap-1.5">
                @if ($unit['tertaut'])
                    <span class="w-2 h-2 rounded-full bg-secondary" aria-hidden="true"></span>
                @endif

                <span class="font-title-sm text-title-sm font-bold text-on-surface">{{ $unit['status'] }}</span>
            </div>

            <span class="font-label-sm text-label-sm text-on-surface-variant">{{ $unit['keterangan'] }}</span>
        </div>
    </div>
</section>
<!-- End of Kartu Identitas -->