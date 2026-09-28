<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPengaduan;
use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use App\Support\StatistikDashboard;
use App\Support\StatistikPengaduan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PengaduanController extends Controller
{
    public function index(Request $request): View
    {
        $status = StatusPengaduan::dariNilai($request->query('status'));
        $cari = trim((string) $request->query('q', ''));
        $telaah = $request->boolean('telaah');

        $daftar = Pengaduan::query()
            ->with('masterUnit')
            ->withCount('pesan')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($telaah, fn ($query) => $query->aktif()->whereHas(
                'pesan',
                fn ($q) => $q->where('peran', 'admin')
            ))
            ->when($cari !== '', fn ($query) => $query->where(function ($q) use ($cari) {
                $q->where('kode_tiket', 'like', '%'.$cari.'%')
                    ->orWhere('subjek', 'like', '%'.$cari.'%')
                    ->orWhere('nama_lengkap', 'like', '%'.$cari.'%')
                    ->orWhere('nrm', 'like', '%'.$cari.'%')
                    ->orWhere('unit', 'like', '%'.$cari.'%')
                    ->orWhereHas('masterUnit', fn ($q) => $q
                        ->where('kode', 'like', '%'.$cari.'%')
                        ->orWhere('nama', 'like', '%'.$cari.'%'));
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.pengaduan.index', [
            'daftar' => $daftar,
            'status' => $status,
            'cari' => $cari,
            'telaah' => $telaah,
            'tahap' => StatusPengaduan::cases(),
        ]);
    }

    public function show(string $kode): View
    {
        $pengaduan = Pengaduan::where('kode_tiket', $kode)
            ->with(['pesan', 'riwayatStatus.admin'])
            ->firstOrFail();

        return view('admin.pengaduan.show', [
            'pengaduan' => $pengaduan,
            'tahap' => StatusPengaduan::cases(),
            'prosedur' => config('pengaduan.prosedur'),
            'zona' => $pengaduan->zonaSla(),
            'sisaHari' => $pengaduan->sisaHariSla(),
        ]);
    }

    /**
     * Pindahkan tahap pengaduan.
     *
     * Perpindahan tahap divalidasi terhadap matriks alur, setiap
     * perubahan dicatat pada riwayat, dan waktu penyelesaian diisi
     * otomatis ketika tiket ditutup.
     */
    public function ubahStatus(Request $request, string $kode): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(StatusPengaduan::class)],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'catatan.max' => 'Catatan paling panjang 500 karakter.',
        ]);

        $pengaduan = Pengaduan::where('kode_tiket', $kode)->firstOrFail();
        $tujuan = StatusPengaduan::from($validated['status']);

        if (! $pengaduan->status->bisaBerpindahKe($tujuan)) {
            return back()->withErrors([
                'status' => 'Pengaduan dengan status "'.$pengaduan->status->label().'" tidak bisa langsung dipindahkan ke "'.$tujuan->label().'".',
            ]);
        }

        $dari = $pengaduan->status;
        $sudahSelesai = $dari->selesai() && $tujuan->selesai();

        if ($sudahSelesai) {
            return redirect()
                ->route('admin.pengaduan.show', $kode)
                ->with('sukses', 'Status pengaduan tidak berubah, tiket sudah berstatus selesai.');
        }

        $pengaduan->forceFill([
            'status' => $tujuan,
            'selesai_at' => $tujuan->selesai() ? now() : null,
        ])->save();

        $pengaduan->riwayatStatus()->create([
            'dari' => $dari,
            'ke' => $tujuan,
            'catatan' => $validated['catatan'] ?? null,
            'admin_id' => $request->user()?->id,
        ]);

        StatistikDashboard::lupaCache();
        StatistikPengaduan::lupaCache();

        return redirect()
            ->route('admin.pengaduan.show', $kode)
            ->with('sukses', 'Status pengaduan diperbarui menjadi '.$tujuan->label().'.');
    }

    public function balas(Request $request, string $kode): RedirectResponse
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
            'lampiran' => $request->file('lampiran')?->store('lampiran', config('pengaduan.disk_lampiran', 'public')),
        ]);

        // Balasan admin membuat tiket masuk antrean telaah, jadi angka
        // dashboard harus dihitung ulang, bukan menunggu cache kedaluwarsa.
        StatistikDashboard::lupaCache();

        return redirect()->route('admin.pengaduan.show', $kode)->with('sukses', 'Balasan sudah dikirim ke pelapor.');
    }
}
