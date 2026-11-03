<?php

namespace App\Http\Controllers;

use App\Enums\StatusPengaduan;
use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use App\Notifications\PengaduanEmailVerifikasi;
use App\Support\AksesTiketPelapor;
use App\Support\PengaduanMasuk;
use App\Support\StatistikDashboard;
use App\Support\StatistikPengaduan;
use App\Support\StatistikUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Cookie;

class PengaduanController extends Controller
{
    /**
     * Jumlah tebakan OTP yang boleh dicoba untuk satu tiket.
     *
     * OTP hanya 6 digit, jadi tanpa batas ini penebak punya sejuta
     * kesempatan selama kode masih berlaku. Angkanya kecil supaya tidak
     * mengganggu pelapor yang salah ketik, tetapi cukup membuat penebakan
     * beruntun tidak realistis dalam 15 menit.
     */
    private const BATAS_PERCOBAAN_OTP = 5;

    /** Jumlah percobaan kode tiket yang boleh dicoba dalam satu jendela. */
    private const BATAS_PERCOBAAN_KODE = 5;

    /** Panjang jendela pembatas tebakan kode tiket, dalam detik. */
    private const JEDA_PERCOBAAN_KODE = 60;

    /**
     * Jumlah permintaan kirim ulang OTP untuk satu tiket.
     *
     * Satu tiket hanya punya satu alamat email, jadi batas per tiket sudah
     * cukup menahan kiriman berulang tanpa ikut menghukum pelapor lain yang
     * kebetulan memakai jaringan yang sama.
     */
    private const BATAS_KIRIM_ULANG_OTP = 3;

    /** Panjang jendela pembatas kirim ulang OTP, dalam detik (15 menit). */
    private const JEDA_KIRIM_ULANG_OTP = 900;

    public function create(): View
    {
        return view('pengaduan.create', [
            'metrik' => StatistikPengaduan::metrik(),
            'units' => config('pengaduan.units'),
        ]);
    }

    /**
     * Simpan pengaduan baru dari formulir pelapor.
     *
     * Aturan isian, pemetaan unit, dan pembuatan kode tiket diserahkan ke
     * App\Support\PengaduanMasuk supaya formulir admin humas memakai
     * kolom yang sama persis dengan yang dipakai di sini.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            PengaduanMasuk::aturan(),
            PengaduanMasuk::pesanValidasi(),
        );

        $pengaduan = PengaduanMasuk::simpan($validated, $request->file('lampiran', []) ?? []);

        // Pesan pembuka otomatis agar pelapor langsung melihatnya di halaman lacak.
        // Perannya 'pembuka', bukan 'admin', karena ini sapaan otomatis dan bukan
        // balasan unit. Kalau berperan 'admin', setiap tiket baru akan langsung
        // terbaca sudah terjawab di daftar pengaduan, monitor, dan dashboard.
        $pengaduan->pesan()->create([
            'kanal' => PesanPengaduan::KANAL_PELAPOR,
            'peran' => 'pembuka',
            'isi' => PengaduanMasuk::pesanPembuka($pengaduan),
        ]);

        StatistikDashboard::lupaCache();
        StatistikPengaduan::lupaCache();
        StatistikUnit::lupaCache();

        // Pelapor baru saja membuat tiketnya sendiri, jadi tiketnya langsung
        // ditandai terverifikasi tanpa perlu mengetik kode tiket ulang.
        AksesTiketPelapor::simpan($request, $pengaduan->kode_tiket);

        return redirect()->route('pengaduan.sukses');
    }

    /**
     * Halaman konfirmasi setelah tiket dibuat.
     *
     * Kode tiket sengaja tidak lagi ditempel di URL, supaya kode yang
     * berfungsi sebagai kunci membuka tiket ini tidak bocor lewat riwayat
     * browser, header referer, atau log server. Kode dibaca dari session
     * yang baru saja diisi langkah store, dan halaman ini hanya bisa
     * dibuka oleh pelapor yang barusan membuat tiketnya.
     */
    public function sukses(Request $request): View|RedirectResponse
    {
        $kode = AksesTiketPelapor::kodeSesi($request);

        if ($kode === null) {
            return redirect()->route('pengaduan.create');
        }

        $pengaduan = Pengaduan::where('kode_tiket', $kode)->firstOrFail();

        return view('pengaduan.sukses', compact('pengaduan'));
    }

