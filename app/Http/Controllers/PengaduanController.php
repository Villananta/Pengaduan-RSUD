<?php

namespace App\Http\Controllers;

use App\Enums\StatusPengaduan;
use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use App\Notifications\PengaduanEmailVerifikasi;
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

class PengaduanController extends Controller
{
    /** Kunci session yang menandai tiket sudah diverifikasi pelapor. */
    private const SESI_TERVERIFIKASI = 'tiket_terverifikasi';

    /**
     * Jumlah tebakan OTP yang boleh dicoba untuk satu tiket.
     *
     * OTP hanya 6 digit, jadi tanpa batas ini penebak punya sejuta
     * kesempatan selama kode masih berlaku. Angkanya kecil supaya tidak
     * mengganggu pelapor yang salah ketik, tetapi cukup membuat penebakan
     * beruntun tidak realistis dalam 15 menit.
     */
    private const BATAS_PERCOBAAN_OTP = 5;

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
        $request->session()->put(self::SESI_TERVERIFIKASI, [
            'kode' => $pengaduan->kode_tiket,
        ]);

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
        $terverifikasi = $request->session()->get(self::SESI_TERVERIFIKASI, []);
        $kode = $terverifikasi['kode'] ?? null;

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
        // Rate limit per kode tiket + IP (maks 5 percobaan per menit)
        // Mencegah brute-force menebak kode tiket walau ganti IP.
        $kodeInput = strtoupper(trim((string) $request->input('kode', '')));
        if ($kodeInput !== '') {
            $rateKey = 'verifikasi:'.$kodeInput.':'.$request->ip();
            if (RateLimiter::tooManyAttempts($rateKey, 5)) {
                $seconds = RateLimiter::availableIn($rateKey);

                return redirect()
                    ->route('pengaduan.lacak')
                    ->withErrors(['kode' => "Terlalu banyak percobaan untuk kode ini. Coba lagi dalam {$seconds} detik."])
                    ->withInput(['kode' => $kodeInput]);
            }
            RateLimiter::hit($rateKey, 60);
        }

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:64'],
        ]);

        $kode = strtoupper(trim($validated['kode']));
        $kandidat = Pengaduan::where('kode_tiket', $kode)->first();

        if (! $kandidat) {
            $request->session()->forget(self::SESI_TERVERIFIKASI);

            return redirect()
                ->route('pengaduan.lacak')
                ->withErrors(['kode' => 'Kode tiket tidak sesuai dengan data kami. Pastikan kode diketik dengan benar.'])
                ->withInput(['kode' => $kode]);
        }

        // Reset rate limit saat berhasil
        if ($kodeInput !== '') {
            $rateKey = 'verifikasi:'.$kodeInput.':'.$request->ip();
            RateLimiter::clear($rateKey);
        }

        $request->session()->put(self::SESI_TERVERIFIKASI, [
            'kode' => $kandidat->kode_tiket,
        ]);

        // Generate token akses permanen & simpan ke cookie HttpOnly (30 hari)
        $token = $kandidat->generateAksesToken();
        $cookie = cookie('pengaduan_token', $token, 43200, null, null, true, true, false, 'Lax'); // 30 hari = 43200 menit

        return redirect()->route('pengaduan.lacak')->cookie($cookie);
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
        // Prioritas: session (verifikasi baru) > cookie token (akses permanen)
        $terverifikasi = $request->session()->get(self::SESI_TERVERIFIKASI, []);
        $kodeTerverifikasi = $terverifikasi['kode'] ?? null;

        $kode = strtoupper(trim((string) $request->query('kode', '')));

        // Kalau tidak ada session, cek cookie token akses
        if (! $kodeTerverifikasi) {
            $token = $request->cookie('pengaduan_token');
            if ($token) {
                $tiketViaToken = Pengaduan::findByValidAksesToken($token);
                if ($tiketViaToken) {
                    $kodeTerverifikasi = $tiketViaToken->kode_tiket;
                    // Restore session biar konsisten
                    $request->session()->put(self::SESI_TERVERIFIKASI, ['kode' => $kodeTerverifikasi]);
                }
            }
        }

        $tiket = $kodeTerverifikasi
            ? Pengaduan::with([
                // Hanya pesan jalur pelapor yang berhak dilihat di halaman
                // lacak; koordinasi humas dengan unit tidak untuk dikonsumsi
                // pelapor sehingga ikut disaring sejak query pertama.
                'pesan' => fn ($q) => $q->jalurPelapor(),
            ])->where('kode_tiket', $kodeTerverifikasi)->first()
            : null;

        if ($tiket === null) {
            $request->session()->forget(self::SESI_TERVERIFIKASI);
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
        $terverifikasi = $request->session()->get(self::SESI_TERVERIFIKASI, []);
        $authorized = ($terverifikasi['kode'] ?? null) === $pengaduan->kode_tiket;

        if (! $authorized) {
            $token = $request->cookie('pengaduan_token');
            if ($token) {
                $tiketViaToken = Pengaduan::findByValidAksesToken($token);
                $authorized = $tiketViaToken?->kode_tiket === $pengaduan->kode_tiket;
                if ($authorized) {
                    // Restore session biar konsisten
                    $request->session()->put(self::SESI_TERVERIFIKASI, ['kode' => $pengaduan->kode_tiket]);
                }
            }
        }

        if (! $authorized) {
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

        if ($pengaduan->verifyEmailOtp($validated['otp'])) {
            RateLimiter::clear($kunciPercobaan);

            return redirect()->route('pengaduan.lacak')
                ->with('sukses', 'Email berhasil diverifikasi. Anda akan menerima notifikasi perkembangan pengaduan.');
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

        $otp = $pengaduan->generateEmailOtp();
        Notification::route('mail', $pengaduan->email)
            ->notify(new PengaduanEmailVerifikasi($pengaduan, $otp));

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Kode OTP baru sudah dikirim ke email Anda. Berlaku 15 menit.']);
        }

        return back()->with('sukses', 'Kode OTP baru sudah dikirim ke email Anda. Berlaku 15 menit.');
    }
}
