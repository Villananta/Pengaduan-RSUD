@extends('layouts.guest')

@section('title', 'Masuk')

@section('robots')
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')

<!-- Halaman Masuk dengan Background Foto + Glassmorphism -->
<section class="relative flex min-h-[720px] items-center justify-center px-8 py-14 lg:min-h-screen lg:px-12"
         style="background-image: url('{{ asset('images/login/Screenshot-2025-08-17-at-144319-copy-1029917264.jpg') }}'); background-size: cover; background-position: center; background-repeat: no-repeat;">

    <!-- Overlay gelap tipis agar kartu kaca lebih kontras -->
    <div class="absolute inset-0" style="background-color: rgba(0, 0, 0, 0.35);"></div>

    <!-- Kontainer Utama (Efek Glassmorphism) -->
    <div class="relative z-10 mx-auto grid w-full max-w-6xl items-center gap-12 lg:grid-cols-2"
         style="background-color: rgba(20, 20, 20, 0.55); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 1.5rem; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
        
        <!-- Kolom Pengantar (Kiri) -->
        <div class="text-white">
            <h2 class="text-4xl font-bold leading-tight tracking-[-1px] text-white lg:text-5xl">
                Masuk ke Konsol Layanan
            </h2>
            <p class="mt-6 text-base leading-[26px]" style="color: rgba(255, 255, 255, 0.9);">
                Satu akun untuk admin humas dan PIC unit. Masuk memakai email dan kata sandi yang terdaftar di sistem pengaduan RSUD Dr. Soetomo.
            </p>

            <div class="mt-8 rounded-2xl border p-5 text-sm leading-6 lg:mt-10"
                     style="background-color: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.25); color: rgba(255, 255, 255, 0.9);">
                    <div class="mb-1 font-semibold text-white">Akun PIC belum ada?</div>
                    Humas mengundangnya lewat Master Data Unit, lalu kata sandi dibuat lewat tautan aktivasi yang dikirim ke email PIC.
                </div>
        </div>
        <!-- End of Kolom Pengantar -->

        <!-- Kolom Formulir (Kartu Putih di Kanan) -->
        <div class="w-full max-w-md lg:ml-auto">
            <div class="rounded-2xl bg-white p-8 shadow-xl">
                <h1 class="mb-10 text-3xl font-bold text-ink">Masuk</h1>

                @if (session('errors'))
                    <div class="mb-6 rounded-lg bg-error-container p-3">
                        <p class="text-sm text-on-error-container" role="alert">{{ session('errors')->first() }}</p>
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
            </div>
        </div>
        <!-- End of Kolom Formulir -->

    </div>
</section>
<!-- End of Halaman Masuk -->

@endsection