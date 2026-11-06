<?php

namespace App\Support;

use App\Enums\StatusInvestigasi;
use App\Enums\StatusPengaduan;
use App\Enums\ZonaSla;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Database\Eloquent\Builder;

/**
 * Data tampilan untuk halaman Monitor Disposisi & SLA Unit.
 *
 * Rebuilt v2: Unit-centric table + Priority queue tab.
 * - Unit Table: shows systemic load per unit with traffic lights
 * - Priority Queue: single actionable list sorted by urgency
 *
 * All queries restricted to active units with tickets. Excludes
 * Humas-direct tickets (no master_unit_id).
 */
final class MonitorDisposisi
{
    private const BATAS_OVERDUE = 0;       // hari sisa ≤ 0 (SLA penuh: 12/20 hari kerja)

    private const BATAS_AT_RISK = 2;       // hari sisa 1-2 (config: peringatan_hari_kerja)

    private const BATAS_INVESTIGASI = 5;   // batas investigasi unit (hari kerja) — dipakai StatistikDashboard

    /**
     * @param  array<int, array<string, mixed>>  $unitTable  Ringkasan per unit
     * @param  array<int, array<string, mixed>>  $priorityQueue  Antrian prioritas (halaman saat ini)
     * @param  int  $totalDitugaskan  Total tiket ke unit aktif (termasuk selesai)
     * @param  int  $hariInvestigasi  Batas investigasi unit (hari kerja)
     * @param  int  $priorityTotal  Total tiket di antrian prioritas (all pages)
     * @param  int  $priorityPerPage  Jumlah per halaman
     * @param  int  $priorityCurrentPage  Halaman saat ini
     * @param  int  $selesaiTepatTotal  Total tiket selesai tepat waktu (SLA penuh)
     * @param  int  $selesaiTerlambatTotal  Total tiket selesai terlambat (SLA penuh)
     * @param  int  $investigasiLewatTotal  Total tiket aktif lewat batas investigasi (5 hari kerja)
     * @param  string  $prioritasStatusFilter  Filter status antrian prioritas
     */
    public function __construct(
        public readonly array $unitTable,
        public readonly array $priorityQueue,
        public readonly int $totalDitugaskan,
        public readonly int $hariInvestigasi,
        public readonly int $priorityTotal,
        public readonly int $priorityPerPage,
        public readonly int $priorityCurrentPage,
        public readonly int $selesaiTepatTotal,
        public readonly int $selesaiTerlambatTotal,
        public readonly int $investigasiLewatTotal,
        public readonly string $prioritasStatusFilter,
    ) {}

    /**
     * Susun data monitor dari request dengan pagination antrian prioritas.
     */
    public static function dariRequest(?int $page = null, int $perPage = 50, ?string $statusFilter = null): self
    {
        $page = $page ?? (request()->query('page') ?? 1);
        $perPage = in_array($perPage, [50, 100, 150, 200]) ? $perPage : 50;
        $statusFilter = $statusFilter ?? request()->query('status');

        // Validasi status filter: hanya terima status aktif yang valid
        $allowedStatuses = ['semua_aktif', StatusPengaduan::Diterima->value, StatusPengaduan::Diproses->value, StatusPengaduan::Revisi->value];
        $statusFilter = in_array($statusFilter, $allowedStatuses) ? $statusFilter : 'semua_aktif';

        $dasar = fn (): Builder => Pengaduan::query()
            ->whereNotNull('master_unit_id')
            ->whereHas('masterUnit', fn (Builder $q): Builder => $q->where('aktif', true))
            ->with('masterUnit');

        $priorityData = self::priorityQueue($dasar(), $page, $perPage, $statusFilter);

        // Agregat SLA tiket selesai & investigasi lewat (semua unit)
        $selesaiTepatTotal = 0;
        $selesaiTerlambatTotal = 0;
        $investigasiLewatTotal = 0;

        foreach (self::unitTable($dasar()) as $row) {
            $selesaiTepatTotal += $row['selesai_tepat'];
            $selesaiTerlambatTotal += $row['selesai_terlambat'];
            $investigasiLewatTotal += $row['investigasi_lewat'];
        }

        return new self(
            unitTable: self::unitTable($dasar()),
            priorityQueue: $priorityData['data'],
            totalDitugaskan: (clone $dasar())->count(),
            hariInvestigasi: Sla::hariInvestigasi(),
            priorityTotal: $priorityData['total'],
            priorityPerPage: $perPage,
            priorityCurrentPage: $page,
            selesaiTepatTotal: $selesaiTepatTotal,
            selesaiTerlambatTotal: $selesaiTerlambatTotal,
            investigasiLewatTotal: $investigasiLewatTotal,
            prioritasStatusFilter: $statusFilter,
        );
    }

