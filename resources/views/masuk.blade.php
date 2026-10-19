@extends('layouts.guest')

@section('title', 'Masuk')

{{-- Alamat masuk sengaja tidak ditautkan di situs pelapor. noindex dipasang
     sebagai penjaga kedua supaya halaman ini tidak muncul di hasil pencarian
     meski alamatnya pernah terkirim lewat tautan atau pesan. --}}
@section('robots')
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')

<!-- Halaman Masuk -->
<section class="flex min-h-[720px] items-center bg-brand-light px-8 py-14">
    <div class="mx-auto grid w-full max-w-6xl items-center gap-12 lg:grid-cols-2">

        <!-- Kolom Pengantar: menjelaskan siapa yang boleh masuk dan bagaimana
             akun dibuat, karena tidak ada pendaftaran mandiri di sini. -->
        <div>
            <h2 class="text-4xl font-bold leading-tight tracking-[-1px] text-ink lg:text-5xl">Masuk ke Konsol Layanan</h2>
            <p class="mt-6 text-base leading-[26px] text-ink-muted">Satu akun untuk admin humas dan PIC unit. Masuk memakai email dan kata sandi yang terdaftar di sistem pengaduan RSUD Dr. Soetomo.</p>

            <div class="mt-6 text-sm leading-6 text-ink-muted lg:mt-12">
                Akun PIC belum ada?
                <span class="font-semibold text-ink">Humas mengundangnya lewat Master Data Unit</span>, lalu kata sandi dibuat lewat tautan aktivasi yang dikirim ke email PIC.
            </div>
        </div>
        <!-- End of Kolom Pengantar -->

        <!-- Kolom Formulir -->
        <div class="w-full max-w-md lg:ml-auto">
            <h1 class="mb-10 text-3xl font-bold text-ink">Masuk</h1>

            @if (session('errors'))
                <div class="mb-6 rounded-lg bg-red-50 p-3">
                    <p class="text-sm text-red-800" role="alert">{{ session('errors')->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('masuk.coba') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="email" class="mb-2 inline-block text-sm font-medium text-ink">Email</label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           autocomplete="username"
                           required
                           class="block w-full rounded-lg border border-brand-300 bg-white px-4 py-3 text-base text-ink placeholder:text-brand-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500/20 transition" />
                </div>

                <div>
                    <label for="kata_sandi" class="mb-2 inline-block text-sm font-medium text-ink">Kata Sandi</label>
                    <input type="password"
                           id="kata_sandi"
                           name="kata_sandi"
                           autocomplete="current-password"
                           required
                           class="block w-full rounded-lg border border-brand-300 bg-white px-4 py-3 text-base text-ink placeholder:text-brand-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500/20 transition" />
                </div>

                <!-- "Ingat saya" memakai checkbox asli yang disembunyikan lalu
                     digambar ulang, supaya centangnya bisa diberi warna brand
                     tanpa meninggalkan kontrol yang bisa diakses papan ketik.
                     Tanda centang diletakkan sejajar dengan kotaknya (bukan
                     di dalamnya) supaya varian peer-checked: tetap berlaku. -->
                <div class="relative flex items-center gap-3">
                    <input type="checkbox"
                           id="ingat"
                           name="ingat"
                           value="1"
                           {{ old('ingat') ? 'checked' : '' }}
                           class="peer sr-only" />
                    <div class="h-5 w-5 shrink-0 rounded border-2 border-brand-300 bg-white transition peer-checked:border-brand-800 peer-checked:bg-brand-800 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/40"></div>
                    <svg class="pointer-events-none absolute left-0 h-5 w-5 text-white opacity-0 transition peer-checked:opacity-100" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="m6 12.5 4 4 8-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <label for="ingat" class="text-sm font-medium text-ink-muted select-none">Ingat saya di peramban ini</label>
                </div>

                <button type="submit"
                        class="flex w-full items-center justify-center rounded-full bg-brand-800 px-8 py-3.5 text-sm font-semibold text-white shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:ring-offset-2">
                    Masuk
                </button>
            </form>
            <!-- End of Kolom Formulir -->
        </div>

    </div>
</section>
<!-- End of Halaman Masuk -->

@endsection
