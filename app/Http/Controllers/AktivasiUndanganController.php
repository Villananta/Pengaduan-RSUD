<?php

namespace App\Http\Controllers;

use App\Support\UndanganPic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman aktivasi akun PIC yang datang dari tautan undangan email.
 *
 * Rute publik tanpa login seperti halaman pelapor: tautan itu sendiri
 * yang menjadi bukti bahwa pembuka adalah pemilik email PIC, jadi tidak
 * ada sesi yang perlu diperiksa di sini.
 */
class AktivasiUndanganController extends Controller
{
    /**
     * Tampilkan formulir sandi sesuai kondisi token yang dibuka.
     *
     * Tiga keadaan dipisah supaya pesan di layar bisa jujur: token yang
     * tidak dikenal, token yang sudah lewat masa berlaku, dan token sehat
     * yang siap dipakai membuat akun.
     */
    public function tampil(string $token): View
    {
        $unit = UndanganPic::cari($token);

        return view('aktivasi.undangan', [
            'unit' => $unit,
            'berlaku' => $unit !== null && UndanganPic::masihBerlaku($unit),
            'tujuan' => $unit === null ? null : UndanganPic::tujuan($unit),
        ]);
    }

    /** Terima data sandi dari PIC lalu aktifkan akunnya. */
    public function simpan(Request $request, string $token): RedirectResponse
    {
        $unit = UndanganPic::cari($token);

        if ($unit === null || ! UndanganPic::masihBerlaku($unit)) {
            return redirect()
                ->route('aktivasi.undangan', ['token' => $token])
                ->withErrors(['undangan' => 'Tautan undangan ini sudah tidak berlaku. Minta admin mengirim undangan baru.']);
        }

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'kata_sandi' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nama.max' => 'Nama lengkap maksimal 120 karakter.',
            'kata_sandi.required' => 'Kata sandi wajib diisi.',
            'kata_sandi.min' => 'Kata sandi minimal 8 karakter.',
            'kata_sandi.confirmed' => 'Konfirmasi kata sandi tidak sama dengan kata sandi.',
        ]);

        UndanganPic::aktivasi($unit, $data['nama'], $data['kata_sandi']);

        // Halaman masuknya sudah ada, tetapi alamatnya sengaja tidak
        // dipublikasikan, jadi suksesnya tetap ditandai lewat kunci flash
        // dan alamat masuk disampaikan admin secara terpisah. Kunci flash
        // dipisah dari 'sukses' tombol admin supaya admin yang membuka
        // tautan undangan di browser yang sama tidak melihat panel berhasil
        // milik halaman master unit.
        return redirect()
            ->route('aktivasi.undangan', ['token' => $token])
            ->with('aktivasi_sukses', 'Akun PIC untuk '.$unit->namaLengkap().' berhasil diaktifkan. Simpan sandi yang baru Anda buat, lalu minta alamat masuk konsol unit ke admin humas.');
    }
}
