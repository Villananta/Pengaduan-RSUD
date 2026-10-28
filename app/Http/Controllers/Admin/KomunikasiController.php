<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KategoriPengaduan;
use App\Enums\TingkatKeparahan;
use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use App\Services\KasusBeratService;
use App\Services\KomunikasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class KomunikasiController extends Controller
{
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

        KomunikasiService::balasPelapor(
            $pengaduan,
            $validated['isi'],
            $request->file('lampiran', []) ?? [],
        );

        return $this->kembali($pengaduan, 'Balasan terkirim ke pelapor.');
    }

    /**
     * Kirim pesan admin humas ke kanal koordinasi unit.
     *
     * Obrolan ini tidak mengubah tahap tiket dan tidak terlihat oleh pelapor;
     * tujuannya hanya menjadi pengganti form jawaban unit yang dulu satu
     * pintu, supaya humas bisa membalas PIC unit dari halaman yang sama.
     */
    public function koordinasi(Request $request, string $kode): RedirectResponse
    {
        $pengaduan = $this->cariTiket($kode);

        $validated = $request->validate([
            'isi' => ['required', 'string', 'max:2000'],
            'lampiran' => ['nullable', 'array', 'max:5'],
            'lampiran.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'isi.required' => 'Tuliskan pesan koordinasi lebih dulu sebelum dikirim.',
            'isi.max' => 'Pesan koordinasi maksimal 2000 karakter.',
            'lampiran.max' => 'Maksimal 5 lampiran per pesan.',
            'lampiran.*.mimes' => 'Lampiran hanya boleh berformat JPG, PNG, atau PDF.',
            'lampiran.*.max' => 'Ukuran maksimal tiap lampiran adalah 4 MB.',
        ]);

        try {
            KomunikasiService::koordinasiUnit(
                $pengaduan,
                $validated['isi'],
                $request->file('lampiran', []) ?? [],
            );
        } catch (RuntimeException $alasan) {
            // Tiket tanpa unit memang tidak punya kanal koordinasi, jadi
            // penolakannya ditulis ke folder isi agar muncul di atas form.
            throw ValidationException::withMessages([
                'isi' => $alasan->getMessage(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages([
                'isi' => 'Terjadi kesalahan saat mengirim koordinasi. Silakan coba lagi.',
            ]);
        }

        return $this->kembali($pengaduan, 'Pesan koordinasi terkirim ke unit.');
    }

    /**
     * Kirim nudge (follow up manual) ke unit yang menangani tiket.
     */
    public function nudge(Request $request, string $kode): RedirectResponse
    {
        $pengaduan = $this->cariTiket($kode);

        $validated = $request->validate([
            'catatan' => ['required', 'string', 'max:500'],
        ], [
            'catatan.required' => 'Tuliskan catatan follow up untuk unit.',
            'catatan.max' => 'Catatan maksimal 500 karakter.',
        ]);

        try {
            KomunikasiService::nudgeUnit(
                $pengaduan,
                $validated['catatan'],
            );
        } catch (RuntimeException $alasan) {
            throw ValidationException::withMessages([
                'catatan' => $alasan->getMessage(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages([
                'catatan' => 'Terjadi kesalahan saat mengirim nudge. Silakan coba lagi.',
            ]);
        }

        return $this->kembali($pengaduan, 'Nudge terkirim ke unit '.($pengaduan->masterUnit?->namaLengkap() ?? '—').'.');
    }

    /**
     * Nyalakan atau matikan penandaan kasus berat beserta ekstensi SLA-nya.
     */
    public function kasusBerat(Request $request, string $kode): RedirectResponse
    {
        $pengaduan = $this->cariTiket($kode);

        $validated = $request->validate([
            'aktif' => ['required', 'boolean'],
        ], [
            'aktif.required' => 'Status kasus berat tidak terbaca.',
        ]);

        $aktif = $request->boolean('aktif');

        KasusBeratService::tandaiKasusBerat($pengaduan, $aktif, $request->user()?->id);

        return $this->kembali(
            $pengaduan,
            $aktif
                ? 'Tiket ditandai kasus berat, target penyelesaian digeser.'
                : 'Penandaan kasus berat dilepas, target kembali ke standar.'
        );
    }

    /**
     * Tetapkan tingkat keparahan tiket.
     *
     * Hanya penanda warna dan filter di konsol. Target SLA sengaja tidak
     * ikut bergeser supaya keputusan admin tidak mengubah janji waktu yang
     * sudah dihitung untuk pelapor.
     */
    public function keparahan(Request $request, string $kode): RedirectResponse
    {
        $pengaduan = $this->cariTiket($kode);

        $validated = $request->validate([
            'tingkat' => ['required', Rule::enum(TingkatKeparahan::class)],
        ], [
            'tingkat.required' => 'Tingkat keparahan tidak terbaca.',
            'tingkat.enum' => 'Tingkat keparahan tidak dikenal.',
        ]);

        $tingkat = TingkatKeparahan::from($validated['tingkat']);

        $pengaduan->aturTingkatKeparahan($tingkat, auth()->id());

        return $this->kembali($pengaduan, 'Tingkat keparahan ditetapkan: '.$tingkat->label().'.');
    }

    /**
     * Tetapkan kategori tiket.
     *
     * Pelapor tidak lagi memilih kategori di portal, jadi humas yang
     * memutuskan setelah membaca kronologi. Perubahannya ikut dicatat di
     * riwayat supaya keputusan itu bisa ditelusuri.
     */
    public function kategori(Request $request, string $kode): RedirectResponse
    {
        $pengaduan = $this->cariTiket($kode);

        $validated = $request->validate([
            'kategori' => ['required', Rule::enum(KategoriPengaduan::class)],
        ], [
            'kategori.required' => 'Pilih kategori pengaduan lebih dulu.',
            'kategori.enum' => 'Kategori pengaduan tidak dikenal.',
        ]);

        $kategori = KategoriPengaduan::from($validated['kategori']);

        $pengaduan->aturKategori($kategori, auth()->id());

        return $this->kembali($pengaduan, 'Kategori pengaduan ditetapkan: '.$kategori->label().'.');
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
