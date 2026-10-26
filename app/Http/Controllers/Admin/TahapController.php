<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPengaduan;
use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use App\Services\TahapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class TahapController extends Controller
{
    /**
     * Tombol tahap pada panel bawah: Proses, Selesaikan, atau Revisi.
     *
     * Semua tombol dikirim ke endpoint yang sama; bedanya hanya nilai
     * tujuan, supaya aturan perpindahan tahap tidak tercecer di tiga
     * controller method yang berbeda.
     */
    public function update(Request $request, string $kode): RedirectResponse
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
            TahapService::pindahkanTahap(
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
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages([
                'tujuan' => 'Terjadi kesalahan saat mengubah tahap pengaduan. Silakan coba lagi.',
            ]);
        }

        return $this->kembali($pengaduan, TahapService::pesanTujuan($tujuan));
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
