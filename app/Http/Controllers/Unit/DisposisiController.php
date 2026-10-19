<?php

namespace App\Http\Controllers\Unit;

use App\Enums\StatusPengaduan;
use App\Http\Controllers\Controller;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Support\DaftarPengaduan;
use App\Support\DetailPengaduan;
use App\Support\JawabanUnit;
use App\Support\NotifikasiUnit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

/**
 * Halaman disposisi milik satu unit layanan.
 *
 * Tiga halaman baca (daftar, detail, arsip) dan satu tulis (kirim jawaban)
 * dikerjakan di sini karena ketiganya berbagi satu aturan yang sama: tiket
 * harus benar-benar ditugaskan ke unit yang ada di URL. Penguncian itu
 * dilakukan lewat parameter unit pada App\Support\DaftarPengaduan dan
 * App\Support\DetailPengaduan, bukan lewat query di template.
 *
 * Aksesnya dijaga auth dan PastikanPic pada grup /unit, lalu dipersempit
 * PastikanUnitSendiri yang menolak unit di luar penugasan petugas dan
 * PastikanUnitAktif yang menolak unit non-aktif. Perintah tulis, yaitu
 * mengirim jawaban lewat App\Support\JawabanUnit, tetap dibatasi
 * throttle:20,1 sebagai lapisan kedua.
 *
 * Kewenangannya berbeda dari konsol humas: unit tidak memindahkan tahap,
 * tidak menutup tiket, dan tidak mengubah disposisi unit lain.
 */
class DisposisiController extends Controller
{
    /** Daftar disposisi yang masuk ke unit ini, tersaring dari URL. */
    public function index(Request $request, MasterUnit $unit): View
    {
        return view('unit.disposisi.index', [
            'unit' => $unit,
            'notifikasiUnit' => new NotifikasiUnit($unit, $request->user()),
            'daftar' => DaftarPengaduan::dariRequest(
                $request,
                terkunci: $unit,
                ruteDaftar: 'unit.disposisi.index',
                ruteTiket: 'unit.disposisi.show',
            ),
        ]);
    }

    /**
     * Detail satu tiket milik unit ini beserta form jawaban ke humas.
     *
     * Kode tiket yang bukan milik unit dibiarkan 404, supaya menebak kode
     * tiket tidak membuka halaman milik unit lain.
     */
    public function show(Request $request, MasterUnit $unit, string $kode): View
    {
        return view('unit.disposisi.show', [
            'unit' => $unit,
            'notifikasiUnit' => new NotifikasiUnit($unit, $request->user()),
            'detail' => DetailPengaduan::dariKode($kode, $unit),
        ]);
    }

    /**
     * Ekspor lembar disposisi tiket ke PDF untuk unit.
     */
    public function eksporDisposisiPdf(MasterUnit $unit, string $kode)
    {
        $detail = DetailPengaduan::dariKode($kode, $unit);

        if (! $detail->unit['tertaut']) {
            return back()->withErrors(['kode' => 'Tiket ini belum didisposisikan ke unit, tidak ada lembar disposisi.']);
        }

        $pdf = Pdf::loadView('exports.lembar-disposisi', ['detail' => $detail])
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => true,
            ]);

        $namaFile = 'lembar-disposisi-'.$detail->kode().'-'.now()->format('Ymd').'.pdf';

        return $pdf->download($namaFile);
    }

    /** Riwayat dan arsip disposisi unit: tiket yang sudah tuntas ditangani. */
    public function arsip(Request $request, MasterUnit $unit): View
    {
        return view('unit.disposisi.arsip', [
            'unit' => $unit,
            'notifikasiUnit' => new NotifikasiUnit($unit, $request->user()),
            'daftar' => DaftarPengaduan::dariRequest(
                $request,
                terkunci: $unit,
                kunciTahap: StatusPengaduan::Selesai,
                ruteDaftar: 'unit.disposisi.arsip',
                ruteTiket: 'unit.disposisi.show',
            ),
        ]);
    }

    /** Terima jawaban PIC unit, lalu kembali ke halaman detail tiket itu. */
    public function kirim(Request $request, MasterUnit $unit, string $kode): RedirectResponse
    {
        $pengaduan = Pengaduan::query()
            ->where('master_unit_id', $unit->id)
            // Sama seperti halaman detail: tiket yang belum diteruskan Humas
            // dianggap bukan milik unit ini, jadi jawabannya ditolak 404.
            ->sudahDiteruskanHumas()
            ->where('kode_tiket', $kode)
            ->firstOrFail();

        $validated = $request->validate([
            'isi' => ['required', 'string', 'max:2000'],
            'lampiran' => ['nullable', 'array', 'max:5'],
            'lampiran.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'isi.required' => 'Tuliskan jawaban unit lebih dulu sebelum dikirim.',
            'isi.max' => 'Jawaban unit maksimal 2000 karakter.',
            'lampiran.max' => 'Maksimal 5 lampiran per pesan.',
            'lampiran.*.mimes' => 'Lampiran hanya boleh berformat JPG, PNG, atau PDF.',
            'lampiran.*.max' => 'Ukuran maksimal tiap lampiran adalah 4 MB.',
        ]);

        try {
            JawabanUnit::kirim(
                $unit,
                $pengaduan,
                $validated['isi'],
                $request->file('lampiran', []) ?? [],
            );
        } catch (RuntimeException $alasan) {
            // Alasan penolakan ditulis dengan nama field yang dipakai template,
            // supaya pesan salahnya muncul tepat di atas formulirnya.
            throw ValidationException::withMessages([
                'isi' => $alasan->getMessage(),
            ]);
        }

        return redirect()
            ->route('unit.disposisi.show', ['unit' => $unit, 'kode' => $pengaduan->kode_tiket])
            ->with('sukses', 'Pesan unit terkirim dan tiket menunggu racikan humas.');
    }
}
