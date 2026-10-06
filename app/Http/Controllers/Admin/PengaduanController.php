<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KanalPengaduan;
use App\Enums\KategoriPengaduan;
use App\Enums\StatusPengaduan;
use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use App\Support\DaftarPengaduan;
use App\Support\DetailPengaduan;
use App\Support\PengaduanMasuk;
use App\Support\Sla;
use App\Support\StatistikDashboard;
use App\Support\StatistikPengaduan;
use App\Support\TindakLanjutPengaduan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class PengaduanController extends Controller
{
    /**
     * Daftar pengaduan konsol admin dalam bentuk tabel yang bisa disaring.
     *
     * Penyusunan query, jumlah per tab, dan kalimat pada tabel diserahkan
     * ke App\Support\DaftarPengaduan supaya template tidak memakai query.
     */
    public function index(Request $request): View
    {
        return view('admin.pengaduan.index', [
            'daftar' => DaftarPengaduan::dariRequest($request),
        ]);
    }

    /**
     * Formulir pencatatan aduan yang masuk di luar portal.
     *
     * Keluhan telepon, SMS, atau yang diterima di loket tetap harus punya
     * tiket resmi, sebab tanpa nomor tiket keluhan itu tidak bisa dilacak
     * pelapor maupun ditagih ke unit. Isiannya sengaja sama dengan
     * formulir pelapor supaya standar datanya tidak berbeda antar kanal.
     */
    public function create(): View
    {
        return view('admin.pengaduan.form', [
            'kategori' => KategoriPengaduan::cases(),
            'kanal' => KanalPengaduan::cases(),
            'units' => config('pengaduan.units'),
            'hariKerja' => Sla::hariKerja(),
            'hariInvestigasi' => Sla::hariInvestigasi(),
        ]);
    }

    /**
     * Simpan aduan yang dicatat admin, lalu buka tiketnya.
     *
     * Pengalihan langsung ke workspace tiket supaya admin bisa langsung
     * memeriksa hasil pencatatan dan lanjut memberi balasan resmi, tanpa
     * harus mencari kode tiket yang baru dibuat di daftar.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            PengaduanMasuk::aturan([
                'kanal' => ['required', Rule::enum(KanalPengaduan::class)],
            ]),
            PengaduanMasuk::pesanValidasi([
                'kanal.required' => 'Pilih kanal penerimaan aduan lebih dulu.',
                'kanal.enum' => 'Kanal penerimaan aduan tidak dikenal.',
                'persetujuan.accepted' => 'Isi pernyataan bahwa data pelapor sudah dikonfirmasi langsung, termasuk persetujuan pengolahan datanya.',
            ]),
        );

        $kanal = KanalPengaduan::from($validated['kanal']);

        $pengaduan = PengaduanMasuk::simpan($validated, $request->file('lampiran', []) ?? []);

        // Pesan pembuka tetap dibuat supaya tiket ini bisa dibaca pelapor
        // lewat halaman lacak begitu kode tiket dan NRM-nya diberikan.
        // Perannya 'pembuka' supaya tidak terhitung sebagai balasan unit.
        $pengaduan->pesan()->create([
            'peran' => 'pembuka',
            'isi' => PengaduanMasuk::pesanPembuka($pengaduan, $kanal, $request->user()?->name),
        ]);

        StatistikDashboard::lupaCache();
        StatistikPengaduan::lupaCache();

        return $this->kembali(
            $pengaduan,
            'Aduan '.$kanal->label().' tersimpan dengan nomor tiket '.$pengaduan->kode_tiket.'. Sampaikan nomor ini ke pelapor.',
        );
    }

    /**
     * Workspace & Detail: satu tiket pengaduan yang dibuka dari daftar.
     *
     * Tiket yang tidak ada dibiarkan 404 supaya admin yang salah ketik kode
     * tidak diarahkan ke halaman kosong tanpa penjelasan.
     */
    public function show(string $kode): View
    {
        return view('admin.pengaduan.show', [
            'detail' => DetailPengaduan::dariKode($kode),
        ]);
    }

    /** Simpan draf jawaban resmi tanpa mengirimnya ke pelapor. */
    public function simpanDraf(Request $request, string $kode): RedirectResponse
    {
        $pengaduan = $this->cariTiket($kode);

        $validated = $request->validate([
            'draf' => ['nullable', 'string', 'max:5000'],
        ], [
            'draf.max' => 'Draf jawaban maksimal 5000 karakter.',
        ]);

        TindakLanjutPengaduan::simpanDraf($pengaduan, $validated['draf'] ?? null);

        return $this->kembali($pengaduan, 'Draf jawaban tersimpan. Belum ada yang dikirim ke pelapor.');
    }

    /** Aksi A: kirim jawaban resmi ke pelapor sekaligus menutup tiket. */
    public function kirimJawaban(Request $request, string $kode): RedirectResponse
    {
        $pengaduan = $this->cariTiket($kode);

        // Isi jawaban boleh dikosongkan karena panel formulasi sekarang hanya
        // menyediakan tombol. Kalau diisi, panjangnya tetap dijaga supaya
        // pelapor tidak menerima potongan kalimat yang tidak berarti.
        $validated = $request->validate([
            'isi' => ['nullable', 'string', 'min:10', 'max:5000'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'isi.min' => 'Jawaban resmi minimal 10 karakter agar pelapor mendapat penjelasan yang berarti.',
            'isi.max' => 'Jawaban resmi maksimal 5000 karakter.',
            'catatan.max' => 'Catatan internal maksimal 500 karakter.',
        ]);

        $tindakan = TindakLanjutPengaduan::tindakanTersedia($pengaduan);

        // Alasan penolakan ditulis dengan nama field yang dipakai template,
        // supaya pesan salahnya muncul di panel yang sama dengan formulirnya.
        if (! $tindakan['tutup']) {
            throw ValidationException::withMessages([
                'isi' => $tindakan['alasanTutup'],
            ]);
        }

        TindakLanjutPengaduan::kirimJawabanResmi(
            $pengaduan,
            $validated['isi'] ?? null,
            $validated['catatan'] ?? null,
        );

        return $this->kembali($pengaduan, 'Tiket ditandai selesai.');
    }

    /** Aksi B: kembalikan tiket ke unit untuk klarifikasi ulang. */
    public function kembalikan(Request $request, string $kode): RedirectResponse
    {
        $pengaduan = $this->cariTiket($kode);

        $validated = $request->validate([
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'catatan.max' => 'Catatan untuk unit maksimal 500 karakter.',
        ]);

        $tindakan = TindakLanjutPengaduan::tindakanTersedia($pengaduan);

        // Alasan penolakan ditulis dengan nama field yang dipakai template,
        // supaya pesan salahnya muncul di panel yang sama dengan formulirnya.
        if (! $tindakan['kembalikan']) {
            throw ValidationException::withMessages([
                'catatan' => $tindakan['alasanKembalikan'],
            ]);
        }

        TindakLanjutPengaduan::kembalikanKeUnit($pengaduan, $validated['catatan'] ?? null);

        return $this->kembali($pengaduan, 'Tiket dikembalikan ke unit untuk klarifikasi ulang.');
    }

    /**
     * Tombol tahap pada panel bawah: Proses, Selesaikan, atau Revisi.
     *
     * Semua tombol dikirim ke endpoint yang sama dan Bedanya hanya nilai
     * tujuan, supaya aturan perpindahan tahap tidak tercecer di tiga
     * controller method yang berbeda.
     */
    public function pindahTahap(Request $request, string $kode): RedirectResponse
    {
        $pengaduan = $this->cariTiket($kode);

        $validated = $request->validate([
            'tujuan' => ['required', Rule::enum(StatusPengaduan::class)],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'tujuan.required' => 'Pilih tahap tujuan lebih dulu.',
            'tujuan.enum' => 'Tahap tujuan tidak dikenal.',
            'catatan.max' => 'Catatan untuk unit maksimal 500 karakter.',
        ]);

        $tujuan = StatusPengaduan::from($validated['tujuan']);

        try {
            TindakLanjutPengaduan::pindahkanTahap(
                $pengaduan,
                $tujuan,
                $validated['catatan'] ?? null,
            );
        } catch (RuntimeException $alasan) {
            // Alasan penolakan ditulis dengan nama field yang dipakai template,
            // supaya pesan salahnya muncul di panel yang sama dengan tombolnya.
            throw ValidationException::withMessages([
                'tujuan' => $alasan->getMessage(),
            ]);
        }

        return $this->kembali($pengaduan, TindakLanjutPengaduan::pesanTujuan($tujuan));
    }

    /**
     * Balas di kolom percakapan, sama seperti yang dilakukan pelapor.
     *
     * Balasan ini tidak menutup tiket. Tutupannya tetap lewat tombol tahap,
     * supaya admin bisa membalas berkali-kali tanpa mengubah status utama.
     */
    public function balas(Request $request, string $kode): RedirectResponse
    {
        $pengaduan = $this->cariTiket($kode);

        $validated = $request->validate([
            'isi' => ['required', 'string', 'max:2000'],
            'lampiran' => ['nullable', 'array', 'max:5'],
            'lampiran.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'isi.required' => 'Tuliskan balasan lebih dulu sebelum dikirim.',
            'isi.max' => 'Balasan maksimal 2000 karakter.',
            'lampiran.max' => 'Maksimal 5 lampiran per balasan.',
            'lampiran.*.mimes' => 'Lampiran hanya boleh berformat JPG, PNG, atau PDF.',
            'lampiran.*.max' => 'Ukuran maksimal tiap lampiran adalah 4 MB.',
        ]);

        TindakLanjutPengaduan::balasPelapor(
            $pengaduan,
            $validated['isi'],
            $request->file('lampiran', []) ?? [],
        );

        return $this->kembali($pengaduan, 'Balasan terkirim ke pelapor.');
    }

    /** Nyalakan atau matikan penandaan kasus berat beserta ekstensi SLA-nya. */
    public function kasusBerat(Request $request, string $kode): RedirectResponse
    {
        $pengaduan = $this->cariTiket($kode);

        $validated = $request->validate([
            'aktif' => ['required', 'boolean'],
        ], [
            'aktif.required' => 'Status kasus berat tidak terbaca.',
        ]);

        $aktif = $request->boolean('aktif');

        TindakLanjutPengaduan::tandaiKasusBerat($pengaduan, $aktif);

        return $this->kembali(
            $pengaduan,
            $aktif
                ? 'Tiket ditandai kasus berat, target penyelesaian digeser.'
                : 'Penandaan kasus berat dilepas, target kembali ke standar.'
        );
    }

    /**
     * Tiket yang dibuka formulir harus benar-benar ada; kalau tidak, kode
     * yang diketik admin tidak boleh sampai tersimpan sebagai balasan.
     */
    private function cariTiket(string $kode): Pengaduan
    {
        return Pengaduan::where('kode_tiket', $kode)->firstOrFail();
    }

    /**
     * Kembali ke panel tiket dengan pesan hasil.
     *
     * Semua tindakan pada halaman detail berakhir di halaman yang sama,
     * supaya admin tidak perlu mencari ulang tiketnya di daftar.
     */
    private function kembali(Pengaduan $pengaduan, string $pesan): RedirectResponse
    {
        return redirect()
            ->route('admin.pengaduan.show', $pengaduan->kode_tiket)
            ->with('sukses', $pesan);
    }
}
