<?php

namespace App\Support;

use App\Enums\StatusInvestigasi;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
    private const BATAS_OVERDUE = 0;      // hari sisa ≤ 0
    private const BATAS_AT_RISK = 2;      // hari sisa 1-2 (config: peringatan_hari_kerja)
    private const MAX_PRIORITY_QUEUE = 50; // batas antrian prioritas

    /**
     * @param  array<int, array<string, mixed>>  $unitTable  Ringkasan per unit
     * @param  array<int, array<string, mixed>>  $priorityQueue  Antrian prioritas
     * @param  int  $totalDitugaskan  Total tiket ke unit aktif (termasuk selesai)
     * @param  int  $hariInvestigasi  Batas investigasi unit (hari kerja)
     */
    public function __construct(
        public readonly array $unitTable,
        public readonly array $priorityQueue,
        public readonly int $totalDitugaskan,
        public readonly int $hariInvestigasi,
    ) {}

    /**
     * Susun data monitor dari request (tanpa filter URL).
     */
    public static function dariRequest(): self
    {
        $dasar = fn (): Builder => Pengaduan::query()
            ->whereNotNull('master_unit_id')
            ->whereHas('masterUnit', fn (Builder $q): Builder => $q->where('aktif', true))
            ->with('masterUnit');

        return new self(
            unitTable: self::unitTable($dasar()),
            priorityQueue: self::priorityQueue($dasar()),
            totalDitugaskan: (clone $dasar())->count(),
            hariInvestigasi: Sla::hariInvestigasi(),
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

            // Breakdown by SLA zone (semua tiket: aktif + selesai)
            $breakdown = $queryUnit(false)->get()
                ->groupBy(function (Pengaduan $p): string {
                    if ($p->status->selesai()) {
                        return 'selesai';
                    }
                    $zona = Sla::zona($p->created_at, null, $p->kasus_berat);
                    return match ($zona) {
                        \App\Enums\ZonaSla::Terlambat => 'overdue',
                        \App\Enums\ZonaSla::Mendek => 'at_risk',
                        \App\Enums\ZonaSla::TepatWaktu => 'on_track',
                        default => 'on_track',
                    };
                });

            $overdue = $breakdown->get('overdue', collect())->count();
            $atRisk = $breakdown->get('at_risk', collect())->count();
            $onTrack = $breakdown->get('on_track', collect())->count();
            $selesai = $breakdown->get('selesai', collect())->count();

            // Rata-rata hari kerja tiket aktif
            $avgHari = $totalAktif > 0
                ? $queryUnit()->get()->avg(fn (Pengaduan $p): int => Sla::hariKe($p->created_at))
                : 0;

            // Urgency score unit (untuk sorting): overdue*10 + at_risk*3
            $urgencyScore = $overdue * 10 + $atRisk * 3;

            $rows[] = [
                'unit_id' => $unit->id,
                'kode' => $unit->kode,
                'nama' => $unit->nama,
                'pic' => $unit->pic,
                'wa' => $unit->kontak_wa,
                'total_aktif' => $totalAktif,
                'total_selesai' => $selesai,
                'overdue' => $overdue,
                'at_risk' => $atRisk,
                'on_track' => $onTrack,
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
     * Antrian prioritas flat (Option B: Priority Queue).
     *
     * Urgency = days_overdue * 10 + (kasus_berat ? 50 : 0) + status_weight
     * status_weight: Diterima=1, Diproses=3, Revisi=5, Menunggu Racikan=2
     *
     * @param  Builder  $dasar  Query dasar sudah difilter unit aktif
     * @return array<int, array<string, mixed>>
     */
    private static function priorityQueue(Builder $dasar): array
    {
        $tickets = (clone $dasar)->aktif()
            ->with('masterUnit')
            ->get()
            ->map(function (Pengaduan $p) {
                $hariKe = Sla::hariKe($p->created_at);
                $totalHari = Sla::totalHariKerja($p->kasus_berat);
                $lewat = max(0, $hariKe - $totalHari); // actual days overdue (positive)
                $sisa = max(0, $totalHari - $hariKe);  // remaining days (0 if overdue)

                // Determine priority group (1-6) per user spec:
                // 1. Telat + Berat
                // 2. Berat + Tidak Telat
                // 3. Telat + Sedang (Diproses/Revisi)
                // 4. Sedang + Tidak Telat
                // 5. Telat + Ringan (Diterima)
                // 6. Ringan + Tidak Telat
                $isBerat = $p->kasus_berat;
                $isTelat = $lewat > 0;
                $isSedang = in_array($p->status, [StatusPengaduan::Diproses, StatusPengaduan::Revisi]);
                $isRingan = $p->status === StatusPengaduan::Diterima;

                if ($isTelat && $isBerat) {
                    $priorityGroup = 1;
                } elseif (!$isTelat && $isBerat) {
                    $priorityGroup = 2;
                } elseif ($isTelat && $isSedang) {
                    $priorityGroup = 3;
                } elseif (!$isTelat && $isSedang) {
                    $priorityGroup = 4;
                } elseif ($isTelat && $isRingan) {
                    $priorityGroup = 5;
                } else {
                    $priorityGroup = 6;
                }

                // Traffic light
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
                // Sort by priority group first, then by days overdue (desc), then by hari_ke (desc)
                return [$t['priority_group'], -$t['lewat'], -$t['hari_ke']];
            })
            ->take(self::MAX_PRIORITY_QUEUE)
            ->values()
            ->all();

        return $tickets;
    }
}