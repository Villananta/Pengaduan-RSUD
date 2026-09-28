<?php

namespace App\Support;

use App\Enums\StatusPengaduan;
use App\Enums\ZonaSla;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Angka dan daftar untuk konsol admin Situation Room.
 *
 * Semua angka berasal dari query nyata terhadap pengaduan dan
 * master_units, tidak ada angka rekaan yang diketik di view.
 */
final class StatistikDashboard
{
    public const KUNCI = 'statistik-dashboard';

    private const TTL = 60;

    /**
     * Hitungan pengaduan per tahap untuk kartu bento.
     *
     * @return array<string, int>
     */
    public static function jumlahPerTahap(): array
    {
        $data = self::data();

        $hasil = array_fill_keys(array_map(
            fn (StatusPengaduan $s): string => $s->value,
            StatusPengaduan::cases(),
        ), 0);

        foreach ($data['per_tahap'] as $tahap => $jumlah) {
            $hasil[$tahap] = (int) $jumlah;
        }

        return $hasil;
    }

    /** Persentase kepatuhan SLA, atau null bila belum ada pengaduan selesai. */
    public static function kepatuhanSlaPersen(): ?float
    {
        $data = self::data();

        return $data['terhitung'] > 0
            ? round($data['tepat_waktu'] / $data['terhitung'] * 100, 1)
            : null;
    }

    /** Rata-rata waktu penyelesaian dalam hari kalender. */
    public static function rataRataHari(): ?float
    {
        return self::data()['rata_rata_hari'];
    }

    /** Jumlah pengaduan yang masih berjalan. */
    public static function aktif(): int
    {
        return self::data()['aktif'];
    }

    /**
     * Unit dengan pengaduan aktif terbanyak, untuk visualisasi beban.
     *
     * Beban dihitung dari pengaduan yang belum selesai agar kartu ini
     * mencerminkan antrean kerja nyata, bukan akumulasi historis.
     *
     * @return Collection<int, MasterUnit>
     */
    public static function unitTerbebani(int $jumlah = 4)
    {
        return MasterUnit::query()
            ->withCount(['pengaduan as beban_aktif' => fn ($q) => $q->aktif()])
            ->get()
            ->filter(fn (MasterUnit $unit): bool => $unit->beban_aktif > 0)
            ->sortBy([
                fn (MasterUnit $a, MasterUnit $b): int => $b->beban_aktif <=> $a->beban_aktif,
                fn (MasterUnit $a, MasterUnit $b): string => $a->kode <=> $b->kode,
            ])
            ->take($jumlah)
            ->values();
    }

    /** Porsi beban satu unit terhadap unit tersibuk, dalam persen. */
    public static function porsiBeban(int $beban, int $puncak): int
    {
        return $puncak > 0 ? (int) round($beban / $puncak * 100) : 0;
    }

    /**
     * Pengaduan yang paling mendesak ditangani humas.
     *
     * Prioritas diberikan pada tiket yang lewat ambang investigasi unit,
     * lalu pada tiket yang statusnya memang menunggu tindakan.
     *
     * @return Collection<int, Pengaduan>
     */
    public static function perluTindakan(int $jumlah = 4)
    {
        return Pengaduan::query()
            ->aktif()
            ->with('masterUnit')
            ->get()
            ->sortByDesc(fn (Pengaduan $p): int => $p->zonaSla()->prioritas())
            ->sortByDesc(fn (Pengaduan $p): bool => $p->status->perluAksi())
            ->sortByDesc(fn (Pengaduan $p): bool => Sla::lewatInvestigasi($p->created_at))
            ->take($jumlah)
            ->values();
    }

    /**
     * Ringkasan kritis untuk banner peringatan.
     *
     * Mengembalikan daftar tiket yang investigation-nya sudah melewati
     * batas hari kerja unit dan jumlah telaah yang siap diracik humas.
     *
     * @return array{lewat: Collection<int, array<string, mixed>>, telaah: int, total_lewat: int}
     */
    public static function ringkasanKritis(int $jumlah = 2): array
    {
        $data = self::data();

        return [
            'lewat' => collect($data['lewat_tiket'])->take($jumlah)->values(),
            'total_lewat' => $data['total_lewat'],
            'telaah' => $data['telaah'],
        ];
    }

