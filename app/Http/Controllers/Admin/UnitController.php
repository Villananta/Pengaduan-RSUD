<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KategoriUnit;
use App\Enums\StatusAksesUnit;
use App\Http\Controllers\Controller;
use App\Models\MasterUnit;
use App\Support\DaftarUnit;
use App\Support\PemeliharaanUnit;
use App\Support\Sla;
use App\Support\UndanganPic;
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
        $data = $this->validasi($request);

        $unit = PemeliharaanUnit::simpan($data);

        return redirect()
            ->route('admin.unit.index')
            ->with('sukses', 'Unit '.$unit->namaLengkap().' sudah ditambahkan ke master data.');
    }

    /** Formulir ubah unit yang sudah ada. */
    public function edit(MasterUnit $unit): View
    {
        return view('admin.unit.form', $this->isiFormulir($unit));
    }

    /** Simpan perubahan unit dari formulir ubah. */
    public function update(Request $request, MasterUnit $unit): RedirectResponse
    {
        $data = $this->validasi($request, $unit);

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
    public function status(MasterUnit $unit): RedirectResponse
    {
        PemeliharaanUnit::alihStatus($unit);

        return back()->with(
            'sukses',
            $unit->aktif
                ? $unit->namaLengkap().' sudah diaktifkan kembali.'
                : $unit->namaLengkap().' dinonaktifkan dan tidak bisa dipilih lagi.',
        );
    }

    /**
     * Kirim undangan aktivasi ke PIC semua unit yang akunnya belum aktif.
     *
     * Tombolnya massal, bukan per baris: undangan hanya berguna kalau
     * seluruh unit yang alamat emailnya terisi ikut terjangkau, dan PIC
     * satu unit tidak boleh tertinggal hanya karena admin lupa menyentuh
     * satu baris di tabel.
     */
    public function kirimUndangan(): RedirectResponse
    {
        $hasil = UndanganPic::kirimMassal();

        return back()->with(
            'sukses',
            $hasil['terkirim'] > 0
                ? $hasil['terkirim'].' undangan aktivasi dikirim ke PIC; '.$hasil['dilewat'].' unit dilewati karena alamat emailnya kosong atau akunnya sudah aktif.'
                : 'Tidak ada undangan yang dikirim: semua unit sudah berakun aktif atau belum punya alamat email.',
        );
    }

    /**
     * Validasi formulir unit dengan kode yang sudah diseragamkan.
     *
     * Kode unit tidak bisa diubah setelah unit dibuat, dan input kode di form
     * ubah sengaja dinonaktifkan. Browser tidak mengirim input yang disabled,
     * jadi kode yang tersimpan dipakai lagi; tanpa itu aturan required selalu
     * menolak setiap penyimpanan dari halaman ubah.
     *
     * Kotak centang "Unit Aktif" juga tidak mengirim apa pun saat dilepas.
     * Nilainya dijadikan boolean lebih dulu supaya pilihan "tidak aktif"
     * tersimpan sebagai false, bukan gagal aturan required.
     *
     * Normalisasi kode dilakukan sebelum validate(), bukan sesudahnya, supaya
     * pemeriksaan keunikan membaca bentuk yang sama dengan yang ditulis ke
     * database. Kode "ifp-01" yang lolos di sini lalu berubah jadi "IFP-01"
     * akan menabrak index unik dan muncul sebagai galat 500.
     *
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?MasterUnit $unit = null): array
    {
        $request->merge([
            'kode' => PemeliharaanUnit::normalisasiKode($unit?->kode ?? $request->input('kode')),
            'aktif' => $request->boolean('aktif'),
        ]);

        return $request->validate(PemeliharaanUnit::aturan($unit));
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
