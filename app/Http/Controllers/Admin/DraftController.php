<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DraftController extends Controller
{
    /**
     * Simpan draf jawaban admin untuk tiket.
     *
     * Draf disimpan di kolom draf_jawaban di tabel pengaduan.
     * Tidak mempengaruhi alur tiket, hanya simpan sementara.
     */
    public function simpan(Request $request, string $kode): JsonResponse
    {
        $pengaduan = Pengaduan::where('kode_tiket', $kode)->firstOrFail();

        $validated = $request->validate([
            'isi' => ['required', 'string', 'max:5000'],
        ], [
            'isi.required' => 'Isi draf tidak boleh kosong.',
            'isi.max' => 'Draf maksimal 5000 karakter.',
        ]);

        $pengaduan->update(['draf_jawaban' => $validated['isi']]);

        return response()->json([
            'sukses' => true,
            'pesan' => 'Draf jawaban tersimpan.',
            'isi' => $pengaduan->draf_jawaban,
        ]);
    }

    /**
     * Ambil draf jawaban admin untuk tiket.
     */
    public function ambil(string $kode): JsonResponse
    {
        $pengaduan = Pengaduan::where('kode_tiket', $kode)->firstOrFail();

        return response()->json([
            'sukses' => true,
            'isi' => $pengaduan->draf_jawaban ?? '',
        ]);
    }

    /**
     * Hapus draf jawaban admin untuk tiket.
     */
    public function hapus(string $kode): JsonResponse
    {
        $pengaduan = Pengaduan::where('kode_tiket', $kode)->firstOrFail();

        $pengaduan->update(['draf_jawaban' => null]);

        return response()->json([
            'sukses' => true,
            'pesan' => 'Draf jawaban dihapus.',
        ]);
    }
}
