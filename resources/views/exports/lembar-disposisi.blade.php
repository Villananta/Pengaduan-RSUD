@php
    $tiket = $detail->pengaduan;
    $unit = $detail->unit;
    $sla = $detail->sla;
    $instruksi = $detail->instruksiDisposisi();
@endphp

<!DOCTYPE html>

<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Lembar Disposisi {{ $detail->kode() }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; margin: 20px; color: #1f2937; }

        .kop { text-align: center; border-bottom: 2px solid #1E3A5F; padding-bottom: 12px; }
        .kop .instansi { font-size: 15px; font-weight: bold; color: #1E3A5F; }
        .kop .rumah-sakit { font-size: 13px; font-weight: bold; }
        .kop .alamat { font-size: 10px; color: #6b7280; }

        .judul { text-align: center; margin: 20px 0; }
        .judul .nama-surat { font-size: 14px; font-weight: bold; text-decoration: underline; }
        .judul .nomor { font-size: 12px; color: #6b7280; margin-top: 4px; }

        .kotak { border: 1px solid #d1d5db; border-radius: 4px; padding: 10px 12px; margin-bottom: 12px; }
        .kotak .judul-bagian { font-weight: bold; margin-bottom: 6px; }
        .kotak.arahan { border-color: #1E3A5F; background: #f1f5f9; }

        table { width: 100%; border-collapse: collapse; }
        table.identitas td { padding: 3px 0; vertical-align: top; }
        table.identitas td.label { width: 170px; color: #6b7280; }

        .baris-tiga { width: 100%; }
        .baris-tiga td { width: 33%; text-align: center; vertical-align: top; padding: 0 4px; }
        .baris-tiga .judul-kartu { font-size: 10px; color: #6b7280; }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: bold; }
        .badge-diterima, .badge-diproses, .badge-selesai { background: #e8f0e9; color: #1b5e20; }
        .badge-revisi { background: #fdecea; color: #b71c1c; }
        .zona-tepat_waktu { background: #e8f0e9; color: #1b5e20; }
        .zona-mendek { background: #fff4e5; color: #8a5300; }
        .zona-terlambat { background: #fdecea; color: #b71c1c; }

        table.terikat { margin-top: 4px; }
        table.terikat td { padding: 3px 0; }

        table.audit { margin-top: 6px; }
        table.audit th, table.audit td { border: 1px solid #d1d5db; padding: 5px 8px; text-align: left; vertical-align: top; }
        table.audit th { background: #1E3A5F; color: #ffffff; font-size: 10px; }
        table.audit td.catatan { font-style: italic; color: #6b7280; }

        .tanda-tangan { width: 100%; margin-top: 28px; padding-top: 14px; border-top: 1px solid #d1d5db; }
        .tanda-tangan td { width: 50%; text-align: center; vertical-align: top; }
        .tanda-tangan .ruang { height: 70px; }
        .tanda-tangan .jabatan { font-weight: bold; }

        .kaki { text-align: center; font-size: 9px; color: #9ca3af; margin-top: 20px; border-top: 1px solid #d1d5db; padding-top: 8px; }
    </style>
</head>

<body>

    <!-- Kepala Surat -->
    <div class="kop">
        <div class="instansi">PEMERINTAH KOTA SURABAYA</div>
        <div class="rumah-sakit">RUMAH SAKIT UMUM DAERAH DR. SOETOMO</div>
        <div class="alamat">Jl. Mayjen Prof. Dr. Moestopo No. 6-8, Surabaya 60131</div>
        <div class="alamat">Telepon: (031) 5502000 | Website: rsudsoetomo.surabaya.go.id</div>
    </div>
    <!-- End of Kepala Surat -->

    <!-- Judul Surat -->
    <div class="judul">
        <div class="nama-surat">LEMBAR DISPOSISI PENGADUAN</div>
        <div class="nomor">Nomor: {{ $detail->kode() }}</div>
    </div>
    <!-- End of Judul Surat -->

    <!-- Identitas Pengaduan -->
    <div class="kotak">
        <div class="judul-bagian">Identitas Pengaduan</div>

        <table class="identitas">
            <tbody>
                <tr>
                    <td class="label">Kode Tiket</td>
                    <td>{{ $detail->kode() }}</td>
                </tr>
                <tr>
                    <td class="label">Tanggal Masuk</td>
                    <td>{{ $tiket->created_at->translatedFormat('d F Y, H:i') }} WIB</td>
                </tr>
                <tr>
                    <td class="label">Kategori</td>
                    <td>{{ $tiket->labelKategori() }}</td>
                </tr>
                <tr>
                    <td class="label">Nama Pelapor</td>
                    <td>{{ $tiket->nama_lengkap }}</td>
                </tr>
                <tr>
                    <td class="label">NRM</td>
                    <td>{{ $tiket->nrm }}</td>
                </tr>
                <tr>
                    <td class="label">No. WhatsApp</td>
                    <td>{{ $tiket->no_wa }}</td>
                </tr>
                <tr>
                    <td class="label">Email</td>
                    <td>{{ $tiket->email }}</td>
                </tr>
                <tr>
                    <td class="label">Unit Pelapor</td>
                    <td>{{ $tiket->unit }}</td>
                </tr>
                <tr>
                    <td class="label">Subjek</td>
                    <td>{{ $tiket->subjek }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- End of Identitas Pengaduan -->

    <!-- Instruksi Disposisi -->
    @if ($instruksi)
        <div class="kotak arahan">
            <div class="judul-bagian">Instruksi Disposisi dari Humas</div>
            <div>{!! nl2br(e($instruksi['catatan'])) !!}</div>

            @if ($instruksi['waktu'])
                <div style="margin-top: 6px;">
                    Waktu Instruksi: {{ $instruksi['waktu']->translatedFormat('d F Y, H:i') }} WIB
                </div>
            @endif
        </div>
    @endif
    <!-- End of Instruksi Disposisi -->

    <!-- Status dan SLA -->
    <table class="baris-tiga">
        <tbody>
            <tr>
                <td>
                    <div class="judul-kartu">Status Saat Ini</div>
                    <div style="margin-top: 4px;">
                        <span class="badge badge-{{ $tiket->status->value }}">{{ $tiket->status->label() }}</span>
                    </div>
                </td>
                <td>
                    <div class="judul-kartu">Tahap Investigasi</div>
                    <div style="margin-top: 4px;">
                        <span class="badge badge-diterima">{{ $detail->investigasi->label() }}</span>
                    </div>
                </td>
                <td>
                    <div class="judul-kartu">Zona SLA</div>
                    <div style="margin-top: 4px;">
                        <span class="badge zona-{{ $sla['zonaNilai'] }}">{{ $sla['zona'] }} (Hari ke-{{ $sla['hariKe'] }})</span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <!-- End of Status dan SLA -->

    <!-- Unit yang Ditugaskan -->
    <div class="kotak">
        <div class="judul-bagian">Unit yang Ditugaskan</div>

        @if ($unit['tertaut'])
            <table class="terikat">
                <tbody>
                    <tr>
                        <td>{{ $unit['nama'] }}</td>
                        <td style="text-align: right;">({{ $unit['kode'] }})</td>
                    </tr>
                </tbody>
            </table>
        @else
            <div>Belum ditugaskan ke unit manapun; penanganan diambil alih Hubungan Masyarakat.</div>
        @endif
    </div>
    <!-- End of Unit yang Ditugaskan -->

    <!-- Jejak Perubahan Tahap -->
    <div class="kotak">
        <div class="judul-bagian">Jejak Perubahan Tahap</div>

        @if (count($detail->audit) > 0)
            <table class="audit">
                <thead>
                    <tr>
                        <th style="width: 130px;">Waktu</th>
                        <th style="width: 90px;">Sumber</th>
                        <th style="width: 140px;">Tahap</th>
                        <th>Penjelasan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($detail->audit as $baris)
                        <tr>
                            <td>{{ $baris['waktu'] }}</td>
                            <td>{{ $baris['sumber'] }}</td>
                            <td>{{ $baris['judul'] }}</td>
                            <td>
                                {{ $baris['kalimat'] }}

                                @if ($baris['catatan'])
                                    <div class="catatan">Catatan: {{ $baris['catatan'] }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div>Belum ada jejak perubahan tahap.</div>
        @endif
    </div>
    <!-- End of Jejak Perubahan Tahap -->

    <!-- Tanda Tangan -->
    <table class="tanda-tangan">
        <tbody>
            <tr>
                <td>
                    <div class="ruang"></div>
                    <div class="jabatan">Mengetahui,</div>
                    <div>Kepala Unit</div>
                    <div>({{ $unit['tertaut'] ? $unit['nama'] : '________________' }})</div>
                </td>
                <td>
                    <div class="ruang"></div>
                    <div class="jabatan">Mengetahui,</div>
                    <div>Admin Humas</div>
                    <div>(________________)</div>
                </td>
            </tr>
        </tbody>
    </table>
    <!-- End of Tanda Tangan -->

    <!-- Kaki Surat -->
    <div class="kaki">
        Dicetak pada {{ now()->translatedFormat('d F Y, H:i') }} WIB<br>
        Sistem Pengaduan RSUD Dr. Soetomo — Lembar ini dicetak secara otomatis dari sistem.
    </div>
    <!-- End of Kaki Surat -->

</body>

</html>
