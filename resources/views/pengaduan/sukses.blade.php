@extends('layouts.guest')

@section('title', 'Aduan Terkirim')

@section('content')
<section class="flex min-h-[720px] flex-col items-center bg-brand-light px-8 py-14">
    <div class="mx-auto flex w-full max-w-[680px] flex-col items-center rounded-3xl bg-white p-10 text-center shadow-[0_1px_2px_rgba(0,0,0,0.05)]">

        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-100">
            <svg class="h-8 w-8 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>

        <span class="mt-6 inline-flex items-center gap-2 rounded-full bg-brand-100 px-4 py-1.5 text-xs font-semibold tracking-[0.24px] text-brand-500">
            <svg class="h-3 w-3 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2 3 7v10l9 5 9-5V7l-9-5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                <path d="m9 11 2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Aduan Diterima Sistem
        </span>

        <h1 class="mt-3 text-[32px] font-bold leading-10 tracking-[-0.8px] text-ink">Terima kasih, aduan Anda telah kami terima.</h1>
        <p class="mt-2 max-w-[520px] text-base leading-[26px] text-ink-muted">Tim Komite Mutu &amp; Kinerja Rumah Sakit akan melakukan validasi dan menghubungi Anda melalui WhatsApp paling lambat 1x24 jam kerja. Simpan kode tiket berikut untuk memantau progres.</p>

        <div class="mt-6 w-full rounded-xl bg-brand-light p-5">
            <p class="text-[11px] font-semibold uppercase tracking-[0.6px] text-brand-600">Nomor Tiket Anda</p>
            <p class="mt-1 text-2xl font-bold tracking-[0.5px] text-brand-800">{{ $pengaduan->kode_tiket }}</p>
            <p class="mt-2 text-[13px] text-ink-muted">Status saat ini: <span class="font-semibold text-brand-800">Diterima / Dalam Validasi</span> · {{ $pengaduan->created_at->translatedFormat('d F Y H:i') }} WIB</p>
        </div>

        <div class="mt-6 flex w-full flex-col gap-2">
            <a href="{{ route('pengaduan.create') }}" class="rounded-lg bg-brand-800 px-8 py-3.5 text-sm font-semibold text-white shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition hover:bg-brand-700">Sampaikan Aduan Lainnya</a>
            <a href="{{ url('/') }}" class="rounded-lg px-8 py-3.5 text-sm font-semibold text-ink-muted transition hover:bg-brand-50">Kembali ke Beranda</a>
        </div>
    </div>
</section>
@endsection