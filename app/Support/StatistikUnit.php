<?php

namespace App\Support;

use App\Enums\StatusPengaduan;
use App\Enums\ZonaSla;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Support\Collection;

/**
 * Angka pengaduan milik satu unit layanan untuk dashboard unit kerja.
 *
 * Seluruh query dibatasi pada master_unit_id unit yang sedang dibuka,
 * supaya unit tidak pernah melihat beban milik unit lain. Zona SLA tetap
 * memakai rumus di App\Support\Sla, sehingga angka di halaman unit dan
 * konsol admin berasal dari satu sumber yang sama dan tidak berbeda
 * hanya karena dihitung di dua tempat.
 */
final class StatistikUnit
{
    /**
     * Ringkasan angka untuk kartu status dan panel ringkas.
     *
     * @return array{
     *     per_tahap: array<string, int>,
     *     total: int,
     *     aktif: int,
     *     terlambat: int,
     *     mendek: int,
     *     rata_rata_hari_kerja: ?float,
     *     selesai_bulan_ini: int,
     * }
     */
    public static function ringkasan(MasterUnit $unit): array
    {
        $perTahap = Pengaduan::query()
            ->where('master_unit_id', $unit->id)
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status')
            ->all();

        // Tahap yang belum pernah terisi tetap mendapat angka nol supaya
        // kartu status selalu tampil lengkap, bukan hilang satu per satu
        // ketika unit ini belum pernah menerima pengaduan di tahap itu.
        $perTahap = array_map(
            fn ($jumlah): int => (int) $jumlah,
            array_merge(
                array_fill_keys(array_map(
                    fn (StatusPengaduan $tahap): string => $tahap->value,
                    StatusPengaduan::cases(),
                ), 0),
                $perTahap,
            ),
        );

        $total = array_sum($perTahap);
        $aktif = $total - $perTahap[StatusPengaduan::Selesai->value];

        $zona = self::hitungZona($unit);

        $selesai = Pengaduan::query()
            ->where('master_unit_id', $unit->id)
            ->selesai()
            ->whereNotNull('selesai_at')
            ->get(['created_at', 'selesai_at']);

        $totalHariKerja = 0;
        $bulanIni = 0;

        $selesai->each(function (Pengaduan $pengaduan) use (&$totalHariKerja, &$bulanIni): void {
            // Hari kerja, bukan hari kalender, supaya satuan rata-rata di
            // sini sejalan dengan batas SLA yang juga dihitung hari kerja.
            $totalHariKerja += (int) abs($pengaduan->created_at->diffInWeekdays($pengaduan->selesai_at, true));

            if ($pengaduan->selesai_at->isCurrentMonth()) {
                $bulanIni++;
            }
        });

        return [
            'per_tahap' => $perTahap,
            'total' => $total,
            'aktif' => $aktif,
            'terlambat' => $zona['terlambat'],
            'mendek' => $zona['mendek'],
            'rata_rata_hari_kerja' => $selesai->count() > 0
                ? round($totalHariKerja / $selesai->count(), 1)
                : null,
            'selesai_bulan_ini' => $bulanIni,
        ];
    }

    /**
     * Jumlah pengaduan aktif milik unit pada tiap zona SLA.
     *
     * Zona SLA dihitung dengan diffInWeekdays yang tidak bisa diterjemahkan
     * ke SQL, jadi penyaringan dilakukan setelah seluruh tiket aktif dibaca
     * ke memori. Dilakukan satu kali saja supaya kartu status dan banner
     * peringatan tidak menjalankan query yang sama dua kali.
     *
     * @return array{terlambat: int, mendek: int}
     */
    private static function hitungZona(MasterUnit $unit): array
    {
        $aktif = Pengaduan::query()
            ->where('master_unit_id', $unit->id)
            ->aktif()
            ->get(['id', 'created_at', 'selesai_at', 'kasus_berat', 'status']);

        return [
            'terlambat' => $aktif
                ->filter(fn (Pengaduan $pengaduan): bool => $pengaduan->zonaSla() === ZonaSla::Terlambat)
                ->count(),
            'mendek' => $aktif
                ->filter(fn (Pengaduan $pengaduan): bool => $pengaduan->zonaSla() === ZonaSla::Mendek)
                ->count(),
        ];
    }

    /**
     * Pengaduan aktif yang mendesak ditangani unit ini.
     *
     * Daftar dibatasi pada tiket yang SLA-nya sudah memasuki hari-hari
     * terakhir sebelum batas atau yang ditandai kasus berat. Tiket yang
     * masih lama sengaja tidak ikut supaya antrean kerja unit tetap
     * berisi pekerjaan yang benar-benar harus didahulukan.
     *
     * Urutan disusun di dalam memori mengikuti konsol admin: kasus berat
     * paling atas, lalu zona SLA paling mendesak, lalu tahap yang menunggu
     * kelengkapan pelapor.
     *
     * @return Collection<int, Pengaduan>
     */
    public static function mendesak(MasterUnit $unit, int $jumlah = 20): Collection
    {
        return Pengaduan::query()
            ->where('master_unit_id', $unit->id)
            ->aktif()
            ->with('masterUnit')
            ->withExists(['pesan as sudah_dibalas' => fn ($q) => $q->jawaban()])
            ->get()
            ->filter(fn (Pengaduan $pengaduan): bool => $pengaduan->kasus_berat
                || $pengaduan->zonaSla()->prioritas() >= ZonaSla::Mendek->prioritas())
            ->sortByDesc(fn (Pengaduan $pengaduan): bool => $pengaduan->status->perluAksi())
            ->sortByDesc(fn (Pengaduan $pengaduan): int => $pengaduan->zonaSla()->prioritas())
            // Kasus berat ditumpuk paling atas karena menghendaki telaah
            // komite etik, jadi tidak boleh menunggu mendekati batas SLA.
            ->sortByDesc(fn (Pengaduan $pengaduan): bool => $pengaduan->kasus_berat)
            ->take($jumlah)
            ->values();
    }

    /**
     * Pengaduan terbaru milik unit, dipakai tabel ringkas di bawah.
     *
     * Tiket yang belum ditutup didahulukan supaya halaman tidak membuka
     * dengan deretan tiket lama yang sudah selesai.
     *
     * @return Collection<int, Pengaduan>
     */
    public static function terbaru(MasterUnit $unit, int $jumlah = 8): Collection
    {
        return Pengaduan::query()
            ->where('master_unit_id', $unit->id)
            ->with('masterUnit')
            ->withExists(['pesan as sudah_dibalas' => fn ($q) => $q->jawaban()])
            ->orderByRaw('case when status = ? then 1 else 0 end', [StatusPengaduan::Selesai->value])
            ->latest('created_at')
            ->take($jumlah)
            ->get();
    }
}
