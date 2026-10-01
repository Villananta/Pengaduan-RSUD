@extends('layouts.guest')

@section('title', 'Terlalu Banyak Permintaan')

@section('content')
    <div class="mx-auto max-w-lg text-center">
        <p class="text-[11px] font-bold uppercase tracking-[0.9px] text-brand-800">Batas Permintaan Tercapai</p>

        <h1 class="mt-2 text-2xl font-semibold tracking-[-0.5px] text-ink">Terlalu Banyak Permintaan</h1>

        <p class="mt-3 text-[13px] leading-5 text-ink-muted">
            {{ $pesan ?? 'Mohon tunggu beberapa saat sebelum mencoba lagi.' }}
        </p>

        <p class="mt-4 rounded-lg bg-brand-light px-3 py-2 text-xs leading-5 text-ink-muted">
            Batas ini melindungi sistem agar tetap dapat melayani seluruh pelapor.
            Bila pengaduan Anda sudah terkirim, Anda tetap dapat membuka halaman lacak tiket.
        </p>

        <div class="mt-6 flex flex-col items-center justify-center gap-2 sm:flex-row">
            <a href="{{ route('pengaduan.lacak') }}" class="rounded-lg bg-brand-800 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-900">
                Buka Halaman Lacak
            </a>
            <a href="{{ url('/') }}" class="rounded-lg bg-brand-light px-4 py-2 text-xs font-semibold text-ink hover:bg-brand-100">
                Kembali ke Beranda
            </a>
        </div>
    </div>

@endsection
