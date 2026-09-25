<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPengaduan;
use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PengaduanController extends Controller
{
    public function index(Request $request)
    {
        $status = StatusPengaduan::dariNilai((string) $request->query('status'));

        $daftar = Pengaduan::query()
            ->withCount('pesan')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.pengaduan.index', [
            'daftar' => $daftar,
            'status' => $status,
            'tahap' => StatusPengaduan::cases(),
        ]);
    }

    public function show(string $kode)
    {
        $pengaduan = Pengaduan::where('kode_tiket', $kode)->firstOrFail();

        return view('admin.pengaduan.show', [
            'pengaduan' => $pengaduan,
            'tahap' => StatusPengaduan::cases(),
            'prosedur' => config('pengaduan.prosedur'),
        ]);
    }

    public function ubahStatus(Request $request, string $kode)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(StatusPengaduan::class)],
        ]);

        $pengaduan = Pengaduan::where('kode_tiket', $kode)->firstOrFail();
        $pengaduan->update(['status' => StatusPengaduan::from($validated['status'])]);

        return redirect()
            ->route('admin.pengaduan.show', $kode)
            ->with('sukses', 'Status pengaduan diperbarui menjadi '.StatusPengaduan::from($validated['status'])->label().'.');
    }

    public function balas(Request $request, string $kode)
    {
        $validated = $request->validate([
            'isi' => ['required', 'string', 'max:2000'],
            'lampiran' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'isi.required' => 'Tuliskan balasan terlebih dahulu.',
            'lampiran.mimes' => 'Lampiran hanya boleh berformat JPG, PNG, atau PDF.',
            'lampiran.max' => 'Ukuran maksimal lampiran adalah 5 MB.',
        ]);

        $pengaduan = Pengaduan::where('kode_tiket', $kode)->firstOrFail();
        $pengaduan->pesan()->create([
            'peran' => 'admin',
            'isi' => $validated['isi'],
            'lampiran' => $request->file('lampiran')?->store('lampiran', 'public'),
        ]);

        return redirect()->route('admin.pengaduan.show', $kode)->with('sukses', 'Balasan sudah dikirim ke pelapor.');
    }
}
