<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Services\DisposisiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class DisposisiController extends Controller
{
    /**
     * Tugaskan tiket ke instalasi teknis yang dipilih admin.
     */
    public function store(Request $request, string $kode): RedirectResponse
    {
        $pengaduan = $this->cariTiket($kode);

        $validated = $request->validate([
            'unit' => ['required', 'string', 'exists:master_units,kode'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'unit.required' => 'Pilih unit tujuan lebih dulu.',
            'unit.exists' => 'Unit tujuan tidak ditemukan pada master data.',
            'catatan.max' => 'Catatan untuk unit maksimal 500 karakter.',
        ]);

        $unit = MasterUnit::where('kode', $validated['unit'])->firstOrFail();

        try {
            DisposisiService::disposisiKeUnit(
                $pengaduan,
                $unit,
                $validated['catatan'] ?? null,
            );
        } catch (RuntimeException $alasan) {
            // Alasan penolakan diarahkan ke field unit supaya muncul di dalam
            // kartu keputusan, dekat dengan pilihan unitnya.
            throw ValidationException::withMessages([
                'unit' => $alasan->getMessage(),
            ]);
        }

        return $this->kembali($pengaduan, 'Pengaduan didisposisikan ke '.$unit->namaLengkap().'.');
    }

    /**
     * Jalur 2: humas menangani tiket langsung tanpa melibatkan unit teknis.
     */
    public function tanganiLangsung(Request $request, string $kode): RedirectResponse
    {
        $pengaduan = $this->cariTiket($kode);

        $validated = $request->validate([
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'catatan.max' => 'Catatan penanganan maksimal 500 karakter.',
        ]);

        try {
            DisposisiService::tanganiLangsung(
                $pengaduan,
                $validated['catatan'] ?? null,
            );
        } catch (RuntimeException $alasan) {
            throw ValidationException::withMessages([
                'catatan' => $alasan->getMessage(),
            ]);
        }

        return $this->kembali($pengaduan, 'Pengaduan ditangani langsung humas dan dilepas dari disposisi unit.');
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
