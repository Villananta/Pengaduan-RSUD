<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KanalPengaduan;
use App\Http\Controllers\Controller;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use App\Services\DisposisiService;
use App\Services\KasusBeratService;
use App\Support\DaftarPengaduan;
use App\Support\DetailPengaduan;
use App\Support\PengaduanMasuk;
use App\Support\Sla;
use App\Support\StatistikDashboard;
use App\Support\StatistikPengaduan;
use App\Support\StatistikUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
     * Kategori tidak dipilih di sini: humas menentukannya dari halaman
     * detail tiket, sama seperti tiket yang masuk lewat portal.
     */
    public function create(): View
    {
        return view('admin.pengaduan.form', [
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
            'kanal' => PesanPengaduan::KANAL_PELAPOR,
            'peran' => 'pembuka',
            'isi' => PengaduanMasuk::pesanPembuka($pengaduan, $kanal, $request->user()?->name),
        ]);

        StatistikDashboard::lupaCache();
        StatistikPengaduan::lupaCache();
        StatistikUnit::lupaCache();

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

    /**
     * Aksi massal (bulk action) untuk beberapa tiket sekaligus.
     *
     * Mendukung:
     * - disposisi: pindahkan ke unit yang dipilih
     * - kasus_berat: nyalakan/matikan penandaan kasus berat
     *
     * Pesan gagal dibagi dua kelas: aturan bisnis memakai kalimat siap
     * tampil, sedangkan kegagalan tak terduga hanya dicatat ke log dan
     * layar menerima pesan umum agar detail basis data tidak bocor.
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'aksi' => ['required', 'string', 'in:disposisi,kasus_berat'],
            // Batas ini mengikuti ukuran halaman terbesar pada daftar
            // pengaduan. Tanpa batas, satu kiriman bisa memuat ribuan kode
            // tiket dan setiap tiket mengirim notifikasi sendiri-sendiri.
            'kode_tiket' => ['required', 'array', 'min:1', 'max:100'],
            'kode_tiket.*' => ['string', 'exists:pengaduan,kode_tiket'],
            'unit' => ['required_if:aksi,disposisi', 'string', 'exists:master_units,kode'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'aktif' => ['required_if:aksi,kasus_berat', 'boolean'],
        ], [
            'aksi.required' => 'Pilih aksi massal yang akan dilakukan.',
            'aksi.in' => 'Aksi massal tidak dikenal.',
            'kode_tiket.required' => 'Pilih minimal satu tiket.',
            'kode_tiket.array' => 'Format pilihan tiket tidak valid.',
            'kode_tiket.min' => 'Pilih minimal satu tiket.',
            'kode_tiket.max' => 'Maksimal 100 tiket sekali proses. Persempit dulu pilihan dengan filter.',
            'kode_tiket.*.exists' => 'Salah satu kode tiket tidak ditemukan.',
            'unit.required_if' => 'Pilih unit tujuan untuk disposisi.',
            'unit.exists' => 'Unit tujuan tidak ditemukan.',
            'catatan.max' => 'Catatan maksimal 500 karakter.',
            'aktif.required_if' => 'Status kasus berat wajib diisi.',
        ]);

        $aksi = $validated['aksi'];
        $kodeTiket = $validated['kode_tiket'];
        $berhasil = 0;
        $gagal = [];

        foreach ($kodeTiket as $kode) {
            try {
                $pengaduan = Pengaduan::where('kode_tiket', $kode)->firstOrFail();

                if ($aksi === 'disposisi') {
                    $unit = MasterUnit::where('kode', $validated['unit'])->firstOrFail();

                    if (! $unit->bisaDitugaskan()) {
                        throw new RuntimeException('Unit tidak aktif atau koneksi terputus.');
                    }

                    if ($pengaduan->status->selesai()) {
                        throw new RuntimeException('Tiket sudah selesai.');
                    }

                    DisposisiService::disposisiKeUnit(
                        $pengaduan,
                        $unit,
                        $validated['catatan'] ?? null,
                        $request->user()?->id,
                    );
                } elseif ($aksi === 'kasus_berat') {
                    if ($pengaduan->status->selesai()) {
                        throw new RuntimeException('Tiket sudah selesai.');
                    }

                    KasusBeratService::tandaiKasusBerat(
                        $pengaduan,
                        $request->boolean('aktif'),
                        $request->user()?->id,
                    );
                }

                $berhasil++;
            } catch (RuntimeException $alasan) {
                // Aturan bisnis memang dilempar dengan pesan siap tampil —
                // "tiket sudah selesai", "unit tidak aktif" — jadi aman dibaca admin.
                $gagal[] = "$kode: {$alasan->getMessage()}";
            } catch (\Throwable $e) {
                // Kegagalan tak terduga (basis data, relasi rusak) bisa memuat
                // struktur query; detailnya dicatat ke log, bukan ke layar admin.
                report($e);
                $gagal[] = "$kode: kesalahan sistem, detail tersimpan di log.";
            }
        }

        $pesan = "$berhasil tiket berhasil diproses.";
        if (! empty($gagal)) {
            $pesan .= ' Gagal: '.implode('; ', $gagal);
        }

        // Semua tiket bisa gagal sekaligus. Kalau pesannya tetap dikirim
        // lewat kunci "sukses", layar memakai warna hijau dan admin mengira
        // aksinya berhasil; kunci "gagal" dipakai supaya sesuai kenyataan.
        return back()->with($berhasil === 0 ? 'gagal' : 'sukses', $pesan);
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
