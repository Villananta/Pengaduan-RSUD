<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $judul }} - {{ $unit->namaLengkap() }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; margin: 20px; }
        h1 { text-align: center; color: #1E3A5F; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background-color: #1E3A5F; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .text-center { text-align: center; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-diterima { background: #e3f2fd; color: #1565c0; }
        .badge-diproses { background: #e8eaf6; color: #3f51b5; }
        .badge-revisi { background: #fce4ec; color: #c62828; }
        .badge-selesai { background: #e8f5e9; color: #2e7d32; }
        .footer { margin-top: 30px; text-align: center; color: #999; font-size: 10px; }
    </style>
</head>
<body>
    <h1>{{ $judul }}</h1>
    <p class="subtitle">{{ $unit->namaLengkap() }} | Dicetak: {{ now()->format('d/m/Y H:i') }} WIB</p>

    <table>
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>Kode Tiket</th>
                <th>Tanggal</th>
                <th>Pelapor</th>
                <th>NRM</th>
                <th>Kategori</th>
                <th>Subjek</th>
                <th class="text-center">Status</th>
                <th class="text-center">Sisa SLA</th>
                <th class="text-center">Target SLA</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pengaduan as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->kode_tiket }}</td>
                    <td>{{ $item->created_at->format('d/m/Y') }}</td>
                    <td>{{ $item->nama_lengkap }}</td>
                    <td>{{ $item->nrm }}</td>
                    <td>{{ $item->kategori->label() }}</td>
                    <td>{{ $item->subjek }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $item->status->value }}">{{ $item->status->label() }}</span>
                    </td>
                    <td class="text-center">{{ $item->sisaHariSla() }} hari</td>
                    <td class="text-center">{{ $item->targetSla()->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Sistem Pengaduan RSUD Dr. Soetomo | Halaman ini dicetak otomatis
    </div>
</body>
</html>