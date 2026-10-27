<?php

namespace App\Exports;

use App\Models\Pengaduan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PengaduanExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        protected ?string $statusFilter = null,
        protected ?string $unitFilter = null,
        protected ?string $kategoriFilter = null,
        protected ?string $search = null,
    ) {}

    public function collection(): Collection
    {
        $query = Pengaduan::query()
            ->with(['masterUnit'])
            ->latest();

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->unitFilter) {
            $query->whereHas('masterUnit', fn ($q) => $q->where('kode', $this->unitFilter));
        }

        // "belum" bukan nilai enum: itu penanda dari filter daftar untuk tiket
        // yang kategorinya belum ditetapkan humas. Tanpa cabang ini, ekspor
        // yang difilter "Belum Ditentukan" akan mencari kolom berisi teks
        // "belum" dan menghasilkan berkas kosong.
        if ($this->kategoriFilter === 'belum') {
            $query->whereNull('kategori');
        } elseif ($this->kategoriFilter) {
            $query->where('kategori', $this->kategoriFilter);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('kode_tiket', 'like', "%{$this->search}%")
                    ->orWhere('nama_lengkap', 'like', "%{$this->search}%")
                    ->orWhere('nrm', 'like', "%{$this->search}%")
                    ->orWhere('subjek', 'like', "%{$this->search}%");
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Kode Tiket',
            'Tanggal Masuk',
            'Kategori',
            'Nama Pelapor',
            'NRM',
            'No. WA',
            'Email',
            'Unit',
            'Subjek',
            'Status',
            'Tahap Investigasi',
            'Zona SLA',
            'Hari ke-',
            'Kasus Berat',
            'Dibuat Pada',
        ];
    }

    public function map($pengaduan): array
    {
        $investigasi = $pengaduan->statusInvestigasi()->label();
        $sla = $pengaduan->sla();

        return [
            $pengaduan->kode_tiket,
            $pengaduan->created_at->format('d/m/Y H:i'),
            $pengaduan->labelKategori(),
            $pengaduan->nama_lengkap,
            $pengaduan->nrm,
            $pengaduan->no_wa,
            $pengaduan->email,
            $pengaduan->masterUnit?->namaLengkap() ?? $pengaduan->unit,
            $pengaduan->subjek,
            $pengaduan->status->label(),
            $investigasi,
            $sla['zona'],
            $sla['hariKe'],
            $pengaduan->kasus_berat ? 'Ya' : 'Tidak',
            $pengaduan->created_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']], 'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]],
        ];
    }
}
