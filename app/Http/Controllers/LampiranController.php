<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LampiranController extends Controller
{
    private const SESI_TERVERIFIKASI = 'tiket_terverifikasi';

    public function pengaduan(Request $request, string $kode, string $file)
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

    public function pesan(Request $request, PesanPengaduan $pesan, string $file)
    {
        $pengaduan = $pesan->pengaduan;

        $this->otorisasiPengaduan($request, $pengaduan);

        // Pastikan pesan boleh diakses oleh pelapor jika berasal dari jalur pelapor
        // Namun admin dan unit tetap bisa mengakses sesuai otorisasi pengaduan
        $user = $request->user();
        if (! $user) {
            // Pelapor hanya boleh melihat pesan di jalur pelapor
            if (! $pesan->jalurPelapor()) {
                abort(404);
            }
        } else {
            // Jika unit, pastikan pesan ini terkait dengan unit yang berhak
            if ($user->berperanUnit()) {
                $unit = $pengaduan->masterUnit;
                if (! $unit || ! $user->terdaftarDiUnit($unit)) {
                    abort(403);
                }
                // Unit hanya bisa melihat pesan jika sudah diteruskan humas
                if ($pengaduan->status->value === \App\Enums\StatusPengaduan::Diterima->value) {
                    abort(404);
                }
            }
            // Admin boleh melihat semua pesan terkait pengaduannya
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
                if ($pengaduan->status->value === \App\Enums\StatusPengaduan::Diterima->value) {
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

    private function tampilkanBerkas(string $path)
    {
        $disk = Storage::disk(config('pengaduan.disk_lampiran'));

        if (! $disk->exists($path)) {
            abort(404);
        }

        try {
            $mimeType = $disk->mimeType($path);
        } catch (\Throwable $e) {
            $mimeType = 'application/octet-stream';
        }

        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ];

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
