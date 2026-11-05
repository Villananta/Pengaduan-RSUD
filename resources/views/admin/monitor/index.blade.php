@extends('admin.layout')

@section('title', 'Monitor Disposisi & SLA')

@section('content')

    <div class="w-full px-margin py-space-lg flex flex-col gap-space-lg">

        {{-- Hasil tindakan --}}
        @if (session('sukses'))
            <div
                role="status"
                class="w-full bg-secondary-container text-on-secondary-container rounded-lg px-space-md py-space-sm flex items-start gap-space-sm"
            >
                <x-symbol nama="check_circle" class="text-[20px] shrink-0" />
                <span class="text-body-sm">{{ session('suksu') }}</span>
            </div>
        @endif

        {{-- Strip Konteks --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md bg-surface-container-lowest p-space-lg rounded-lg shadow-sm">
            <div class="flex items-start gap-space-md">
                <span class="w-12 h-12 rounded-lg bg-primary-container text-on-primary flex items-center justify-center shadow-sm shrink-0">
                    <x-symbol nama="timelapse" class="text-[26px]" />
                </span>

                <div class="flex flex-col">
                    <div class="flex items-center gap-space-sm flex-wrap">
                        <span class="font-label-sm text-label-sm uppercase tracking-wider font-bold bg-secondary-container/60 text-on-secondary-container px-space-xs py-0.5 rounded-xl">
                            Sistem Pengawasan Internal Lapis 2
                        </span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-secondary animate-ping" aria-hidden="true"></span>
                            Pelacakan Langsung SIMRS & Humas
                        </span>
                    </div>

                    <h1 class="font-headline-md text-headline-md text-on-surface font-bold tracking-tight mt-0.5">
                        Monitor Disposisi & SLA Unit
                    </h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        Memastikan unit merespon telaah medis maksimal {{ $monitor->hariInvestigasi }} hari kerja sesuai Permenkes No. 4/2018.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-space-sm flex-wrap self-end lg:self-center">
                <a
                    href="{{ route('admin.monitor.index') }}"
                    class="px-space-md py-2 rounded-lg bg-secondary text-on-secondary font-label-lg text-label-lg hover:bg-secondary/90 transition-colors flex items-center gap-1.5 shadow-sm"
                    title="Muat ulang angka dan daftar disposisi unit"
                >
                    <x-symbol nama="refresh" class="text-[18px]" />
                    Muat Ulang Data
                </a>
            </div>
        </div>
        <!-- End of Strip Konteks -->

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-md">
            <div class="bg-surface-container-lowest rounded-lg p-space-md shadow-sm border-l-4 border-error">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-label-sm text-label-sm text-on-surface-variant">Tiket Overdue</p>
                        <p class="font-headline-md text-headline-md font-bold text-error mt-1">
                            {{ array_sum(array_column($monitor->unitTable, 'overdue')) }}
                        </p>
                    </div>
                    <x-symbol nama="crisis_alert" class="text-[32px] text-error/60" />
                </div>
            </div>

            <div class="bg-surface-container-lowest rounded-lg p-space-md shadow-sm border-l-4 border-warning">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-label-sm text-label-sm text-on-surface-variant">Mendekati Batas</p>
                        <p class="font-headline-md text-headline-md font-bold text-warning mt-1">
                            {{ array_sum(array_column($monitor->unitTable, 'at_risk')) }}
                        </p>
                    </div>
                    <x-symbol nama="warning" class="text-[32px] text-warning/60" />
                </div>
            </div>

            <div class="bg-surface-container-lowest rounded-lg p-space-md shadow-sm border-l-4 border-success">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-label-sm text-label-sm text-on-surface-variant">Sesuai Jadwal</p>
                        <p class="font-headline-md text-headline-md font-bold text-success mt-1">
                            {{ array_sum(array_column($monitor->unitTable, 'on_track')) }}
                        </p>
                    </div>
                    <x-symbol nama="check_circle" class="text-[32px] text-success/60" />
                </div>
            </div>

            <div class="bg-surface-container-lowest rounded-lg p-space-md shadow-sm border-l-4 border-primary">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-label-sm text-label-sm text-on-surface-variant">Total Ditugaskan</p>
                        <p class="font-headline-md text-headline-md font-bold text-primary mt-1">
                            {{ number_format($monitor->totalDitugaskan, 0, ',', '.') }}
                        </p>
                    </div>
                    <x-symbol nama="assignment" class="text-[32px] text-primary/60" />
                </div>
            </div>
        </div>

        {{-- Tab Navigasi --}}
        <div class="bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden">
            <div class="border-b border-outline-variant">
                <nav class="flex gap-space-xs px-space-sm" role="tablist" aria-label="Tampilan monitor">
                    <button
                        role="tab"
                        id="tab-unit-table"
                        data-tab-target="panel-unit-table"
                        class="tab-btn px-space-md py-2.5 rounded-t-lg font-label-md text-label-md font-semibold transition-colors bg-primary-container text-on-primary"
                        aria-selected="true"
                        aria-controls="panel-unit-table"
                    >
                        <x-symbol nama="table_view" class="text-[18px] inline-block align-middle me-1" />
                        Tabel Unit
                    </button>
                    <button
                        role="tab"
                        id="tab-priority-queue"
                        data-tab-target="panel-priority-queue"
                        class="tab-btn px-space-md py-2.5 rounded-t-lg font-label-md text-label-md font-semibold transition-colors text-on-surface-variant hover:text-on-surface hover:bg-surface-container"
                        aria-selected="false"
                        aria-controls="panel-priority-queue"
                    >
                        <x-symbol nama="priority_high" class="text-[18px] inline-block align-middle me-1" />
                        Antrian Prioritas
                    </button>
                </nav>
            </div>

            {{-- Panel: Tabel Unit --}}
            <div role="tabpanel" id="panel-unit-table" aria-labelledby="tab-unit-table" class="p-space-md">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" role="grid">
                        <thead>
                            <tr class="bg-surface-container text-on-surface-variant font-semibold">
                                <th class="px-3 py-2 text-left">Unit</th>
                                <th class="px-3 py-2 text-center">Total Aktif</th>
                                <th class="px-3 py-2 text-center">🔴 Overdue</th>
                                <th class="px-3 py-2 text-center">🟡 At Risk</th>
                                <th class="px-3 py-2 text-center">🟢 On Track</th>
                                <th class="px-3 py-2 text-center">Rata-rata Hari</th>
                                <th class="px-3 py-2 text-center">PIC / WA</th>
                                <th class="px-3 py-2 text-center">Status</th>
                                <th class="px-3 py-2 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            @forelse ($monitor->unitTable as $u)
                                <tr class="hover:bg-surface-container transition-colors {{ $u['overdue'] > 0 ? 'bg-error-container/20' : ($u['at_risk'] > 0 ? 'bg-warning-container/20' : '') }}">
                                    <td class="px-3 py-2 font-medium text-on-surface">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full {{ $u['overdue'] > 0 ? 'bg-error' : ($u['at_risk'] > 0 ? 'bg-warning' : 'bg-success') }}" aria-hidden="true"></span>
                                            <span>{{ $u['kode'] }}: {{ $u['nama'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-center font-body-md text-body-md text-on-surface">{{ $u['total_aktif'] }}</td>
                                    <td class="px-3 py-2 text-center font-bold text-error">{{ $u['overdue'] }}</td>
                                    <td class="px-3 py-2 text-center font-bold text-warning">{{ $u['at_risk'] }}</td>
                                    <td class="px-3 py-2 text-center font-bold text-success">{{ $u['on_track'] }}</td>
                                    <td class="px-3 py-2 text-center font-mono text-on-surface-variant">{{ $u['avg_hari'] }} hari</td>
                                    <td class="px-3 py-2 text-center text-on-surface-variant">
                                        @if ($u['pic'])
                                            <div class="font-body-sm text-body-sm">{{ $u['pic'] }}</div>
                                            <div class="font-label-sm text-label-sm flex items-center justify-center gap-1">
                                                <x-symbol nama="phone" class="text-[14px]" />
                                                {{ $u['wa'] ?? '—' }}
                                            </div>
                                        @else
                                            <span class="text-outline">Belum diisi</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full {{ $u['koneksi_aktif'] ? 'bg-success' : 'bg-error' }}" title="{{ $u['koneksi_aktif'] ? 'SIMRS Terhubung' : 'SIMRS Terputus' }}"></span>
                                            <span class="w-2.5 h-2.5 rounded-full {{ $u['bisa_ditugaskan'] ? 'bg-success' : 'bg-warning' }}" title="{{ $u['bisa_ditugaskan'] ? 'Siap Ditugaskan' : 'Belum Siap (PIC/WA kosong)' }}"></span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <a
                                            href="{{ $u['url'] }}"
                                            class="px-3 py-1.5 rounded-sm bg-primary-container text-on-primary font-label-sm text-label-sm hover:bg-primary transition-colors flex items-center justify-center gap-1"
                                        >
                                            <x-symbol nama="visibility" class="text-[16px]" />
                                            Lihat
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-3 py-8 text-center text-on-surface-variant">
                                        Tidak ada unit dengan tiket aktif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Ringkasan bawah tabel --}}
                <div class="mt-4 pt-4 border-t border-outline-variant flex flex-wrap items-center justify-between gap-2 text-sm text-on-surface-variant">
                    <span>Total unit aktif: {{ count($monitor->unitTable) }}</span>
                    <span>Total tiket aktif: {{ array_sum(array_column($monitor->unitTable, 'total_aktif')) }}</span>
                    <span>Total selesai: {{ array_sum(array_column($monitor->unitTable, 'total_selesai')) }}</span>
                </div>
            </div>

            {{-- Panel: Antrian Prioritas --}}
            <div role="tabpanel" id="panel-priority-queue" aria-labelledby="tab-priority-queue" class="p-space-md hidden">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                        Menampilkan {{ count($monitor->priorityQueue) }} tiket prioritas (maks. 50). Urutan: 1) Telat & Berat → 2) Berat & Tidak Telat → 3) Telat & Sedang → 4) Sedang & Tidak Telat → 5) Telat & Ringan → 6) Ringan & Tidak Telat.
                    </p>
                    <span class="font-label-sm text-label-sm px-2 py-1 rounded-full bg-surface-container text-on-surface-variant">
                        Auto-refresh: 60s
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm" role="grid">
                        <thead>
                            <tr class="bg-surface-container text-on-surface-variant font-semibold">
                                <th class="px-3 py-2 text-left">Prioritas</th>
                                <th class="px-3 py-2 text-left">Tiket</th>
                                <th class="px-3 py-2 text-left">Unit</th>
                                <th class="px-3 py-2 text-left">Pelapor</th>
                                <th class="px-3 py-2 text-left">Status / Investigasi</th>
                                <th class="px-3 py-2 text-center">Hari ke-</th>
                                <th class="px-3 py-2 text-center">Sisa</th>
                                <th class="px-3 py-2 text-center">SLA</th>
                                <th class="px-3 py-2 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            @forelse ($monitor->priorityQueue as $i => $t)
                                @php
                                    $priorityLabels = [
                                        1 => '🔴 Telat + Berat',
                                        2 => '🟠 Berat + Tidak Telat',
                                        3 => '🟡 Telat + Sedang',
                                        4 => '🟢 Sedang + Tidak Telat',
                                        5 => '🔵 Telat + Ringan',
                                        6 => '⚪ Ringan + Tidak Telat',
                                    ];
                                    $priorityColors = [
                                        1 => 'bg-error-container text-on-error-container',
                                        2 => 'bg-warning-container text-on-warning-container',
                                        3 => 'bg-warning-container text-on-warning-container',
                                        4 => 'bg-success-container text-on-success-container',
                                        5 => 'bg-info-container text-on-info-container',
                                        6 => 'bg-surface-container text-on-surface-variant',
                                    ];
                                @endphp
                                <tr class="hover:bg-surface-container transition-colors {{ $t['lewat'] > 0 ? 'bg-error-container/15' : ($t['sisa_hari'] <= 2 && $t['sisa_hari'] > 0 ? 'bg-warning-container/15' : '') }}">
                                    <td class="px-3 py-2 font-mono text-on-surface">
                                        #{{ $i + 1 }}
                                        <span class="ml-1.5 inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold {{ $priorityColors[$t['priority_group']] }}">
                                            {{ $priorityLabels[$t['priority_group']] }}
                                        </span>
                                        @if ($t['kasus_berat'])
                                            <span class="ml-1 inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-primary-fixed text-primary-container">
                                                <x-symbol nama="shield" class="text-[10px]" />
                                                BERAT
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        <a href="{{ $t['url_detail'] }}" class="font-medium text-primary hover:underline">{{ $t['kode'] }}</a>
                                        <div class="font-label-sm text-label-sm text-on-surface-variant line-clamp-1 mt-0.5">{{ $t['subjek'] }}</div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <a href="{{ $t['url_unit'] }}" class="font-medium text-on-surface hover:underline">{{ $t['unit'] }}</a>
                                    </td>
                                    <td class="px-3 py-2 text-on-surface-variant">{{ $t['pelapor'] }}</td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium bg-surface-container-high text-on-surface-variant">{{ $t['status'] }}</span>
                                        <div class="font-label-sm text-label-sm text-outline mt-0.5">{{ $t['status_investigasi'] }}</div>
                                    </td>
                                    <td class="px-3 py-2 text-center font-mono font-semibold {{ $t['lewat'] > 0 ? 'text-error' : 'text-on-surface' }}">
                                        {{ $t['hari_ke'] }}
                                    </td>
                                    <td class="px-3 py-2 text-center font-mono font-semibold {{ $t['sisa_hari'] <= 0 ? 'text-error' : ($t['sisa_hari'] <= 2 ? 'text-warning' : 'text-success') }}">
                                        @if ($t['lewat'] > 0)
                                            LEWAT {{ $t['lewat'] }} HR
                                        @else
                                            {{ $t['sisa_hari'] }} HR
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-[11px] font-medium {{ $t['sla_badge']['class'] }}">
                                            {{ $t['sla_badge']['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <a
                                            href="{{ $t['url_detail'] }}"
                                            class="px-2 py-1 rounded text-label-sm font-label-sm font-bold transition-colors flex items-center justify-center gap-1 mx-auto bg-primary-container text-on-primary hover:bg-primary"
                                            title="Buka Detail"
                                        >
                                            <x-symbol nama="visibility" class="text-[14px]" />
                                            Buka
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-3 py-8 text-center text-on-surface-variant">
                                        Tidak ada tiket aktif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script>
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = this.dataset.tabTarget;
                var panel = document.getElementById(targetId);
                if (!panel) return;

                // Hide all panels
                document.querySelectorAll('[role="tabpanel"]').forEach(function (p) {
                    p.classList.add('hidden');
                });
                // Deactivate all tabs
                document.querySelectorAll('.tab-btn').forEach(function (b) {
                    b.classList.remove('bg-primary-container', 'text-on-primary');
                    b.classList.add('text-on-surface-variant', 'hover:text-on-surface', 'hover:bg-surface-container');
                    b.setAttribute('aria-selected', 'false');
                });

                // Show target
                panel.classList.remove('hidden');
                this.classList.add('bg-primary-container', 'text-on-primary');
                this.classList.remove('text-on-surface-variant', 'hover:text-on-surface', 'hover:bg-surface-container');
                this.setAttribute('aria-selected', 'true');
            });
        });

        // Auto-refresh priority queue every 60s
        var refreshTimer = setInterval(function () {
            var activeTab = document.querySelector('.tab-btn[aria-selected="true"]');
            if (activeTab && activeTab.id === 'tab-priority-queue') {
                // Only refresh if priority queue tab is active
                // For now just reload page; could be enhanced with fetch
                window.location.reload();
            }
        }, 60000);

        // Cleanup on unload
        window.addEventListener('beforeunload', function () {
            clearInterval(refreshTimer);
        });
    </script>
@endpush