<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KategoriUnit;
use App\Enums\StatusAksesUnit;
use App\Http\Controllers\Controller;
use App\Models\MasterUnit;
use App\Support\DaftarUnit;
use App\Support\PemeliharaanUnit;
use App\Support\Sla;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman Master Data Unit & Instalasi dan pengelolaannya.
 *
 * Controller ini hanya mengatur alur HTTP. Seluruh query tampilan diserahkan
 * ke App\Support\DaftarUnit dan seluruh aturan penyimpanan ke
 * App\Support\PemeliharaanUnit, supaya template ini tidak memakai query.
 */
class UnitController extends Controller
{
    /** Daftar unit, KPI, dan saring tabel. */
    public function index(Request $request): View
    {
        return view('admin.unit.index', [
            'daftar' => DaftarUnit::dariRequest($request),
            'pilihan' => DaftarUnit::pilihanSaring(),
        ]);
    }

    /** Formulir tambah unit baru. */
    public function create(): View
    {
        return view('admin.unit.form', $this->isiFormulir(null));
    }

    /** Simpan unit baru dari formulir tambah. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(PemeliharaanUnit::aturan());

        $unit = PemeliharaanUnit::simpan($data);

        return redirect()
            ->route('admin.unit.index')
            ->with('sukses', 'Unit '.$unit->namaLengkap().' sudah ditambahkan ke master data.');
    }

    /** Formulir ubah unit yang sudah ada. */
    public function edit(string $kode): View
    {
        return view('admin.unit.form', $this->isiFormulir($this->cariUnit($kode)));
    }

    /** Simpan perubahan unit dari formulir ubah. */
    public function update(Request $request, string $kode): RedirectResponse
    {
        $unit = $this->cariUnit($kode);
        $data = $request->validate(PemeliharaanUnit::aturan($unit));

        PemeliharaanUnit::simpan($data, $unit);

        return redirect()
            ->route('admin.unit.index')
            ->with('sukses', 'Data unit '.$unit->kode.' sudah diperbarui.');
    }

    /**
     * Alih status aktif sebuah unit.
     *
     * Unit nonaktif tidak dihapus supaya tiket lama yang sudah ditugaskan ke
     * unit itu tetap punya nama yang bisa dibaca.
     */
    public function status(string $kode): RedirectResponse
    {
        $unit = $this->cariUnit($kode);

        PemeliharaanUnit::alihStatus($unit);

        return back()->with(
            'sukses',
            $unit->aktif
                ? $unit->namaLengkap().' sudah diaktifkan kembali.'
                : $unit->namaLengkap().' dinonaktifkan dan tidak bisa dipilih lagi.',
        );
    }

    /**
     * Cari unit berdasarkan kode, bukan id angka.
     *
     * Kode unitlah yang dikenal petugas karena kode itu yang tampil di
     * setiap layar. Kalau kodenya tidak ada, jawabannya 404 supaya tautan
     * lama yang sudah kedaluwarsa tidak terlihat seperti halaman kosong.
     */
    private function cariUnit(string $kode): MasterUnit
    {
        return MasterUnit::query()
            ->where('kode', $kode)
            ->firstOrFail();
    }

    /**
     * Isi bersama untuk formulir tambah dan ubah.
     *
     * Beban unit ikut diambil karena panel ringkasan di atas formulir
     * perlu menjelaskan apakah unit yang sedang diubah sedang memegang
     * antrean panjang atau tidak.
     *
     * @return array<string, mixed>
     */
    private function isiFormulir(?MasterUnit $unit): array
    {
        return [
            'unit' => $unit,
            'peran' => DaftarUnit::pilihanPeran(),
            'kategori' => KategoriUnit::cases(),
            'akses' => StatusAksesUnit::cases(),
            'hariInvestigasi' => Sla::hariInvestigasi(),
            'bebanAktif' => $unit?->pengaduan()->aktif()->count() ?? 0,
            'bebanSelesai' => $unit?->pengaduan()->selesai()->count() ?? 0,
        ];
    }
}
