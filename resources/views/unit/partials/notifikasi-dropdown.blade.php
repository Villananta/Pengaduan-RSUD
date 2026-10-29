{{-- Dropdown Notifikasi Unit (Partial) --}}
@php
    $unitNotif = $unit ?? (auth()->check() ? auth()->user()->unit()->first() : null);
    $notifikasiUnitObj = isset($notifikasiUnit) ? $notifikasiUnit : ($unitNotif ? new \App\Support\NotifikasiUnit($unitNotif, auth()->user()) : null);
@endphp
@if (auth()->check() && auth()->user()->berperanUnit())
    @php
        $jumlahNotif = $notifikasiUnitObj ? $notifikasiUnitObj->jumlahNotifikasiBelumDibaca() : auth()->user()->unreadNotifications()->count();
        $adaNudge = $notifikasiUnitObj ? $notifikasiUnitObj->adaNudgeBelumDibaca() : auth()->user()->unreadNotifications()->where('data->type', 'pengaduan_nudge_unit')->exists();
        $notifikasi = $notifikasiUnitObj ? $notifikasiUnitObj->notifikasiTerbaru(10) : auth()->user()->unreadNotifications()->latest()->limit(10)->get();
        $markReadUrl = $unitNotif ? route('unit.notifikasi.tandai-dibaca', $unitNotif) : null;
        $markAllUrl = $unitNotif ? route('unit.notifikasi.tandai-semua-dibaca', $unitNotif) : null;
    @endphp

    {{-- <details> dipakai supaya dropdown tetap bisa dibuka tanpa JavaScript;
         tombol loncengnya sendiri adalah <summary>. --}}
    <div class="relative" id="notifikasi-wrapper">
        <details id="detail-notifikasi" class="static">
            <summary
                id="btn-notifikasi"
                data-mark-read-url="{{ $markReadUrl ?: route('unit.notifikasi.baca.user') }}"
                class="relative flex items-center justify-center p-space-xs text-on-surface-variant hover:text-brand-800 transition-colors cursor-pointer list-none [&::-webkit-details-marker]:hidden [&::marker]:hidden before:hidden"
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
                @if ($adaNudge)
                    {{-- Titik follow up ditaruh di sudut bawah supaya tidak
                         menutupi angka lencana di sudut atas. --}}
                    <span
                        class="titik-nudge absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-warning ring-2 ring-white"
                        aria-label="Ada follow up (nudge) dari admin humas"
                    ></span>
                @endif
            </summary>

            {{-- Dropdown Notifikasi --}}
            <div
                id="dropdown-notifikasi"
                class="absolute right-0 top-full mt-2 w-96 max-h-[420px] overflow-y-auto bg-white rounded-lg shadow-lg border border-outline-variant z-[60]"
                role="menu"
                aria-orientation="vertical"
            >
                <div class="p-3 border-b border-outline-variant flex items-center justify-between">
                    <h3 class="font-title-sm text-title-sm font-bold text-on-surface">Notifikasi</h3>
                    <form method="POST" action="{{ $markAllUrl ?: route('unit.notifikasi.baca-semua.user') }}" id="form-tandai-semua" class="inline">
                        @csrf
                        <button
                            type="submit"
                            class="text-label-sm text-primary hover:text-primary/80 font-semibold transition-colors"
                        >
                            Tandai semua dibaca
                        </button>
                    </form>
                </div>

                <div id="daftar-notifikasi" class="max-h-[360px] overflow-y-auto">
                    @if ($notifikasi->isEmpty())
                        <div class="p-4 text-center text-on-surface-variant text-body-sm">
                            Tidak ada notifikasi baru
                        </div>
                    @else
                        <div class="divide-y divide-outline-variant">
                            @foreach ($notifikasi as $notif)
                                <x-item-notifikasi :notif="$notif" />
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </details>
    </div>
@endif
