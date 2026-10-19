@extends('admin.layout')

@section('title', 'Lembar Disposisi - '.$detail->kode())

@section('content')
    @php
        $tiket = $detail->pengaduan;
        $unit = $detail->unit;
        $sla = $detail->sla;
        $instruksi = $detail->instruksiDisposisi();
        $investigasi = $tiket->statusInvestigasi()->label();
    @endphp

    <div class="w-full px-margin py-space-md flex flex-col gap-space-lg max-w-4xl mx-auto">

        {{-- Header RSUD --}}
        <div class="text-center border-b-2 border-primary pb-space-md">
            <div class="text-body-lg font-bold text-primary">PEMERINTAH KOTA SURABAYA</div>
            <div class="text-body-md font-semibold text-on-surface">RUMAH SAKIT UMUM DAERAH DR. SOETOMO</div>
            <div class="text-body-sm text-on-surface-variant">Jl. Mayjen Prof. Dr. Moestopo No. 6-8, Surabaya 60131</div>
            <div class="text-body-sm text-on-surface-variant">Telepon: (031) 5502000 | Website: rsudsoetomo.surabaya.go.id</div>
        </div>

        {{-- Judul Surat --}}
        <div class="text-center my-space-lg">
            <div class="text-body-lg font-bold text-on-surface underline">LEMBAR DISPOSISI PENGADUAN</div>
            <div class="text-body-md text-on-surface-variant mt-1">Nomor: {{ $detail->kode() }}</div>
        </div>

        {{-- Identitas Pengaduan --}}
        <div class="bg-surface-container-lowest rounded-lg border border-outline-variant p-space-md">
            <div class="font-semibold text-on-surface mb-space-sm flex items-center gap-2">
                <x-symbol nama="badge" class="text-secondary" />
                Identitas Pengaduan
            </div>
            <table class="w-full text-body-sm">
                <tbody>
                    <tr>
                        <td class="w-48 text-on-surface-variant">Kode Tiket</td>
                        <td class="font-mono font-bold text-on-surface">{{ $detail->kode() }}</td>
                    </tr>
                    <tr>
                        <td class="text-on-surface-variant">Tanggal Masuk</td>
                        <td>{{ $tiket->created_at->translatedFormat('d F Y, H:i') }} WIB</td>
                    </tr>
                    <tr>
                        <td class="text-on-surface-variant">Kategori</td>
                        <td><span class="px-2 py-0.5 rounded-full bg-brand-100 text-brand-800 text-xs">{{ $tiket->kategori->label() }}</span></td>
                    </tr>
                    <tr>
                        <td class="text-on-surface-variant">Nama Pelapor</td>
                        <td>{{ $tiket->nama_lengkap }}</td>
                    </tr>
                    <tr>
                        <td class="text-on-surface-variant">NRM</td>
                        <td>{{ $tiket->nrm }}</td>
                    </tr>
                    <tr>
                        <td class="text-on-surface-variant">No. WhatsApp</td>
                        <td>{{ $tiket->no_wa }}</td>
                    </tr>
                    <tr>
                        <td class="text-on-surface-variant">Email</td>
                        <td>{{ $tiket->email }}</td>
                    </tr>
                    <tr>
                        <td class="text-on-surface-variant">Unit Pelapor</td>
                        <td>{{ $tiket->unit }}</td>
                    </tr>
                    <tr>
                        <td class="text-on-surface-variant">Subjek</td>
                        <td>{{ $tiket->subjek }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Instruksi Disposisi --}}
        @if ($instruksi)
        <div class="bg-secondary-container rounded-lg border border-secondary p-space-md">
            <div class="font-semibold text-on-secondary-container mb-space-sm flex items-center gap-2">
                <x-symbol nama="assignment" class="text-secondary" />
                Instruksi Disposisi dari Humas
            </div>
            <div class="prose prose-sm max-w-none text-on-secondary-container">
                {!! nl2br(e($instruksi['catatan'])) !!}
            </div>
            @if ($instruksi['waktu'])
                <div class="mt-2 text-body-sm text-on-secondary-container/80">
                    <strong>Waktu Instruksi:</strong> {{ $instruksi['waktu']->translatedFormat('d F Y, H:i') }} WIB
                </div>
            @endif
        </div>
        @endif

        {{-- Status & SLA --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-space-md">
            <div class="bg-surface-container-lowest rounded-lg border border-outline-variant p-space-md text-center">
                <div class="text-label-sm text-on-surface-variant">Status Saat Ini</div>
                <div class="mt-1">
                    <span class="px-3 py-1 rounded-full {{ $tiket->status->badgeAdmin() }} font-label-md text-label-md font-bold">
                        {{ $tiket->status->label() }}
                    </span>
                </div>
            </div>
            <div class="bg-surface-container-lowest rounded-lg border border-outline-variant p-space-md text-center">
                <div class="text-label-sm text-on-surface-variant">Tahap Investigasi</div>
                <div class="mt-1">
                    <span class="px-3 py-1 rounded-full bg-brand-100 text-brand-800 font-label-md text-label-md font-bold">
                        {{ $investigasi }}
                    </span>
                </div>
            </div>
            <div class="bg-surface-container-lowest rounded-lg border border-outline-variant p-space-md text-center">
                <div class="text-label-sm text-on-surface-variant">Zona SLA</div>
                <div class="mt-1">
                    <span class="px-3 py-1 rounded-full {{ $sla['nadaZona'] }} font-label-md text-label-md font-bold flex items-center justify-center gap-1 mx-auto">
                        <x-symbol nama="schedule" class="text-[13px]" />
                        {{ $sla['zona'] }} (Hari ke-{{ $sla['hariKe'] }})
                    </span>
                </div>
            </div>
        </div>

        {{-- Unit yang Ditugaskan --}}
        <div class="bg-surface-container-lowest rounded-lg border border-outline-variant p-space-md">
            <div class="font-semibold text-on-surface mb-space-sm flex items-center gap-2">
                <x-symbol nama="support_agent" class="text-secondary" />
                Unit yang Ditugaskan
            </div>
            @if ($unit['tertaut'])
                <div class="flex items-center gap-space-md">
                    <span class="px-space-md py-2 rounded-lg {{ $unit['nada'] }} font-label-lg text-label-lg font-bold flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-secondary" aria-hidden="true"></span>
                        {{ $unit['nama'] }}
                    </span>
                    <span class="text-body-sm text-on-surface-variant">({{ $unit['kode'] }})</span>
                </div>
            @else
                <div class="text-on-surface-variant">
                    <x-symbol nama="support_agent" class="inline text-[16px] align-middle mr-1" />
                    Belum ditugaskan ke unit manapun
                </div>
            @endif
        </div>

        {{-- Jejak Audit --}}
        <div class="bg-surface-container-lowest rounded-lg border border-outline-variant p-space-md">
            <div class="font-semibold text-on-surface mb-space-sm flex items-center gap-2">
                <x-symbol nama="history" class="text-secondary" />
                Jejak Perubahan Tahap
            </div>
            @if (count($detail->audit) > 0)
                <div class="space-y-2">
                    @foreach ($detail->audit as $baris)
                        <div class="flex flex-col md:flex-row md:items-center gap-2 p-2 bg-surface-container rounded-lg">
                            <div class="w-32 font-mono text-body-sm text-on-surface-variant">{{ $baris['waktu'] }}</div>
                            <div class="flex items-center gap-2 flex-1">
                                <span class="px-2 py-0.5 rounded-full {{ $baris['nada'] }} text-xs">{{ $baris['sumber'] }}</span>
                                <x-symbol :nama="$baris['ikon']" class="text-on-surface-variant text-[14px]" />
                                <span class="px-2 py-0.5 rounded-full bg-brand-100 text-brand-800 font-label-sm text-label-sm font-bold">
                                    {{ $baris['judul'] }}
                                </span>
                            </div>
                            @if ($baris['catatan'])
                                <div class="ml-8 md:ml-24 text-body-sm text-on-surface-variant italic">{{ $baris['catatan'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-body-sm text-on-surface-variant">Belum ada jejak perubahan tahap.</p>
            @endif
        </div>

        {{-- Tanda Tangan --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-xl mt-space-xl pt-space-lg border-t border-outline-variant">
            <div class="text-center">
                <div class="h-24"></div>
                <div class="font-semibold text-on-surface">Mengetahui,<br>Kepala Unit</div>
                <div class="text-body-sm text-on-surface-variant mt-2">({{ $unit['tertaut'] ? $unit['nama'] : '________________' }})</div>
            </div>
            <div class="text-center">
                <div class="h-24"></div>
                <div class="font-semibold text-on-surface">Mengetahui,<br>Admin Humas</div>
                <div class="text-body-sm text-on-surface-variant mt-2">(________________)</div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="text-center text-body-xs text-on-surface-variant mt-space-lg border-t border-outline-variant pt-space-md">
            Dicetak pada {{ now()->translatedFormat('d F Y, H:i') }} WIB<br>
            Sistem Pengaduan RSUD Dr. Soetomo — Lembar ini dicetak secara otomatis dari sistem.
        </div>
    </div>
@endsection