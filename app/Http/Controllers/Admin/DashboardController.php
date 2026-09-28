<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPengaduan;
use App\Http\Controllers\Controller;
use App\Support\Dashboard;
use App\Support\Sla;
use App\Support\StatistikDashboard;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $unitTerbebani = StatistikDashboard::unitTerbebani();

        $dashboard = new Dashboard(
            perTahap: StatistikDashboard::jumlahPerTahap(),
            kepatuhan: StatistikDashboard::kepatuhanSlaPersen(),
            rataRata: StatistikDashboard::rataRataHari(),
            aktif: StatistikDashboard::aktif(),
            perluTindakan: StatistikDashboard::perluTindakan(),
            unitTerbebani: $unitTerbebani,
            puncakBeban: $unitTerbebani->max('beban_aktif') ?? 0,
            kritis: StatistikDashboard::ringkasanKritis(),
            statusKoneksi: StatistikDashboard::statusKoneksi(),
            unitTerhubung: StatistikDashboard::unitTerhubung(),
            hariKerja: Sla::hariKerja(),
            hariInvestigasi: Sla::hariInvestigasi(),
        );

        return view('admin.dashboard', [
            'dashboard' => $dashboard,
            'tahap' => StatusPengaduan::cases(),
        ]);
    }
}
