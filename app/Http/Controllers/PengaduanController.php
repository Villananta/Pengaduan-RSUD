<?php

namespace App\Http\Controllers;

use App\Enums\KategoriPengaduan;
use App\Enums\StatusPengaduan;
use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use App\Support\PengaduanMasuk;
use App\Support\StatistikDashboard;
use App\Support\StatistikPengaduan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengaduanController extends Controller
{
    /** Kunci session yang menandai tiket sudah diverifikasi pelapor. */
    private const SESI_TERVERIFIKASI = 'tiket_terverifikasi';

    public function create(): View
    {
        return view('pengaduan.create', [
            'metrik' => StatistikPengaduan::metrik(),
            'kategori' => KategoriPengaduan::cases(),
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

        // Pelapor baru saja membuat tiketnya sendiri, jadi tiketnya langsung
        // ditandai terverifikasi tanpa perlu mengetik kode tiket ulang.
        $request->session()->put(self::SESI_TERVERIFIKASI, [
            'kode' => $pengaduan->kode_tiket,
        ]);

        return redirect()->route('pengaduan.sukses', $pengaduan->kode_tiket);
    }

    public function sukses(string $kode): View
    {
        $pengaduan = Pengaduan::where('kode_tiket', $kode)->firstOrFail();

        return view('pengaduan.sukses', compact('pengaduan'));
    }

    /**
     * Verifikasi kepemilikan tiket.
     *
     * Kode tiket sengaja dikirim lewat POST, bukan query string, supaya
     * tidak tersimpan di riwayat browser, log server, maupun header
     * referer. Hasil verifikasi disimpan di session, bukan di URL.
     */
    public function verifikasi(Request $request): RedirectResponse
    {
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

        $request->session()->put(self::SESI_TERVERIFIKASI, [
            'kode' => $kandidat->kode_tiket,
        ]);

        return redirect()->route('pengaduan.lacak');
    }

    /**
     * Lacak tiket.
     *
     * Rincian tiket yang memuat data pribadi hanya dibuka untuk sesi yang
     * sudah memverifikasi kode tiket, sehingga data pasien lain tidak bisa
     * dibaca hanya dengan mencoba-tebak kode acak.
     */
    public function lacak(Request $request): View
    {
        $terverifikasi = $request->session()->get(self::SESI_TERVERIFIKASI, []);
        $kodeTerverifikasi = $terverifikasi['kode'] ?? null;

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

        // Balasan hanya boleh dikirim dari sesi yang sudah memverifikasi kode tiket.
        $terverifikasi = $request->session()->get(self::SESI_TERVERIFIKASI, []);

        if (($terverifikasi['kode'] ?? null) !== $pengaduan->kode_tiket) {
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
}
