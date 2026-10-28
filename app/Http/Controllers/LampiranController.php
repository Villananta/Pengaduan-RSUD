<?php

namespace App\Http\Controllers;

use App\Enums\StatusPengaduan;
use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LampiranController extends Controller
{
    private const SESI_TERVERIFIKASI = 'tiket_terverifikasi';

    /**
     * Jenis berkas yang aman dirender langsung oleh browser.
     *
     * Sisanya dipaksa unduh. Validasi unggahan memang hanya menerima
     * JPG/PNG/PDF, tetapi berkas bisa masuk lewat seeder atau salinan manual
     * yang tidak melewati validasi itu.
     */
    private const EKSTENSI_TAMPIL = ['jpg', 'jpeg', 'png', 'pdf'];

    public function pengaduan(Request $request, string $kode, string $file): StreamedResponse
    {
        $pengaduan = Pengaduan::where('kode_tiket', strtoupper($kode))->firstOrFail();

        $this->otorisasiPengaduan($request, $pengaduan);

        // Cari path yang sesuai di daftar lampiran pengaduan
        $pathDitemukan = null;
        foreach ($pengaduan->daftarLampiran() as $path) {
            if (basename($path) === $file || $path === $file || str_ends_with($path, '/'.$file)) {
                $pathDitemukan = $path;
                break;
            }
        }

        if (! $pathDitemukan) {
            abort(404);
        }

        return $this->tampilkanBerkas($pathDitemukan);
    }

    public function pesan(Request $request, PesanPengaduan $pesan, string $file): StreamedResponse
    {
        $pengaduan = $pesan->pengaduan;

        $this->otorisasiPengaduan($request, $pengaduan);

        // Pelapor yang membuka lewat sesi atau token hanya boleh membaca
        // pesan di jalur pelapor. Hak akses admin dan unit sudah diperiksa
        // otorisasiPengaduan(), jadi tidak perlu diulang di sini.
        if (! $request->user() && ! $pesan->jalurPelapor()) {
            abort(404);
        }

        $pathDitemukan = null;
        foreach ($pesan->daftarLampiran() as $path) {
            if (basename($path) === $file || $path === $file || str_ends_with($path, '/'.$file)) {
                $pathDitemukan = $path;
                break;
            }
        }

        if (! $pathDitemukan) {
            abort(404);
        }

        return $this->tampilkanBerkas($pathDitemukan);
    }

    private function otorisasiPengaduan(Request $request, Pengaduan $pengaduan): void
    {
        $user = $request->user();

        if ($user) {
            if ($user->berperanAdmin()) {
                return;
            }

            if ($user->berperanUnit()) {
                $unit = $pengaduan->masterUnit;
                if (! $unit || ! $user->terdaftarDiUnit($unit)) {
                    abort(403);
                }
                // Hanya tiket yang sudah diteruskan humas yang bisa diakses unit
                if ($pengaduan->status->value === StatusPengaduan::Diterima->value) {
                    abort(404);
                }

                return;
            }

            abort(403);
        }

        // Pelapor - cek session atau token cookie
        $terverifikasi = $request->session()->get(self::SESI_TERVERIFIKASI, []);
        if (($terverifikasi['kode'] ?? null) === $pengaduan->kode_tiket) {
            return;
        }

        $token = $request->cookie('pengaduan_token');
        if ($token) {
            $tiketViaToken = Pengaduan::findByValidAksesToken($token);
            if ($tiketViaToken && $tiketViaToken->kode_tiket === $pengaduan->kode_tiket) {
                return;
            }
        }

        abort(403);
    }

    /**
     * Kirim berkas apa adanya, tanpa membiarkan browser menebak tipenya.
     *
     * nosniff dipasang untuk semua berkas. CSP sandbox hanya untuk yang
     * bukan PDF, karena penampil PDF bawaan browser bisa ikut terblokir
     * kalau aturan itu dipasang di sana.
     */
    private function tampilkanBerkas(string $path): StreamedResponse
    {
        $disk = Storage::disk(config('pengaduan.disk_lampiran'));

        if (! $disk->exists($path)) {
            abort(404);
        }

        $ekstensi = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $bolehTampil = in_array($ekstensi, self::EKSTENSI_TAMPIL, true);

        try {
            $mimeType = $disk->mimeType($path);
        } catch (\Throwable $e) {
            $mimeType = 'application/octet-stream';
        }

        // Tipe yang tidak dikenal juga dipaksa unduh: lebih baik pengguna
        // mengunduh berkas daripada browser memutuskan sendiri cara
        // menampilkannya di origin aplikasi.
        if (! $bolehTampil) {
            $mimeType = 'application/octet-stream';
        }

        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => ($bolehTampil ? 'inline' : 'attachment').'; filename="'.basename($path).'"',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($ekstensi !== 'pdf') {
            $headers['Content-Security-Policy'] = "default-src 'none'; sandbox";
        }

        $stream = $disk->readStream($path);
        if ($stream === false) {
            abort(404);
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, $headers);
    }
}