    /**
     * Ringkasan per unit (Option A: Unit Table).
     *
     * @param  Builder  $dasar  Query dasar sudah difilter unit aktif
     * @return array<int, array<string, mixed>>
     */
    private static function unitTable(Builder $dasar): array
    {
        // Ambil unit yang punya tiket aktif
        $unitIds = (clone $dasar)->aktif()->pluck('master_unit_id')->unique();

        $units = MasterUnit::query()
            ->whereIn('id', $unitIds)
            ->where('aktif', true)
            ->get()
            ->keyBy('id');

        $rows = [];

        foreach ($unitIds as $unitId) {
            $unit = $units[$unitId];

            $queryUnit = fn (bool $hanyaAktif = true): Builder => (clone $dasar)
                ->where('master_unit_id', $unitId)
                ->when($hanyaAktif, fn (Builder $q): Builder => $q->aktif());

            // Total tiket aktif di unit ini
            $totalAktif = $queryUnit()->count();

            // Total tiket selesai di unit ini
            $totalSelesai = (clone $dasar)
                ->where('master_unit_id', $unitId)
                ->where('status', 'selesai')
                ->count();

            // Breakdown SLA penuh (12/20 hari) — untuk tiket AKTIF
            $breakdownAktif = $queryUnit(true)->get()
                ->groupBy(function (Pengaduan $p): string {
                    $zona = Sla::zona($p->created_at, null, $p->kasus_berat);

                    return match ($zona) {
                        ZonaSla::Terlambat => 'overdue',
                        ZonaSla::Mendek => 'at_risk',
                        ZonaSla::TepatWaktu => 'on_track',
                        default => 'on_track',
                    };
                });

            $overdueAktif = $breakdownAktif->get('overdue', collect())->count();
            $atRiskAktif = $breakdownAktif->get('at_risk', collect())->count();
            $onTrackAktif = $breakdownAktif->get('on_track', collect())->count();

            // Breakdown SLA penuh untuk tiket SELESAI (pakai selesai_at)
            $selesaiTiket = $queryUnit(false)->where('status', 'selesai')->get();
            $selesaiTepat = 0;
            $selesaiTerlambat = 0;

            foreach ($selesaiTiket as $p) {
                $zona = Sla::zona($p->created_at, $p->selesai_at, $p->kasus_berat);
                if ($zona === ZonaSla::TepatWaktu) {
                    $selesaiTepat++;
                } else {
                    $selesaiTerlambat++;
                }
            }

            // Investigasi unit: tiket yang lewat batas 5 hari kerja
            $investigasiLewat = $queryUnit(true)
                ->get()
                ->filter(fn (Pengaduan $p) => Sla::lewatInvestigasi($p->created_at))
                ->count();

            // Rata-rata hari kerja tiket aktif
            $avgHari = $totalAktif > 0
                ? $queryUnit()->get()->avg(fn (Pengaduan $p): int => Sla::hariKe($p->created_at))
                : 0;

            // Urgency score unit (untuk sorting): overdue*10 + at_risk*3
            $urgencyScore = $overdueAktif * 10 + $atRiskAktif * 3;

            $rows[] = [
                'unit_id' => $unit->id,
                'kode' => $unit->kode,
                'nama' => $unit->nama,
                'pic' => $unit->pic,
                'wa' => $unit->kontak_wa,
                'total_aktif' => $totalAktif,
                'total_selesai' => $totalSelesai,
                'selesai_tepat' => $selesaiTepat,
                'selesai_terlambat' => $selesaiTerlambat,
                'overdue' => $overdueAktif,
                'at_risk' => $atRiskAktif,
                'on_track' => $onTrackAktif,
                'investigasi_lewat' => $investigasiLewat,
                'avg_hari' => round($avgHari, 1),
                'urgency_score' => $urgencyScore,
                'bisa_ditugaskan' => $unit->bisaDitugaskan(),
                'koneksi_aktif' => $unit->koneksiAktif(),
                'url' => route('admin.pengaduan.index', ['unit' => $unit->kode]),
            ];
        }

        // Sort: highest urgency first, then by overdue count
        usort($rows, fn (array $a, array $b): int => $b['urgency_score'] <=> $a['urgency_score'] ?: $b['overdue'] <=> $a['overdue']);

        return $rows;
    }

