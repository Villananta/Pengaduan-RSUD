<?php

namespace App\Http\Controllers;

use App\Enums\PeranPengguna;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Sesi masuk dan keluar untuk dua peran pengguna.
 *
 * Satu formulir dipakai bersama: admin humas diarahkan ke konsol admin,
 * PIC unit ke konsol unitnya. Halaman ini satu-satunya pintu akun karena
 * pendaftaran mandiri memang tidak ada — akun PIC lahir dari undangan
 * aktivasi, akun admin dari pengelolaan basis data.
 */
class SesiController extends Controller
{
    /** Tampilkan formulir masuk; pengguna yang sudah masuk langsung diarahkan. */
    public function tampil(Request $request): View|RedirectResponse
    {
        if ($request->user() !== null) {
            return $this->arahkan($request->user());
        }

        return view('masuk');
    }

    /** Coba masuk memakai email dan kata sandi. */
    public function coba(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'kata_sandi' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'kata_sandi.required' => 'Kata sandi wajib diisi.',
        ]);

        $ingat = $request->boolean('ingat');

        // Pesan kegagalan sengaja disatukan untuk email yang tidak ada dan
        // sandi yang salah, supaya halaman ini tidak bisa dipakai menebak
        // alamat mana yang sudah terdaftar.
        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['kata_sandi']], $ingat)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();

        return $this->arahkan($request->user());
    }

    /** Akhiri sesi lalu kembali ke halaman pelapor. */
    public function keluar(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('pengaduan.create');
    }

    /** Tujuan sesudah masuk ditentukan peran, bukan pilihan pengguna. */
    private function arahkan(User $pengguna): RedirectResponse
    {
        if ($pengguna->peran !== PeranPengguna::Unit) {
            return redirect()->route('admin.dashboard');
        }

        // Satu akun PIC mengurus satu unit, jadi unit itulah yang langsung
        // dibuka. Akun yang belum ditautkan tidak diberi daftar unit untuk
        // dipilih — halaman penjelasan yang lebih jujur, dan tidak ada unit
        // yang bisa dimasuki diam-diam lewat halaman pemilih lama.
        $unit = $pengguna->unit()->orderBy('id')->first();

        return $unit === null
            ? redirect()->route('unit.beranda')
            : redirect()->route('unit.dashboard', $unit);
    }
}
