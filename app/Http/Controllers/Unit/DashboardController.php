<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\MasterUnit;
use App\Support\DashboardUnit;
use App\Support\Sla;
use App\Support\StatistikUnit;
use Illuminate\View\View;

/**
 * Dashboard unit layanan melalui pintu masuk /unit.
 *
 * Rutenya kini dibungkus auth dan PastikanPic, tetapi unit yang dibuka
 * masih ditentukan dari kode unit di URL — penguncian per-unit sesuai
 * identitas pemakai belum dipasang. Unit non-aktif tetap ditolak lewat
 * middleware App\Http\Middleware\PastikanUnitAktif supaya URL langsung ke
 * unit yang dinonaktifkan tetap 404. Seluruh penghitungan
 * diserahkan ke App\Support\StatistikUnit supaya template tidak menyimpan
 * query, dan angkanya tetap memakai rumus SLA yang sama dengan konsol admin.
 */
class DashboardController extends Controller
{
    /**
     * Halaman pemilih unit sebelum dashboardnya dibuka.
     *
     * Jumlah pengaduan aktif ikut diambil di sini supaya petugas bisa
     * memilih unit dari beban yang terlihat, bukan hanya dari namanya.
     */
    public function pilih(): View
    {
        return view('unit.pilih', [
            'unit' => null,
            'daftarUnit' => MasterUnit::query()
                ->withCount(['pengaduan as beban_aktif' => fn ($q) => $q->aktif()])
                ->orderBy('kode')
                ->get(),
        ]);
    }

    /** Ringkasan pengaduan milik satu unit. */
    public function index(MasterUnit $unit): View
    {
        $ringkasan = StatistikUnit::ringkasan($unit);

        return view('unit.dashboard', [
            'unit' => $unit,
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
                terbaru: StatistikUnit::terbaru($unit, 8),
                hariKerja: Sla::hariKerja(),
                hariInvestigasi: Sla::hariInvestigasi(),
            ),
        ]);
    }
}