    /**
     * Verifikasi kepemilikan tiket.
     *
     * Kode tiket sengaja dikirim lewat POST, bukan query string, supaya
     * tidak tersimpan di riwayat browser, log server, maupun header
     * referer. Hasil verifikasi disimpan di session, bukan di URL.
     * Token akses permanen (30 hari) disimpan di cookie HttpOnly untuk
     * mengizinkan akses kembali tanpa verifikasi ulang.
     */
    public function verifikasi(Request $request): RedirectResponse
    {
        // Rate limit per kode tiket (maks 5 percobaan per menit). Kunci
        // sengaja tanpa IP supaya jatah satu kode tidak berlipat hanya
        // karena penebak berpindah jaringan; pembatasan per IP tetap dijaga
        // throttle rute di atasnya.
        $kodeInput = strtoupper(trim((string) $request->input('kode', '')));
        if ($kodeInput !== '') {
            $rateKey = self::kunciPercobaanKode($kodeInput);
            if (RateLimiter::tooManyAttempts($rateKey, self::BATAS_PERCOBAAN_KODE)) {
                $seconds = RateLimiter::availableIn($rateKey);

                return redirect()
                    ->route('pengaduan.lacak')
                    ->withErrors(['kode' => "Terlalu banyak percobaan untuk kode ini. Coba lagi dalam {$seconds} detik."])
                    ->withInput(['kode' => $kodeInput]);
            }
            RateLimiter::hit($rateKey, self::JEDA_PERCOBAAN_KODE);
        }

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:64'],
        ]);

        $kode = strtoupper(trim($validated['kode']));
        $kandidat = Pengaduan::where('kode_tiket', $kode)->first();

        if (! $kandidat) {
            AksesTiketPelapor::lupakan($request);

            return redirect()
                ->route('pengaduan.lacak')
                ->withErrors(['kode' => 'Kode tiket tidak sesuai dengan data kami. Pastikan kode diketik dengan benar.'])
                ->withInput(['kode' => $kode]);
        }

        // Reset rate limit saat berhasil
        if ($kodeInput !== '') {
            RateLimiter::clear(self::kunciPercobaanKode($kodeInput));
        }

        AksesTiketPelapor::simpan($request, $kandidat->kode_tiket);

        // Generate token akses permanen & simpan ke cookie HttpOnly (30 hari)
        $token = $kandidat->generateAksesToken();

        return redirect()->route('pengaduan.lacak')->cookie(self::cookieAkses($token));
    }

    /**
     * Lacak tiket.
     *
     * Rincian tiket yang memuat data pribadi hanya dibuka untuk sesi yang
     * sudah memverifikasi kode tiket, sehingga data pasien lain tidak bisa
     * dibaca hanya dengan mencoba-tebak kode acak.
     * Mendukung akses via session (verifikasi baru) ATAU cookie token (akses permanen).
     */
    public function lacak(Request $request): View
    {
        // Session diperiksa lebih dulu, lalu cookie token 30 hari.
        $kodeTerverifikasi = AksesTiketPelapor::kodeTerbuka($request);

        $kode = strtoupper(trim((string) $request->query('kode', '')));

        $tiket = $kodeTerverifikasi
            ? Pengaduan::with([
                // Hanya pesan jalur pelapor yang berhak dilihat di halaman
                // lacak; koordinasi humas dengan unit tidak untuk dikonsumsi
                // pelapor sehingga ikut disaring sejak query pertama.
                'pesan' => fn ($q) => $q->jalurPelapor(),
            ])->where('kode_tiket', $kodeTerverifikasi)->first()
            : null;

        if ($tiket === null) {
            AksesTiketPelapor::lupakan($request);
        }

        return view('pengaduan.lacak', [
            'kode' => $kode !== '' ? $kode : ($kodeTerverifikasi ?? ''),
            'tiket' => $tiket,
            'tahap' => StatusPengaduan::cases(),
            'sudahDicek' => $tiket !== null,
            'ringkasRataRata' => StatistikPengaduan::ringkasRataRata(),
        ]);
    }

    public function kirimPesan(Request $request, string $kode): RedirectResponse
    {
        $pengaduan = Pengaduan::where('kode_tiket', $kode)->firstOrFail();

        // Cek otorisasi: session (verifikasi baru) ATAU cookie token (akses permanen)
        if (! AksesTiketPelapor::berhak($request, $pengaduan)) {
            return redirect()
                ->route('pengaduan.lacak')
                ->withErrors(['kode' => 'Verifikasi kode tiket terlebih dahulu sebelum mengirim pesan.']);
        }

        $validated = $request->validate([
            'isi' => ['required', 'string', 'max:2000'],
            'lampiran' => ['nullable', 'array', 'max:5'],
            'lampiran.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'isi.required' => 'Tuliskan pesan terlebih dahulu.',
            'lampiran.max' => 'Maksimal 5 lampiran per pesan.',
            'lampiran.*.mimes' => 'Lampiran hanya boleh berformat JPG, PNG, atau PDF.',
            'lampiran.*.max' => 'Ukuran maksimal tiap lampiran adalah 4 MB.',
        ]);

        // Pesan dari pelapor selalu berperan sebagai "pelapor".
        $pengaduan->pesan()->create([
            'kanal' => PesanPengaduan::KANAL_PELAPOR,
            'peran' => 'pelapor',
            'isi' => $validated['isi'],
            'lampiran' => PesanPengaduan::simpanBerkas($request->file('lampiran', []) ?? []),
        ]);

        return redirect()
            ->route('pengaduan.lacak')
            ->with('sukses', 'Pesan Anda sudah terkirim ke admin humas.');
    }

    /**
     * Tampilkan formulir verifikasi email.
     *
     * Kode OTP tidak pernah dibaca dari URL. Tautan di email hanya membuka
     * formulir ini, sehingga kode tidak tertinggal di riwayat browser, log
     * server, atau header referer setiap kali halaman dibuka.
     */
    public function tampilkanVerifikasiEmail(string $kode): View|RedirectResponse
    {
        $pengaduan = Pengaduan::where('kode_tiket', strtoupper($kode))->firstOrFail();

        if ($pengaduan->isEmailVerified()) {
            return redirect()->route('pengaduan.lacak')
                ->with('info', 'Email sudah terverifikasi sebelumnya.');
        }

        return view('pengaduan.verifikasi-email', [
            'pengaduan' => $pengaduan,
            'kode' => $pengaduan->kode_tiket,
        ]);
    }

    /**
     * Verifikasi OTP yang dikirim ke email pelapor.
     *
     * Percobaan dibatasi per tiket, bukan hanya per IP: penebak bisa
     * berpindah IP, sedangkan tiket yang ditebak tetap satu. Kuncinya
     * sengaja tidak memuat IP supaya jatah tidak berlipat hanya karena
     * pelapor berganti jaringan.
     */
    public function verifikasiEmail(Request $request, string $kode): RedirectResponse
    {
        $pengaduan = Pengaduan::where('kode_tiket', strtoupper($kode))->firstOrFail();

        // Kalau email sudah terverifikasi, langsung ke halaman lacak
        if ($pengaduan->isEmailVerified()) {
            return redirect()->route('pengaduan.lacak')
                ->with('info', 'Email sudah terverifikasi sebelumnya.');
        }

        $kunciPercobaan = 'otp-email:'.$pengaduan->kode_tiket;

        if (RateLimiter::tooManyAttempts($kunciPercobaan, self::BATAS_PERCOBAAN_OTP)) {
            return back()->withErrors([
                'otp' => 'Terlalu banyak percobaan kode OTP. Coba lagi dalam 15 menit.',
            ]);
        }

        $validated = $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ], [
            'otp.required' => 'Masukkan kode OTP yang dikirim ke email Anda.',
            'otp.digits' => 'Kode OTP terdiri dari 6 angka.',
        ]);

        $tokenBaru = $pengaduan->verifyEmailOtp($validated['otp']);

        if ($tokenBaru !== null) {
            RateLimiter::clear($kunciPercobaan);

            // Rotasi token mencabut token lama, jadi cookie di browser ini
            // harus diganti saat itu juga. Tanpa ini pelapor tetap bisa
            // membuka tiket selama session hidup, lalu kehilangan akses
            // begitu session berakhir.
            return redirect()->route('pengaduan.lacak')
                ->with('sukses', 'Email berhasil diverifikasi. Anda akan menerima notifikasi perkembangan pengaduan.')
                ->cookie(self::cookieAkses($tokenBaru));
        }

        RateLimiter::hit($kunciPercobaan, 900);

        return back()->withErrors(['otp' => 'Kode OTP tidak valid atau sudah kedaluwarsa (maksimal 15 menit).'])
            ->withInput(['otp' => $validated['otp']]);
    }

    /**
     * Kirim ulang OTP verifikasi email.
     */
    public function kirimUlangOtp(Request $request, string $kode): RedirectResponse|JsonResponse
    {
        $pengaduan = Pengaduan::where('kode_tiket', strtoupper($kode))->firstOrFail();

        if ($pengaduan->isEmailVerified()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Email sudah terverifikasi sebelumnya.']);
            }

            return redirect()->route('pengaduan.lacak')
                ->with('info', 'Email sudah terverifikasi sebelumnya.');
        }

        // Kirim ulang juga dibatasi per tiket, bukan hanya per IP lewat
        // throttle rute. Tanpa ini, satu kode tiket bisa dipakai membanjiri
        // alamat emailnya sendiri hanya dengan berpindah jaringan.
        $kunciKirimUlang = 'kirim-ulang-otp:'.$pengaduan->kode_tiket;

        if (RateLimiter::tooManyAttempts($kunciKirimUlang, self::BATAS_KIRIM_ULANG_OTP)) {
            $sisaMenit = max(1, (int) ceil(RateLimiter::availableIn($kunciKirimUlang) / 60));
            $pesan = 'Terlalu banyak permintaan kode OTP. Coba lagi dalam '.$sisaMenit.' menit.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $pesan], 429);
            }

            return back()->withErrors(['otp' => $pesan]);
        }

        $otp = $pengaduan->generateEmailOtp();
        Notification::route('mail', $pengaduan->email)
            ->notify(new PengaduanEmailVerifikasi($pengaduan, $otp));

        // Hitungan baru dicatat setelah email benar-benar dikirim, supaya
        // kegagalan pengiriman tidak menghabiskan jatah pelapor.
        RateLimiter::hit($kunciKirimUlang, self::JEDA_KIRIM_ULANG_OTP);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Kode OTP baru sudah dikirim ke email Anda. Berlaku 15 menit.']);
        }

        return back()->with('sukses', 'Kode OTP baru sudah dikirim ke email Anda. Berlaku 15 menit.');
    }

    /** Kunci pembatas tebakan kode tiket, sengaja tanpa IP. */
    private static function kunciPercobaanKode(string $kode): string
    {
        return 'verifikasi:'.$kode;
    }

    /**
     * Cookie token akses 30 hari.
     *
     * Dipakai jalur verifikasi kode tiket maupun verifikasi email. Disatukan
     * supaya kedua jalur tidak bisa berbeda soal HttpOnly, Secure, dan
     * SameSite saat salah satunya diubah.
     */
    private static function cookieAkses(string $token): Cookie
    {
        return cookie(AksesTiketPelapor::COOKIE, $token, 43200, null, null, true, true, false, 'Lax'); // 30 hari = 43200 menit
    }
}