    /**
     * Unit yang terhubung ke SIMRS beserta disposisi terakhir.
     *
     * @return Collection<int, MasterUnit>
     */
    public static function statusKoneksi()
    {
        return MasterUnit::query()
            ->orderByDesc('koneksi_simrs')
            ->orderBy('kode')
            ->get();
    }

    /** Jumlah unit yang koneksi SIMRS-nya masih hidup. */
    public static function unitTerhubung(): int
    {
        return self::data()['unit_terhubung'];
    }

    /** Buang cache statistik, dipanggil setiap kali ada pengaduan yang berubah. */
    public static function lupaCache(): void
    {
        Cache::forget(self::KUNCI);
    }

    /**
     * Hitung seluruh angka dashboard dalam satu kali jalan, lalu simpan.
     *
     * Nilai yang masuk cache hanya array dan angka sederhana supaya aman
     * dibaca kembali dari cache file atau database.
     *
     * @return array<string, mixed>
     */
    private static function data(): array
    {
        return Cache::remember(self::KUNCI, self::TTL, self::hitung(...));
    }

    /** @return array<string, mixed> */
    private static function hitung(): array
    {
        $perTahap = Pengaduan::query()
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status')
            ->all();

        $selesai = (int) ($perTahap[StatusPengaduan::Selesai->value] ?? 0);
        $aktif = (int) array_sum(array_diff_key($perTahap, [StatusPengaduan::Selesai->value => true]));

        // Telaah: jawaban unit sudah masuk lewat balasan admin, tinggal diracik humas.
        $telaah = Pengaduan::query()
            ->aktif()
            ->whereHas('pesan', fn ($q) => $q->where('peran', 'admin'))
            ->count();

        $lewatTiket = collect();

        Pengaduan::query()
            ->aktif()
            ->with('masterUnit')
            ->get(['id', 'kode_tiket', 'unit', 'master_unit_id', 'status', 'created_at'])
            ->filter(fn (Pengaduan $pengaduan): bool => Sla::lewatInvestigasi($pengaduan->created_at))
            ->each(function (Pengaduan $pengaduan) use (&$lewatTiket): void {
                $lewatTiket->push([
                    'kode' => $pengaduan->kode_tiket,
                    'unit' => $pengaduan->namaUnit(),
                    'lewat' => Sla::hariKerjaLewat($pengaduan->created_at),
                ]);
            });

        $tepatWaktu = 0;
        $totalHari = 0.0;
        $terhitung = 0;

        Pengaduan::query()
            ->selesai()
            ->whereNotNull('selesai_at')
            ->get(['created_at', 'selesai_at'])
            ->each(function (Pengaduan $pengaduan) use (&$tepatWaktu, &$totalHari, &$terhitung): void {
                $mulai = $pengaduan->created_at;
                $selesai = $pengaduan->selesai_at;

                if ($mulai === null || $selesai === null) {
                    return;
                }

                $terhitung++;
                $totalHari += $mulai->diffInDays($selesai, true);

                if (Sla::zona($mulai, $selesai) === ZonaSla::TepatWaktu) {
                    $tepatWaktu++;
                }
            });

        $unitTerhubung = MasterUnit::query()
            ->where('koneksi_simrs', true)
            ->where('koneksi_simrs_terakhir', '>=', now()->subMinutes(15))
            ->count();

        return [
            'per_tahap' => $perTahap,
            'aktif' => $aktif,
            'selesai' => $selesai,
            'tepat_waktu' => $tepatWaktu,
            'terhitung' => $terhitung,
            'rata_rata_hari' => $terhitung > 0 ? round($totalHari / $terhitung, 1) : null,
            'telaah' => $telaah,
            'lewat_tiket' => $lewatTiket->sortByDesc('lewat')->values()->all(),
            'total_lewat' => $lewatTiket->count(),
            'unit_terhubung' => $unitTerhubung,
        ];
    }
}
