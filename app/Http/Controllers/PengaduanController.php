<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use Illuminate\Http\Request;

class PengaduanController extends Controller
{
    public function create()
    {
        $metrik = [
            ['nilai' => '1.420+', 'label' => 'Aduan Tertangani', 'sub' => '100% tercatat resmi'],
            ['nilai' => '94.2%', 'label' => 'Kepatuhan SLA', 'sub' => '≤ 12 hari kerja'],
            ['nilai' => '6.8 Hari', 'label' => 'Rata-rata Resolusi', 'sub' => 'Lebih cepat dari SLA'],
            ['nilai' => '88.7%', 'label' => 'Kepuasan Pasca', 'sub' => 'Survei pelapor'],
        ];

        return view('pengaduan.create', compact('metrik'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kategori' => ['required', 'in:fasilitas,medis'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nrm' => ['required', 'string', 'max:255'],
            'no_wa' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'alamat' => ['required', 'string', 'max:1000'],
            'waktu_kejadian' => ['required', 'date'],
            'unit' => ['required', 'string', 'max:255'],
            'subjek' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string', 'max:10000'],
            'lampiran.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'persetujuan' => ['required', 'accepted'],
        ], [
            'persetujuan.accepted' => 'Anda harus menyetujui pernyataan kebenaran data sebelum mengirim aduan.',
            'lampiran.*.mimes' => 'Lampiran hanya boleh berformat JPG, PNG, atau PDF.',
            'lampiran.*.max' => 'Ukuran maksimal tiap lampiran adalah 5 MB.',
        ]);

        $lampiran = [];
        if ($request->hasFile('lampiran')) {
            foreach ($request->file('lampiran') as $file) {
                $lampiran[] = $file->store('lampiran', 'public');
            }
        }

        $pengaduan = Pengaduan::create([
            'kode_tiket' => $this->generateKodeTiket(),
            'kategori' => $validated['kategori'],
            'nama_lengkap' => $validated['nama_lengkap'],
            'nrm' => $validated['nrm'],
            'no_wa' => $validated['no_wa'],
            'email' => $validated['email'],
            'alamat' => $validated['alamat'],
            'waktu_kejadian' => $validated['waktu_kejadian'],
            'unit' => $validated['unit'],
            'subjek' => $validated['subjek'],
            'deskripsi' => $validated['deskripsi'],
            'lampiran' => $lampiran,
            'status' => 'diterima',
        ]);

        return redirect()->route('pengaduan.sukses', $pengaduan->kode_tiket);
    }

    public function sukses(string $kode)
    {
        $pengaduan = Pengaduan::where('kode_tiket', $kode)->firstOrFail();

        return view('pengaduan.sukses', compact('pengaduan'));
    }

    protected function generateKodeTiket(): string
    {
        do {
            $kode = 'ADUAN-'.now()->format('Ymd').'-'.strtoupper(collect(range(1, 5))->map(fn () => chr(random_int(65, 90)))->implode(''));
        } while (Pengaduan::where('kode_tiket', $kode)->exists());

        return $kode;
    }
}
