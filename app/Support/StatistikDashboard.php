<?php

namespace App\Support;

use App\Enums\StatusPengaduan;
use App\Enums\ZonaSla;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Pagination\LengthAwarePaginator;
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

    /**
     * Rata-rata waktu penyelesaian dalam hari kerja.
     *
     * Dihitung dengan hari kerja, bukan hari kalender, supaya angkanya
     * bisa dibandingkan langsung dengan batas SLA 12 hari kerja.
     */
    public static function rataRataHariKerja(): ?float
    {
        return self::data()['rata_rata_hari_kerja'];
    }

    /**
     * Jumlah pengaduan yang masih berjalan.
     */
    public static function aktif(): int
    {
        return self::data()['aktif'];
    }

    /**
     * Pemisahan pengaduan yang sedang diproses menurut jalurnya.
     *
     * Pengaduan yang tertaut ke MASTER_UNITS sedang ditelusuri unit,
     * sedangkan yang belum tertaut masih ditangani humas langsung.
     *
     * @return array{unit: int, humas: int}
     */
    public static function disposisiDiproses(): array
    {
        $data = self::data();

        return [
            'unit' => $data['diproses_unit'],
            'humas' => $data['diproses_humas'],
        ];
    }

    /**
     * Pengaduan selesai bulan ini dibanding bulan sebelumnya.
     *
     * @return array{bulan_ini: int, selisih: int}
     */
    public static function selesaiBulanIni(): array
    {
        $data = self::data();

        return [
            'bulan_ini' => $data['selesai_bulan_ini'],
            'selisih' => $data['selesai_bulan_ini'] - $data['selesai_bulan_lalu'],
        ];
    }

    /**
     * Kepatuhan SLA tiap unit, untuk kartu beban resolusi.
     *
     * Hanya pengaduan yang benar-benar ditugaskan ke unit yang dihitung,
     * jadi pengaduan yang ditangani humas langsung tidak ikut dihitung
     * sebagai beban unit. Unit tanpa pengaduan selesai tidak punya entri
     * sama sekali supaya tampilan bisa membedakan 0% dari belum ada data.
     *
     * @return array<int, float> Indeks master_unit_id, nilai persen.
     */
    public static function kepatuhanSlaUnit(): array
    {
        return self::data()['kepatuhan_unit'];
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
     * Daftar ini sengaja hanya menampilkan tiket yang sudah mendekati batas
     * SLA atau ditandai kasus berat. Tiket yang masih lama dan biasa-biasa
     * saja tidak perlu membanjiri antrean, karena daftar ini dipakai untuk
     * memutuskan tiket mana yang harus ditangani lebih dulu.
     *
     * Urutan disusun di dalam memori karena zona SLA dihitung dengan
     * diffInWeekdays yang tidak bisa diterjemahkan ke SQL, jadi paginasi
     * dilakukan di atas hasil akhir, bukan di query.
     *
     * @param  int  $perHalaman  Jumlah kartu per halaman.
     * @param  int|null  $halaman  Halaman yang diminta, null berarti ambil dari query.
     */
    public static function perluTindakan(int $perHalaman = 4, ?int $halaman = null): LengthAwarePaginator
    {
        $daftar = Pengaduan::query()
            ->aktif()
            ->with('masterUnit')
            ->withExists(['pesan as sudah_dibalas' => fn ($q) => $q->jawaban()])
            ->get()
            ->filter(fn (Pengaduan $p): bool => self::mendesak($p))
            ->sortByDesc(fn (Pengaduan $p): bool => $p->status->perluAksi())
            ->sortByDesc(fn (Pengaduan $p): int => $p->zonaSla()->prioritas())
            // Kasus berat ditumpuk paling atas karena menghendaki telaah
            // komite etik, jadi tidak bisa menunggu mendekati batas SLA.
            ->sortByDesc(fn (Pengaduan $p): bool => $p->kasus_berat)
            ->values();

        $halaman ??= LengthAwarePaginator::resolveCurrentPage('halaman_tindakan');

        return new LengthAwarePaginator(
            $daftar->forPage($halaman, $perHalaman)->values(),
            $daftar->count(),
            $perHalaman,
            $halaman,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'halaman_tindakan',
                // Parameter lain pada query ikut dibawa supaya tautan
                // halaman tidak menghilangkan filter yang sedang aktif.
                'query' => request()->except('halaman_tindakan'),
            ],
        );
    }

    /**
     * True bila sebuah tiket layak masuk daftar butuh tindakan segera.
     *
     * Ada dua syarat: SLA-nya sudah memasuki dua hari kerja terakhir
     * (zona Mendek dan Terlambat memakai angka peringatan yang sama), atau
     * tiketnya ditandai kasus berat. Kasus berat tetap masuk meski SLA-nya
     * longgar karena penandaan itu berarti perlu telaah komite etik.
     */
    public static function mendesak(Pengaduan $pengaduan): bool
    {
        return $pengaduan->kasus_berat
            || $pengaduan->zonaSla()->prioritas() >= ZonaSla::Mendek->prioritas();
    }

    /**
     * Ringkasan kritis untuk banner peringatan.
     *
     * Mengembalikan daftar tiket yang investigasinya sudah melewati
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
            ->withCount(['pengaduan as beban_aktif' => fn ($q) => $q->aktif()])
            ->orderByDesc('koneksi_simrs')
            ->orderByDesc('beban_aktif')
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

        // Telaah: jawaban unit sudah masuk lewat balasan humas atau langsung
        // dari PIC unit, tinggal diracik humas.
        $telaah = Pengaduan::query()
            ->aktif()
            ->whereHas('pesan', fn ($q) => $q->jawaban())
            ->count();

        $disposisi = Pengaduan::query()
            ->where('status', StatusPengaduan::Diproses->value)
            ->selectRaw('count(*) as total, count(master_unit_id) as ke_unit')
            ->first();

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

        $selesaiTiket = Pengaduan::query()
            ->selesai()
            ->whereNotNull('selesai_at')
            ->get(['created_at', 'selesai_at', 'master_unit_id', 'kasus_berat']);

        $tepatWaktu = 0;
        $totalHariKerja = 0.0;
        $terhitung = 0;
        $bulanIni = 0;
        $bulanLalu = 0;

        // Kepatuhan SLA per unit, indeks master_unit_id.
        $kepatuhanUnit = [];

        $selesaiTiket->each(function (Pengaduan $pengaduan) use (
            &$tepatWaktu,
            &$totalHariKerja,
            &$terhitung,
            &$bulanIni,
            &$bulanLalu,
            &$kepatuhanUnit,
        ): void {
            $mulai = $pengaduan->created_at;
            $selesaiAt = $pengaduan->selesai_at;

            if ($mulai === null || $selesaiAt === null) {
                return;
            }

            $terhitung++;
            $totalHariKerja += (int) abs($mulai->diffInWeekdays($selesaiAt, true));

            // Kasus berat punya target sendiri yang lebih panjang, jadi zona
            // SLA-nya dihitung dengan batas itu, bukan batas standar 12 hari.
            $tepat = Sla::zona($mulai, $selesaiAt, $pengaduan->kasus_berat) === ZonaSla::TepatWaktu;

            if ($tepat) {
                $tepatWaktu++;
            }

            if ($selesaiAt->isCurrentMonth()) {
                $bulanIni++;
            } elseif ($selesaiAt->isSameMonth(now()->subMonth())) {
                $bulanLalu++;
            }

            $unit = $pengaduan->master_unit_id;

            if ($unit === null) {
                return;
            }

            $kepatuhanUnit[$unit] ??= ['terhitung' => 0, 'tepat' => 0];
            $kepatuhanUnit[$unit]['terhitung']++;

            if ($tepat) {
                $kepatuhanUnit[$unit]['tepat']++;
            }
        });

        $kepatuhanUnit = collect($kepatuhanUnit)
            ->map(fn (array $baris): ?float => $baris['terhitung'] > 0
                ? round($baris['tepat'] / $baris['terhitung'] * 100, 1)
                : null)
            ->all();

        $unitTerhubung = MasterUnit::query()->terhubung()->count();

        return [
            'per_tahap' => $perTahap,
            'aktif' => $aktif,
            'selesai' => $selesai,
            'tepat_waktu' => $tepatWaktu,
            'terhitung' => $terhitung,
            'rata_rata_hari_kerja' => $terhitung > 0 ? round($totalHariKerja / $terhitung, 1) : null,
            'telaah' => $telaah,
            'lewat_tiket' => $lewatTiket->sortByDesc('lewat')->values()->all(),
            'total_lewat' => $lewatTiket->count(),
            'unit_terhubung' => $unitTerhubung,
            'diproses_unit' => (int) ($disposisi?->ke_unit ?? 0),
            'diproses_humas' => (int) ($disposisi?->total ?? 0) - (int) ($disposisi?->ke_unit ?? 0),
            'selesai_bulan_ini' => $bulanIni,
            'selesai_bulan_lalu' => $bulanLalu,
            'kepatuhan_unit' => $kepatuhanUnit,
        ];
    }
}
