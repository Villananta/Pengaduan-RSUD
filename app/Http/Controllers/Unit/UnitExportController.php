<?php

namespace App\Http\Controllers\Unit;

use App\Exports\UnitPengaduanExport;
use App\Http\Controllers\Controller;
use App\Models\MasterUnit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class UnitExportController extends Controller
{
    /**
     * Export daftar disposisi (tiket aktif) unit ke Excel.
     */
    public function disposisiExcel(Request $request, MasterUnit $unit)
    {
        $filename = 'disposisi-'.$unit->kode.'-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new UnitPengaduanExport($unit, 'disposisi'), $filename);
    }

    /**
     * Export arsip (tiket selesai) unit ke Excel.
     */
    public function arsipExcel(Request $request, MasterUnit $unit)
    {
        $filename = 'arsip-'.$unit->kode.'-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new UnitPengaduanExport($unit, 'arsip'), $filename);
    }

    /**
     * Export daftar disposisi (tiket aktif) unit ke PDF.
     */
    public function disposisiPdf(Request $request, MasterUnit $unit)
    {
        // Query yang sama dengan berkas Excel, supaya aturan "tiket yang
        // sudah diteruskan humas" tidak punya dua versi di dua ekspor.
        $pengaduan = (new UnitPengaduanExport($unit, 'disposisi'))->collection();

        $pdf = Pdf::loadView('exports.unit-daftar', [
            'unit' => $unit,
            'pengaduan' => $pengaduan,
            'tipe' => 'disposisi',
            'judul' => 'Daftar Disposisi Aktif',
        ])->setPaper('A4', 'landscape');

        return $pdf->stream('disposisi-'.$unit->kode.'-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Export arsip (tiket selesai) unit ke PDF.
     */
    public function arsipPdf(Request $request, MasterUnit $unit)
    {
        $pengaduan = (new UnitPengaduanExport($unit, 'arsip'))->collection();

        $pdf = Pdf::loadView('exports.unit-daftar', [
            'unit' => $unit,
            'pengaduan' => $pengaduan,
            'tipe' => 'arsip',
            'judul' => 'Arsip Tiket Selesai',
        ])->setPaper('A4', 'landscape');

        return $pdf->stream('arsip-'.$unit->kode.'-'.now()->format('Ymd-His').'.pdf');
    }
}
