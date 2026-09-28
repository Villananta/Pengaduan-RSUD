<?php

namespace App\Support;

use App\Enums\StatusPengaduan;
use App\Models\Pengaduan;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Angka statistik pengaduan yang ditampilkan ke publik.
 *
 * Seluruh angka dihitung dari tabel pengaduan. Tidak ada lagi angka
 * rekaan yang diketik manual di controller atau view.
 */
final class StatistikPengaduan
{
    private const KUNCI = 'statistik-pengaduan';

    private const TTL = 300;

    /**
     * Ringkasan metrik untuk halaman pengajuan aduan.
     *
     * Nilai *_kosong bernilai null ketika belum ada data yang cukup,
     * sehingga view dapat menampilkan "Belum ada data" alih-alih 0%.
     *
     * @return array<int, array{nilai: string, label: string, sub: string, tersedia: bool}>
     */
    public static function metrik(): array
    {
        $data = Cache::remember(self::KUNCI, self::TTL, self::hitung(...));

        $total = $data['total'];
        $selesai = $data['selesai'];
        $tepatWaktu = $data['tepat_waktu'];

        return [
            [
                'nilai' => (string) number_format($total).'+',
                'label' => 'Aduan Tertangani',
                'sub' => $total > 0 ? 'Tersimpan resmi di sistem' : 'Belum ada pengaduan masuk',
                'tersedia' => true,
            ],
            [
                'nilai' => $selesai > 0 ? self::persen($tepatWaktu, $selesai) : 'Belum ada data',
                'label' => 'Kepatuhan SLA',
                'sub' => 'Selesai dalam '.Sla::hariKerja().' hari kerja',
                'tersedia' => $selesai > 0,
            ],
            [
                'nilai' => $data['rata_rata_hari'] !== null
                    ? number_format($data['rata_rata_hari'], 1, ',', '.').' Hari'
                    : 'Belum ada data',
                'label' => 'Rata-rata Resolusi',
                'sub' => $data['rata_rata_hari'] !== null
                    ? 'Dari '.$selesai.' pengaduan selesai'
                    : 'Menunggu pengaduan pertama selesai',
                'tersedia' => $data['rata_rata_hari'] !== null,
            ],
            [
                'nilai' => (string) $data['aktif'],
                'label' => 'Pengaduan Diproses',
                'sub' => 'Belum berstatus selesai',
                'tersedia' => true,
            ],
        ];
    }

    /** Rata-rata waktu penyelesaian pengaduan selesai dalam hari kalender. */
    public static function rataRataHari(): ?float
    {
        $rata = Cache::remember(self::KUNCI, self::TTL, self::hitung(...))['rata_rata_hari'];

        return $rata;
    }

    /** Kalimat ringkas rata-rata resolusi, atau null bila belum ada data. */
    public static function ringkasRataRata(): ?string
    {
        $rata = self::rataRataHari();

        return $rata === null
            ? null
            : 'Penyelesaian Rata-rata '.number_format($rata, 1, ',', '.').' Hari Kerja';
    }

    private static function hitung(): array
    {
        $total = Pengaduan::query()->count();
        $selesai = Pengaduan::query()
            ->where('status', StatusPengaduan::Selesai->value)
            ->count();

        $aktif = Pengaduan::query()
            ->where('status', '!=', StatusPengaduan::Selesai->value)
            ->count();

        $tepatWaktu = 0;
        $totalHari = 0.0;
        $terhitung = 0;

        Pengaduan::query()
            ->where('status', StatusPengaduan::Selesai->value)
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

                if (Sla::zona($mulai, $selesai)->value === 'tepat_waktu') {
                    $tepatWaktu++;
                }
            });

        return [
            'total' => $total,
            'selesai' => $selesai,
            'aktif' => $aktif,
            'tepat_waktu' => $tepatWaktu,
            'terhitung' => $terhitung,
            'rata_rata_hari' => $terhitung > 0 ? round($totalHari / $terhitung, 1) : null,
        ];
    }

    private static function persen(int $bagian, int $total): string
    {
        return number_format($total > 0 ? $bagian / $total * 100 : 0, 1, ',', '.').'%';
    }

    /** Target penyelesaian untuk satu pengaduan. */
    public static function target(CarbonInterface $mulai): CarbonInterface
    {
        return Sla::target($mulai);
    }
}
