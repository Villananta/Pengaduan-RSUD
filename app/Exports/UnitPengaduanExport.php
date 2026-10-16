<?php

namespace App\Exports;

use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UnitPengaduanExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        protected MasterUnit $unit,
        protected string $tipe, // 'disposisi' | 'arsip'
    ) {}

    public function collection(): Collection
    {
        return Pengaduan::query()
            ->where('master_unit_id', $this->unit->id)
            ->when($this->tipe === 'arsip', fn ($q) => $q->where('status', 'selesai'))
            ->when($this->tipe === 'disposisi', fn ($q) => $q->where('status', '!=', 'selesai'))
            ->with(['masterUnit'])
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'Kode Tiket',
            'Tanggal',
            'Pelapor',
            'NRM',
            'Kategori',
            'Unit',
            'Subjek',
            'Status',
            'Sisa SLA',
            'Target SLA',
        ];
    }

    public function map($pengaduan): array
    {
        return [
            $pengaduan->kode_tiket,
            $pengaduan->created_at->format('d/m/Y'),
            $pengaduan->nama_lengkap,
            $pengaduan->nrm,
            $pengaduan->kategori->label(),
            $pengaduan->namaUnit(),
            $pengaduan->subjek,
            $pengaduan->status->label(),
            $pengaduan->sisaHariSla().' hari',
            $pengaduan->targetSla()->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']], 'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]],
        ];
    }
}