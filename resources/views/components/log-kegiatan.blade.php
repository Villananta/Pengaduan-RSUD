@props([
    'kegiatan',
    'kosong' => 'Belum ada kegiatan pada tiket ini.',
])

{{--
    Linimasa log kegiatan satu tiket.

    Isinya sudah disusun App\Support\DetailPengaduan, jadi komponen ini tidak
    membaca model maupun menjalankan query. Halaman humas mengirim seluruh
    kegiatan, sedangkan halaman unit mengirim hasil kegiatanUnit().
--}}

<div class="flex flex-col">
    @forelse ($kegiatan as $baris)
        <div class="flex gap-space-sm">

            <!-- Rel waktu: ikon kejadian dan garis penghubung ke baris berikutnya. -->
            <div class="flex flex-col items-center w-9 shrink-0">
                <span class="w-9 h-9 rounded-full flex items-center justify-center {{ $baris['nada'] }}">
                    <x-symbol :nama="$baris['ikon']" class="text-[18px]" />
                </span>

                @unless ($loop->last)
                    <span class="w-px flex-1 bg-outline-variant/60 my-1" aria-hidden="true"></span>
                @endunless
            </div>

            <div class="flex flex-col gap-0.5 flex-1 min-w-0 pb-space-md">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-label-md text-label-md font-bold text-on-surface">{{ $baris['judul'] }}</span>
                    <span class="px-2 py-0.5 rounded-xs {{ $baris['nada'] }} font-label-sm text-label-sm font-semibold">
                        {{ $baris['jenis'] }}
                    </span>
                </div>

                @if (filled($baris['isi']))
                    <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">{{ $baris['isi'] }}</p>
                @endif

                <span class="font-label-sm text-label-sm text-outline">
                    {{ $baris['aktor'] }} &bull; {{ $baris['waktu'] }}

                    @if ($baris['lampiran'] > 0)
                        &bull; {{ $baris['lampiran'] }} lampiran
                    @endif
                </span>
            </div>
        </div>
    @empty
        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $kosong }}</p>
    @endforelse
</div>
