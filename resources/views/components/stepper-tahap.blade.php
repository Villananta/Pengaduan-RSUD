@props([
    'pilar',
    'sla',
    'judul' => 'Progres Siklus Pengaduan RSUD (Lapis 1 &mdash; Humas Terpadu)',
    'ringkasSla',
])

<!-- Stepper Empat Tahap Pengaduan beserta posisi hari kerja -->
<section class="w-full bg-surface-container-low/40 rounded-lg p-space-md flex flex-col gap-space-sm">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm">
        <span class="font-label-sm text-label-sm uppercase tracking-wider font-bold text-on-surface-variant">
            {{ $judul }}
        </span>
        <span class="font-label-sm text-label-sm text-secondary font-semibold">
            {{ $ringkasSla }}
        </span>
    </div>

    <div class="relative flex flex-col sm:flex-row sm:items-start justify-between gap-space-sm">
        {{-- Rail tipis di belakang node. Panjangnya hanya ikut tahap
             yang sudah dilalui, tahap terakhir tidak dicoret penuh. --}}
        <div class="hidden sm:block absolute inset-x-6 top-4 h-1" aria-hidden="true">
            <div class="h-1 w-full rounded-full bg-surface-container-highest"></div>
            <div
                class="absolute left-0 top-0 h-1 rounded-full bg-secondary"
                style="width: {{ $sla['persen'] }}%"
            ></div>
        </div>

        @foreach ($pilar as $pilarItem)
            <div class="flex flex-col items-start sm:items-center gap-1 relative z-10 flex-1">
                <span @class([
                    'w-8 h-8 rounded-full flex items-center justify-center shadow-sm',
                    'bg-secondary text-on-secondary' => $pilarItem['keadaan'] === 'tuntas',
                    'bg-primary-container text-on-primary ring-4 ring-secondary-container' => $pilarItem['keadaan'] === 'berjalan',
                    'bg-surface-container-lowest text-outline opacity-75' => $pilarItem['keadaan'] === 'menunggu',
                ])>
                    <x-symbol :nama="$pilarItem['ikon']" class="text-[18px]" />
                </span>

                <span @class([
                    'font-label-md text-label-md',
                    'font-bold text-secondary' => $pilarItem['keadaan'] === 'tuntas',
                    'font-bold text-primary-container' => $pilarItem['keadaan'] === 'berjalan',
                    'font-semibold text-outline' => $pilarItem['keadaan'] === 'menunggu',
                ])>
                    {{ $pilarItem['urut'] }}. {{ $pilarItem['label'] }}
                </span>

                <span class="font-label-sm text-label-sm text-on-surface-variant">
                    {{ $pilarItem['waktu'] ?? $pilarItem['subStatus'] }}
                </span>
            </div>
        @endforeach
    </div>

    <div class="flex items-center gap-space-xs font-label-sm text-label-sm text-on-surface-variant">
        <x-symbol :nama="$sla['lewatUnit'] ? 'crisis_alert' : 'verified'" class="text-[16px] {{ $sla['lewatUnit'] ? 'text-error' : 'text-secondary' }}" />

        @if ($sla['lewatUnit'])
            Investigasi unit sudah melewati batas {{ $sla['hariInvestigasi'] }} hari kerja, tiket masuk eskalasi.
        @else
            Batas investigasi internal unit {{ $sla['hariInvestigasi'] }} hari kerja, target penyelesaian {{ $sla['target'] }}.
        @endif
    </div>
</section>
<!-- End of Stepper Empat Tahap Pengaduan -->