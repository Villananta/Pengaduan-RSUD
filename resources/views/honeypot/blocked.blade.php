@extends('layouts.guest')

@section('title', 'Permintaan Diblokir')

@section('content')

<!-- Honeypot Blocked -->
<section class="flex min-h-[720px] flex-col items-center bg-brand-light px-8 py-14">
    <div class="mx-auto flex w-full max-w-[480px] flex-col items-center rounded-3xl bg-white p-10 text-center shadow-[0_1px_2px_rgba(0,0,0,0.05)]">

        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-100">
            <svg class="h-8 w-8 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>

        <h1 class="mt-6 text-[28px] font-bold leading-10 tracking-[-0.8px] text-ink">Permintaan diproses</h1>
        <p class="mt-2 max-w-[520px] text-base leading-[26px] text-ink-muted">Terima kasih telah mengakses layanan ini.</p>

        <div class="mt-6 w-full rounded-xl bg-brand-light p-4">
            <p class="text-[13px] text-ink-muted">Jika Anda mengalami kendala, silakan coba lagi nanti atau hubungi call center.</p>
        </div>

    </div>
</section>
<!-- End of Honeypot Blocked -->

@endsection