    /**
     * Antrian prioritas flat dengan pagination (Option B: Priority Queue).
     *
     * Prioritas: 1) Telat+Berat 2) Berat+TidakTelat 3) Telat+Sedang
     * 4) Sedang+TidakTelat 5) Telat+Ringan 6) Ringan+TidakTelat
     *
     * @param  Builder  $dasar  Query dasar sudah difilter unit aktif
     * @param  int  $page  Halaman (1-based)
     * @param  int  $perPage  Jumlah per halaman (kelipatan 50)
     * @param  string  $statusFilter  Filter status: 'semua_aktif', 'diterima', 'diproses', 'revisi'
     * @return array{data: array<int, array<string, mixed>>, total: int}
     */
    private static function priorityQueue(Builder $dasar, int $page, int $perPage, string $statusFilter): array
    {
        $query = (clone $dasar)->with('masterUnit');

        // Apply status filter
        match ($statusFilter) {
            StatusPengaduan::Diterima->value => $query->where('status', StatusPengaduan::Diterima->value),
            StatusPengaduan::Diproses->value => $query->where('status', StatusPengaduan::Diproses->value),
            StatusPengaduan::Revisi->value => $query->where('status', StatusPengaduan::Revisi->value),
            default => $query->aktif(), // 'semua_aktif' - semua status aktif (diterima, diproses, revisi)
        };

        // Get total count before pagination
        $total = $query->count();

        // Get paginated results
        $tickets = $query
            ->get()
            ->map(function (Pengaduan $p) {
                $hariKe = Sla::hariKe($p->created_at);
                $totalHari = Sla::totalHariKerja($p->kasus_berat);
                $lewat = max(0, $hariKe - $totalHari);
                $sisa = max(0, $totalHari - $hariKe);

                $isBerat = $p->kasus_berat;
                $isTelat = $lewat > 0;
                $isSedang = in_array($p->status, [StatusPengaduan::Diproses, StatusPengaduan::Revisi]);
                $isRingan = $p->status === StatusPengaduan::Diterima;

                if ($isTelat && $isBerat) {
                    $priorityGroup = 1;
                } elseif (! $isTelat && $isBerat) {
                    $priorityGroup = 2;
                } elseif ($isTelat && $isSedang) {
                    $priorityGroup = 3;
                } elseif (! $isTelat && $isSedang) {
                    $priorityGroup = 4;
                } elseif ($isTelat && $isRingan) {
                    $priorityGroup = 5;
                } else {
                    $priorityGroup = 6;
                }

                $zona = Sla::zona($p->created_at, null, $p->kasus_berat);
                $slaBadge = match ($zona->value) {
                    'terlambat' => ['label' => '🔴 TERLAMBAT', 'class' => 'bg-error-container text-on-error-container'],
                    'mendek' => ['label' => '🟡 MENDEKATI BATAS', 'class' => 'bg-warning-container text-on-warning-container'],
                    'tepat_waktu' => ['label' => '🟢 SESUAI JADWAL', 'class' => 'bg-success-container text-on-success-container'],
                    default => ['label' => '—', 'class' => 'bg-surface-container text-on-surface-variant'],
                };

                return [
                    'kode' => $p->kode_tiket,
                    'unit' => $p->masterUnit?->namaLengkap() ?? $p->unit,
                    'unit_kode' => $p->masterUnit?->kode,
                    'pelapor' => $p->nama_lengkap,
                    'subjek' => $p->subjek,
                    'status' => $p->status->label(),
                    'status_investigasi' => StatusInvestigasi::dariPengaduan($p, $p->unit_menjawab ?? false)->label(),
                    'hari_ke' => $hariKe,
                    'sisa_hari' => $sisa,
                    'lewat' => $lewat,
                    'kasus_berat' => $p->kasus_berat,
                    'priority_group' => $priorityGroup,
                    'sla_badge' => $slaBadge,
                    'created_at' => $p->created_at->translatedFormat('d M Y'),
                    'url_detail' => route('admin.pengaduan.show', $p->kode_tiket),
                    'url_unit' => $p->masterUnit ? route('admin.pengaduan.index', ['unit' => $p->masterUnit->kode]) : null,
                ];
            })
            ->sortBy(function ($t) {
                return [$t['priority_group'], -$t['lewat'], -$t['hari_ke']];
            })
            ->values();

        // Manual pagination since we sorted in PHP
        $offset = ($page - 1) * $perPage;
        $data = $tickets->slice($offset, $perPage)->values()->all();

        return [
            'data' => $data,
            'total' => $total,
        ];
    }
}
