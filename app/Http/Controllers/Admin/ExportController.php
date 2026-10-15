<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PengaduanExport;
use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use App\Support\DetailPengaduan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    /**
     * Ekspor daftar pengaduan ke Excel.
     *
     * Parameter query sama dengan daftar pengaduan agar filter konsisten.
     */
    public function pengaduanExcel(Request $request)
    {
        $export = new PengaduanExport(
            statusFilter: $request->query('status'),
            unitFilter: $request->query('unit'),
            kategoriFilter: $request->query('kategori'),
            search: $request->query('cari'),
        );

        $namaFile = 'pengaduan-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download($export, $namaFile);
    }

    /**
     * Ekspor lembar disposisi tiket ke PDF.
     */
    public function lembarDisposisiPdf(string $kode)
    {
        $detail = DetailPengaduan::dariKode($kode);

        if (! $detail->unit['tertaut']) {
            return back()->withErrors(['kode' => 'Tiket ini belum didisposisikan ke unit, tidak ada lembar disposisi.']);
        }

        $pdf = Pdf::loadView('exports.lembar-disposisi', ['detail' => $detail])
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => true,
            ]);

        $namaFile = 'lembar-disposisi-'.$detail->kode().'-'.now()->format('Ymd').'.pdf';

        return $pdf->download($namaFile);
    }

    /**
     * Pratinjau lembar disposisi di browser (untuk tombol Cetak).
     */
    public function lembarDisposisiPreview(string $kode)
    {
        $detail = DetailPengaduan::dariKode($kode);

        if (! $detail->unit['tertaut']) {
            return back()->withErrors(['kode' => 'Tiket ini belum didisposisikan ke unit, tidak ada lembar disposisi.']);
        }

        $pdf = Pdf::loadView('exports.lembar-disposisi', ['detail' => $detail])
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => true,
            ]);

        return $pdf->stream('lembar-disposisi-'.$detail->kode().'.pdf');
    }
}
