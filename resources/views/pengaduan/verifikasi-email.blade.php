@extends('layouts.guest')

@section('title', 'Verifikasi Email')

@section('content')

<!-- Verifikasi Email -->
<section class="flex min-h-[720px] flex-col items-center bg-brand-light px-8 py-14">
    <div class="mx-auto flex w-full max-w-[480px] flex-col items-center rounded-3xl bg-white p-10 text-center shadow-[0_1px_2px_rgba(0,0,0,0.05)]">

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
            Verifikasi Email
        </span>

        <h1 class="mt-3 text-[28px] font-bold leading-10 tracking-[-0.8px] text-ink">Verifikasi alamat email Anda</h1>
        <p class="mt-2 max-w-[520px] text-base leading-[26px] text-ink-muted">Kami telah mengirim kode OTP 6 digit ke <strong>{{ $pengaduan->email }}</strong>. Masukkan kode di bawah untuk memverifikasi email Anda.</p>

        <!-- Info Tiket -->
        <div class="mt-6 w-full rounded-xl bg-brand-light p-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.6px] text-brand-600">Nomor Tiket</p>
            <p class="mt-1 text-lg font-bold tracking-[0.5px] text-brand-800">{{ $pengaduan->kode_tiket }}</p>
            <p class="mt-2 text-[13px] text-ink-muted">Kode OTP berlaku <span class="font-semibold text-amber-800">15 menit</span> sejak dikirim.</p>
        </div>

        @if (session('errors'))
            <div class="mt-4 w-full rounded-lg bg-red-50 p-3 text-left">
                <p class="text-sm text-red-800" role="alert">{{ session('errors')->first() }}</p>
            </div>
        @endif

        @if (session('sukses'))
            <div class="mt-4 w-full rounded-lg bg-green-50 p-3 text-left">
                <p class="text-sm text-green-800" role="status">{{ session('sukses') }}</p>
            </div>
        @endif

        <!-- Form OTP -->
        <form method="POST" action="{{ route('pengaduan.verifikasi-email.post', ['kode' => $kode]) }}" class="mt-6 w-full flex flex-col gap-4">
            @csrf

            <div class="text-left">
                <label for="otp" class="block text-sm font-medium text-ink">Kode OTP (6 digit)</label>
                <input type="text"
                       id="otp"
                       name="otp"
                       value="{{ old('otp') }}"
                       maxlength="6"
                       pattern="\d{6}"
                       inputmode="numeric"
                       autocomplete="one-time-code"
                       required
                       class="mt-1.5 block w-full rounded-lg border border-brand-300 bg-white px-4 py-3 text-center text-[24px] font-mono tracking-[0.5em] text-ink placeholder:text-brand-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500/20 transition"
                       placeholder="0 0 0 0 0 0" />
            </div>

            <button type="submit"
                    class="rounded-full bg-brand-800 px-8 py-3.5 text-sm font-semibold text-white shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:ring-offset-2">
                Verifikasi
            </button>

            <button type="button"
                    onclick="kirimUlangOtp()"
                    class="rounded-full bg-brand-100 px-8 py-3.5 text-sm font-semibold text-brand-800 transition hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:ring-offset-2">
                Kirim Ulang Kode
            </button>

            <a href="{{ route('pengaduan.lacak', ['kode' => $kode]) }}"
               class="rounded-full px-8 py-3.5 text-sm font-semibold text-ink-muted transition hover:bg-brand-50 text-center">
                Kembali ke Lacak Tiket
            </a>
        </form>

    </div>
</section>
<!-- End of Verifikasi Email -->

@push('scripts')
<script>
    let countdown = 0;
    const btnResend = document.querySelector('button[onclick="kirimUlangOtp()"]');

    function kirimUlangOtp() {
        if (countdown > 0) return;

        btnResend.disabled = true;
        btnResend.innerHTML = 'Mengirim...';

        fetch('{{ route('pengaduan.verifikasi-email.resend', ['kode' => $kode]) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(data => {
            if (data.success || data.message) {
                alert(data.message || 'Kode OTP baru sudah dikirim.');
                startCountdown(60);
            } else {
                alert(data.message || 'Gagal mengirim ulang kode.');
            }
        })
        .catch(() => {
            alert('Terjadi kesalahan jaringan.');
        })
        .finally(() => {
            btnResend.disabled = false;
            btnResend.innerHTML = 'Kirim Ulang Kode';
        });
    }

    function startCountdown(seconds) {
        countdown = seconds;
        const interval = setInterval(() => {
            countdown--;
            btnResend.innerHTML = `Kirim Ulang (${countdown}s)`;
            btnResend.disabled = true;
            if (countdown <= 0) {
                clearInterval(interval);
                btnResend.innerHTML = 'Kirim Ulang Kode';
                btnResend.disabled = false;
            }
        }, 1000);
    }

    // Auto-focus ke input OTP
    document.getElementById('otp')?.focus();

    // Format input: hanya angka, tambah spasi otomatis
    document.getElementById('otp')?.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 6) value = value.slice(0, 6);
        e.target.value = value;
    });
</script>
@endpush