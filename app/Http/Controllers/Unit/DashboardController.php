<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\MasterUnit;
use App\Support\DashboardUnit;
use App\Support\NotifikasiUnit;
use App\Support\Sla;
use App\Support\StatistikUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard unit layanan melalui pintu masuk /unit.
 *
 * Satu akun PIC mengurus satu unit, jadi /unit tidak lagi menawarkan
 * pilihan unit. Kode unit di URL dikunci berlapis: middleware
 * App\Http\Middleware\PastikanUnitSendiri menolak unit milik PIC lain, dan
 * App\Http\Middleware\PastikanUnitAktif menolak unit yang dinonaktifkan
 * supaya URL langsung ke unit tersebut tetap 404. Seluruh penghitungan
 * diserahkan ke App\Support\StatistikUnit supaya template tidak menyimpan
 * query, dan angkanya tetap memakai rumus SLA yang sama dengan konsol admin.
 */
class DashboardController extends Controller
{
    /**
     * Pintu masuk konsol unit.
     *
     * Petugas yang sudah ditautkan langsung dipindahkan ke dashboard
     * unitnya, tanpa singgah ke halaman pemilih. Petugas yang belum
     * ditautkan hanya mendapat halaman penjelasan — bukan daftar unit —
     * supaya tidak ada unit yang bisa dibuka tanpa penugasan.
     */
    public function beranda(Request $request): RedirectResponse|View
    {
        $unit = $request->user()->unit()->orderBy('id')->first();

        if ($unit !== null) {
            return redirect()->route('unit.dashboard', $unit);
        }

        return view('unit.belum-ditautkan', ['unit' => null]);
    }

    /** Ringkasan pengaduan milik satu unit. */
    public function index(Request $request, MasterUnit $unit): View
    {
        $ringkasan = StatistikUnit::ringkasan($unit);

        // Sort dan pagination untuk tabel "Pengaduan Terbaru"
        $urut = in_array($request->query('urut'), ['asc', 'desc']) ? $request->query('urut') : 'desc';
        $perHalaman = in_array((int) $request->query('per_halaman'), [10, 25, 50, 100]) ? (int) $request->query('per_halaman') : 10;
        $halaman = (int) $request->query('halaman_terbaru', 1);

        $terbaruPaginated = StatistikUnit::terbaruPaginated($unit, $perHalaman, $urut, $halaman);

        return view('unit.dashboard', [
            'unit' => $unit,
            'notifikasiUnit' => new NotifikasiUnit($unit, $request->user()),
            'dashboard' => new DashboardUnit(
                unit: $unit,
                perTahap: $ringkasan['per_tahap'],
                total: $ringkasan['total'],
                aktif: $ringkasan['aktif'],
                terlambat: $ringkasan['terlambat'],
                mendek: $ringkasan['mendek'],
                rataRataHariKerja: $ringkasan['rata_rata_hari_kerja'],
                selesaiBulanIni: $ringkasan['selesai_bulan_ini'],
                mendesak: StatistikUnit::mendesak($unit),
                terbaru: $terbaruPaginated,
                hariKerja: Sla::hariKerja(),
                hariInvestigasi: Sla::hariInvestigasi(),
                terbaruUrut: $urut,
                terbaruPerHalaman: $perHalaman,
            ),
        ]);
    }
}
