@extends('layouts.guest')

@use(App\Support\Sla)

@section('title', 'Aduan Terkirim')

@section('content')

<!-- Konfirmasi Pengajuan -->
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
        <p class="mt-2 max-w-[520px] text-base leading-[26px] text-ink-muted">Pengaduan Anda sudah tercatat resmi dan akan diverifikasi oleh Tim Pengaduan. Simpan kode tiket dan nomor rekam medis Anda untuk memantau progres, karena keduanya dibutuhkan untuk membuka halaman lacak.</p>

        <!-- Nomor Tiket dan Posisi SLA -->
        <div class="mt-6 w-full rounded-xl bg-brand-light p-5">
            <p class="text-[11px] font-semibold uppercase tracking-[0.6px] text-brand-600">Nomor Tiket Anda</p>
            <p class="mt-1 text-2xl font-bold tracking-[0.5px] text-brand-800">{{ $pengaduan->kode_tiket }}</p>
            <p class="mt-2 text-[13px] text-ink-muted">Status saat ini: <span class="font-semibold text-brand-800">{{ $pengaduan->status->label() }}</span> · {{ $pengaduan->created_at->translatedFormat('d F Y H:i') }} WIB</p>
            <p class="mt-1 text-[13px] text-ink-muted">Target penyelesaian: <span class="font-semibold text-brand-800">{{ $pengaduan->targetSla()->translatedFormat('d F Y') }}</span> ({{ Sla::hariKerja() }} hari kerja)</p>
        </div>
        <!-- End of Nomor Tiket dan Posisi SLA -->

        <!-- Langkah Berikutnya -->
        <div class="mt-6 flex w-full flex-col gap-2">
            <a href="{{ route('pengaduan.lacak') }}" class="rounded-full bg-brand-800 px-8 py-3.5 text-sm font-semibold text-white shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition hover:bg-brand-700">Lacak Pengaduan Saya</a>
            <a href="{{ route('pengaduan.create') }}" class="rounded-full bg-brand-100 px-8 py-3.5 text-sm font-semibold text-brand-800 transition hover:bg-brand-50">Sampaikan Aduan Lainnya</a>
            <a href="{{ url('/') }}" class="rounded-full px-8 py-3.5 text-sm font-semibold text-ink-muted transition hover:bg-brand-50">Kembali ke Beranda</a>
        </div>
        <!-- End of Langkah Berikutnya -->
    </div>
</section>
<!-- End of Konfirmasi Pengajuan -->

@endsection