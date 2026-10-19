@extends('layouts.guest')

@section('title', 'Aktivasi Akun PIC')

@section('content')

<!-- Aktivasi Akun PIC -->
<section class="flex min-h-[720px] flex-col items-center bg-brand-light px-8 py-14">
    <div class="mx-auto flex w-full max-w-[480px] flex-col items-center rounded-3xl bg-white p-10 text-center shadow-[0_1px_2px_rgba(0,0,0,0.05)]">

        @if (session('aktivasi_sukses'))

            <!-- Panel Keberhasilan: token sudah terpakai, jadi halaman ini
                 tidak lagi menawarkan formulir apa pun. -->
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                <svg class="h-8 w-8 text-green-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                    <path d="m8.5 12.5 2.5 2.5 4.5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>

            <h1 class="mt-6 text-[28px] font-bold leading-10 tracking-[-0.8px] text-ink">Aktivasi berhasil</h1>
            <p class="mt-2 text-base leading-[26px] text-ink-muted">{{ session('aktivasi_sukses') }}</p>

        @elseif ($unit === null)

            <!-- Token tidak dikenal: bisa jadi sudah pernah dipakai atau
                 memang bukan tautan yang dikeluarkan sistem ini. -->
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-red-50">
                <svg class="h-8 w-8 text-red-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <polyline points="22 6 12 13 2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>

            <h1 class="mt-6 text-[28px] font-bold leading-10 tracking-[-0.8px] text-ink">Tautan undangan tidak berlaku</h1>
            <p class="mt-2 text-base leading-[26px] text-ink-muted">Tautan ini tidak dikenal atau sudah pernah dipakai. Minta admin mengirim undangan baru lewat halaman Master Data Unit.</p>

        @elseif (! $berlaku)

            <!-- Token dikenal tapi masa berlakunya sudah lewat: pesannya
                 dipisah dari token asing supaya PIC tahu harus minta apa. -->
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-100">
                <svg class="h-8 w-8 text-amber-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                    <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>

            <h1 class="mt-6 text-[28px] font-bold leading-10 tracking-[-0.8px] text-ink">Tautan undangan sudah kedaluwarsa</h1>
            <p class="mt-2 text-base leading-[26px] text-ink-muted">Tautan untuk {{ $unit->namaLengkap() }} berlaku {{ \App\Support\UndanganPic::UMUR_JAM }} jam sejak dikirim dan kini sudah lewat. Minta admin mengirim undangan baru.</p>

        @else

            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-100">
                <svg class="h-8 w-8 text-amber-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <polyline points="22 6 12 13 2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>

            <span class="mt-6 inline-flex items-center gap-2 rounded-full bg-amber-100 px-4 py-1.5 text-xs font-semibold tracking-[0.24px] text-amber-500">
                <svg class="h-3 w-3 text-amber-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2 3 7v10l9 5 9-5V7l-9-5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                    <path d="m9 11 2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Aktivasi Akun PIC
            </span>

            <h1 class="mt-3 text-[28px] font-bold leading-10 tracking-[-0.8px] text-ink">Buat kata sandi akun PIC</h1>
            <p class="mt-2 max-w-[520px] text-base leading-[26px] text-ink-muted">Anda membuka tautan undangan untuk unit berikut. Isi nama dan kata sandi yang akan dipakai membuka konsol unit.</p>

            <!-- Ringkasan Unit: siapa yang mengundang dan ke mana tautan ini terikat. -->
            <div class="mt-6 w-full rounded-xl bg-brand-light p-4 text-left">
                <p class="text-[11px] font-semibold uppercase tracking-[0.6px] text-brand-600">Unit Tujuan</p>
                <p class="mt-1 text-lg font-bold tracking-[0.5px] text-brand-800">{{ $unit->namaLengkap() }}</p>
                <p class="mt-1 text-[13px] text-ink-muted">
                    PIC: {{ $unit->pic ?? 'Belum ditetapkan' }} &middot; {{ $tujuan }}
                </p>
                <p class="mt-2 text-[13px] text-ink-muted">Tautan berlaku sampai {{ $unit->undangan_berlaku->isoFormat('d MMMM Y, HH:mm') }} WIB.</p>
            </div>

            @if (session('errors'))
                <div class="mt-4 w-full rounded-lg bg-red-50 p-3 text-left">
                    <p class="text-sm text-red-800" role="alert">{{ session('errors')->first() }}</p>
                </div>
            @endif

            <!-- Formulir Aktivasi -->
            <form method="POST" action="{{ route('aktivasi.undangan.simpan', ['token' => $unit->undangan_token]) }}" class="mt-6 w-full flex flex-col gap-4 text-left">
                @csrf

                <div>
                    <label for="nama" class="block text-sm font-medium text-ink">Nama Lengkap</label>
                    <input type="text"
                           id="nama"
                           name="nama"
                           value="{{ old('nama', $unit->pic) }}"
                           maxlength="120"
                           required
                           class="mt-1.5 block w-full rounded-lg border border-brand-300 bg-white px-4 py-3 text-base text-ink placeholder:text-brand-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500/20 transition" />
                </div>

                <div>
                    <label for="kata_sandi" class="block text-sm font-medium text-ink">Kata Sandi (min. 8 karakter)</label>
                    <input type="password"
                           id="kata_sandi"
                           name="kata_sandi"
                           minlength="8"
                           required
                           class="mt-1.5 block w-full rounded-lg border border-brand-300 bg-white px-4 py-3 text-base text-ink placeholder:text-brand-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500/20 transition" />
                </div>

                <div>
                    <label for="kata_sandi_confirmation" class="block text-sm font-medium text-ink">Ulangi Kata Sandi</label>
                    <input type="password"
                           id="kata_sandi_confirmation"
                           name="kata_sandi_confirmation"
                           minlength="8"
                           required
                           class="mt-1.5 block w-full rounded-lg border border-brand-300 bg-white px-4 py-3 text-base text-ink placeholder:text-brand-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500/20 transition" />
                </div>

                <button type="submit"
                        class="mt-2 rounded-full bg-brand-800 px-8 py-3.5 text-sm font-semibold text-white shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:ring-offset-2">
                    Aktivasi Akun
                </button>
            </form>
            <!-- End of Formulir Aktivasi -->

        @endif

    </div>
</section>
<!-- End of Aktivasi Akun PIC -->

@endsection
