<?php

namespace App\Http\Controllers;

use App\Enums\KategoriPengaduan;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Support\StatistikDashboard;
use App\Support\StatistikPengaduan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->aturanAduan(), $this->pesanValidasiAduan());

        $lampiran = [];
        if ($request->hasFile('lampiran')) {
            foreach ($request->file('lampiran') as $file) {
                $lampiran[] = $file->store('lampiran', config('pengaduan.disk_lampiran', 'public'));
            }
        }

        $pengaduan = Pengaduan::buat(
            collect($validated)
                ->except(['persetujuan', 'lampiran'])
                ->put('master_unit_id', $this->unitMaster($validated['unit']))
                ->all(),
            $this->generateKodeTiket(),
        );

        $pengaduan->forceFill(['lampiran' => $lampiran])->save();

        // Pesan pembuka otomatis agar pelapor langsung melihatnya di halaman lacak.
        // Pesan ini berasal dari Tim Humas & Pengaduan sebagai balasan resmi pertama.
        $pengaduan->pesan()->create([
            'peran' => 'admin',
            'isi' => "Terima kasih telah menyampaikan pengaduan kepada kami. Laporan Anda telah kami terima dengan nomor tiket {$pengaduan->kode_tiket} pada {$pengaduan->created_at->translatedFormat('d F Y, H:i')} WIB.\n\nTim Humas & Pengaduan RSUD Dr. Soetomo akan menindaklanjuti laporan ini sesuai prosedur yang berlaku, dengan estimasi penyelesaian maksimal 5 hari kerja. Kami akan menginformasikan perkembangan penanganan melalui kontak yang telah Anda daftarkan.\n\nMohon simpan nomor tiket ini sebagai referensi jika Anda ingin menanyakan status laporan.\n\nTerima kasih atas kepercayaan Anda kepada RSUD Dr. Soetomo.",
        ]);

        StatistikDashboard::lupaCache();
        StatistikPengaduan::lupaCache();

        // Pelapor baru saja membuktikan kepemilikan NRM, jadi tiketnya
        // langsung ditandai terverifikasi tanpa perlu mengetik ulang.
        $request->session()->put(self::SESI_TERVERIFIKASI, [
            'kode' => $pengaduan->kode_tiket,
            'nrm' => $pengaduan->nrm,
        ]);

        return redirect()->route('pengaduan.sukses', $pengaduan->kode_tiket);
    }

    /**
     * Terjemahkan label unit pilihan pelapor ke MASTER_UNITS.
     *
     * Pengaduan lama atau unit yang belum dipetakan tetap boleh kosong,
     * karena kolomnya nullable dan nama pada kolom unit tetap disimpan.
     */
    private function unitMaster(string $label): ?int
    {
        $peta = config('pengaduan.peta_unit', []);
        $kode = $peta[$label] ?? null;

        if ($kode === null) {
            return null;
        }

        return MasterUnit::where('kode', $kode)->value('id');
    }

    public function sukses(string $kode): View
    {
        $pengaduan = Pengaduan::where('kode_tiket', $kode)->firstOrFail();

        return view('pengaduan.sukses', compact('pengaduan'));
    }

    /**
     * Verifikasi kepemilikan tiket.
     *
     * NRM sengaja dikirim lewat POST, bukan query string, supaya tidak
     * tersimpan di riwayat browser, log server, maupun header referer.
     * Hasil verifikasi disimpan di session, bukan di URL.
     */
    public function verifikasi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:64'],
            'nrm' => ['required', 'string', 'min:4', 'max:64'],
        ]);

        $kode = strtoupper(trim($validated['kode']));
        $kandidat = Pengaduan::where('kode_tiket', $kode)->first();

        if (! $kandidat || ! $kandidat->nrmCocok($validated['nrm'])) {
            $request->session()->forget(self::SESI_TERVERIFIKASI);

            return redirect()
                ->route('pengaduan.lacak')
                ->withErrors(['kode' => 'Kode tiket atau NRM tidak cocok dengan data yang kami simpan.'])
                ->withInput(['kode' => $kode]);
        }

        $request->session()->put(self::SESI_TERVERIFIKASI, [
            'kode' => $kandidat->kode_tiket,
            'nrm' => $kandidat->nrm,
        ]);

        return redirect()->route('pengaduan.lacak');
    }

    /**
     * Lacak tiket.
     *
     * Rincian tiket yang memuat data pribadi hanya dibuka untuk sesi yang
     * sudah memverifikasi kode tiket dan NRM, sehingga data pasien lain
     * tidak bisa dibaca hanya dengan mencoba-tebak kode acak.
     */
    public function lacak(Request $request): View
    {
        $terverifikasi = $request->session()->get(self::SESI_TERVERIFIKASI, []);
        $kodeTerverifikasi = $terverifikasi['kode'] ?? null;

        $kode = strtoupper(trim((string) $request->query('kode', '')));

        $tiket = $kodeTerverifikasi
            ? Pengaduan::with('pesan')->where('kode_tiket', $kodeTerverifikasi)->first()
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

        // Balasan hanya boleh dikirim dari sesi yang sudah memverifikasi NRM.
        $terverifikasi = $request->session()->get(self::SESI_TERVERIFIKASI, []);

        if (($terverifikasi['kode'] ?? null) !== $pengaduan->kode_tiket) {
            return redirect()
                ->route('pengaduan.lacak')
                ->withErrors(['kode' => 'Verifikasi kode tiket dan NRM terlebih dahulu sebelum mengirim pesan.']);
        }

        $validated = $request->validate([
            'isi' => ['required', 'string', 'max:2000'],
            'lampiran' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'isi.required' => 'Tuliskan pesan terlebih dahulu.',
            'lampiran.mimes' => 'Lampiran hanya boleh berformat JPG, PNG, atau PDF.',
            'lampiran.max' => 'Ukuran maksimal lampiran adalah 4 MB.',
        ]);

        // Pesan dari pelapor selalu berperan sebagai "pelapor".
        $pengaduan->pesan()->create([
            'peran' => 'pelapor',
            'isi' => $validated['isi'],
            'lampiran' => $request->file('lampiran')?->store('lampiran', config('pengaduan.disk_lampiran', 'public')),
        ]);

        return redirect()
            ->route('pengaduan.lacak')
            ->with('sukses', 'Pesan Anda sudah terkirim ke admin humas.');
    }

    /** Aturan validasi formulir pengaduan. */
    private function aturanAduan(): array
    {
        return [
            'kategori' => ['required', Rule::enum(KategoriPengaduan::class)],
            'nama_lengkap' => ['required', 'string', 'min:3', 'max:255'],
            'nrm' => ['required', 'string', 'min:4', 'max:64', 'regex:/^[0-9A-Za-z\-\/.\s]+$/'],
            'no_wa' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'alamat' => ['required', 'string', 'min:10', 'max:1000'],
            'waktu_kejadian' => ['required', 'date', 'after:2020-01-01', 'before_or_equal:now'],
            'unit' => ['required', 'string', Rule::in(config('pengaduan.units'))],
            'subjek' => ['required', 'string', 'min:10', 'max:255'],
            'deskripsi' => ['required', 'string', 'min:20', 'max:10000'],
            'lampiran' => ['nullable', 'array', 'max:5'],
            'lampiran.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'persetujuan' => ['required', 'accepted'],
        ];
    }

    /** Pesan error custom agar ramah untuk pelapor. */
    private function pesanValidasiAduan(): array
    {
        return [
            'persetujuan.accepted' => 'Anda harus menyetujui pernyataan kebenaran data sebelum mengirim aduan.',
            'nama_lengkap.min' => 'Nama lengkap minimal 3 karakter.',
            'nrm.min' => 'Nomor rekam media minimal 4 karakter.',
            'nrm.regex' => 'Nomor rekam media hanya boleh berisi angka, huruf, tanda hubung, atau spasi.',
            'no_wa.regex' => 'Nomor WhatsApp harus berupa nomor telepon aktif, contoh 081234567890.',
            'alamat.min' => 'Alamat minimal 10 karakter.',
            'waktu_kejadian.after' => 'Waktu kejadian tidak valid.',
            'waktu_kejadian.before_or_equal' => 'Waktu kejadian tidak boleh di masa depan.',
            'unit.in' => 'Unit atau instalasi yang dipilih tidak terdaftar.',
            'subjek.min' => 'Ringkasan masalah minimal 10 karakter agar mudah dipahami.',
            'deskripsi.min' => 'Uraian kronologi minimal 20 karakter.',
            'lampiran.max' => 'Maksimal 5 lampiran per pengaduan.',
            'lampiran.*.mimes' => 'Lampiran hanya boleh berformat JPG, PNG, atau PDF.',
            'lampiran.*.max' => 'Ukuran maksimal tiap lampiran adalah 4 MB.',
        ];
    }

    protected function generateKodeTiket(): string
    {
        do {
            $kode = 'ADUAN-'.now()->format('Ymd').'-'.strtoupper(collect(range(1, 5))->map(fn () => chr(random_int(65, 90)))->implode(''));
        } while (Pengaduan::where('kode_tiket', $kode)->exists());

        return $kode;
    }
}
