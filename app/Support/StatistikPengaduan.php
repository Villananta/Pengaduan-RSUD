<?php

namespace App\Support;

use App\Enums\StatusPengaduan;
use App\Enums\ZonaSla;
use App\Models\Pengaduan;
use Illuminate\Support\Facades\Cache;

/**
 * Angka statistik pengaduan yang ditampilkan ke publik.
 *
 * Seluruh angka dihitung dari tabel pengaduan. Tidak ada lagi angka
 * rekaan yang diketik manual di controller atau view.
 *
 * Satuan waktu di sini adalah hari kerja, sama dengan yang dipakai
 * App\Support\Sla. Menghitung dengan hari kalender membuat rata-rata
 * publik menyimpang dari batas 12 hari kerja yang dijanjikan di halaman
 * yang sama, terutama untuk tiket yang dimulai dan selesai sekitar akhir pekan.
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
        $tepatWaktu = $data['tepat_waktu'];

        // Pembagi kepatuhan harus $terhitung, bukan jumlah tiket selesai.
        // Tiket yang sudah berstatus Selesai tapi belum punya selesai_at
        // tidak pernah ikut dihitung, sehingga memakainya sebagai pembagi
        // membuat persen kepatuhan turun tanpa sebab yang sebenarnya.
        $terhitung = $data['terhitung'];

        return [
            [
                'nilai' => (string) number_format($total).'+',
                'label' => 'Aduan Tertangani',
                'sub' => $total > 0 ? 'Tersimpan resmi di sistem' : 'Belum ada pengaduan masuk',
                'tersedia' => true,
            ],
            [
                'nilai' => $terhitung > 0 ? self::persen($tepatWaktu, $terhitung) : 'Belum ada data',
                'label' => 'Kepatuhan SLA',
                'sub' => 'Selesai dalam '.Sla::hariKerja().' hari kerja',
                'tersedia' => $terhitung > 0,
            ],
            [
                'nilai' => $data['rata_rata_hari_kerja'] !== null
                    ? number_format($data['rata_rata_hari_kerja'], 1, ',', '.').' Hari Kerja'
                    : 'Belum ada data',
                'label' => 'Rata-rata Resolusi',
                'sub' => $data['rata_rata_hari_kerja'] !== null
                    ? 'Dari '.$terhitung.' pengaduan selesai'
                    : 'Menunggu pengaduan pertama selesai',
                'tersedia' => $data['rata_rata_hari_kerja'] !== null,
            ],
            [
                'nilai' => (string) $data['aktif'],
                'label' => 'Pengaduan Diproses',
                'sub' => 'Belum berstatus selesai',
                'tersedia' => true,
            ],
        ];
    }

    /** Rata-rata waktu penyelesaian pengaduan selesai dalam hari kerja. */
    public static function rataRataHariKerja(): ?float
    {
        $rata = Cache::remember(self::KUNCI, self::TTL, self::hitung(...))['rata_rata_hari_kerja'];

        return $rata;
    }

    /** Kalimat ringkas rata-rata resolusi, atau null bila belum ada data. */
    public static function ringkasRataRata(): ?string
    {
        $rata = self::rataRataHariKerja();

        return $rata === null
            ? null
            : 'Penyelesaian Rata-rata '.number_format($rata, 1, ',', '.').' Hari Kerja';
    }

    /** Buang cache statistik publik, dipanggil setiap kali ada pengaduan yang berubah. */
    public static function lupaCache(): void
    {
        Cache::forget(self::KUNCI);
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
        $totalHariKerja = 0.0;
        $terhitung = 0;

        Pengaduan::query()
            ->where('status', StatusPengaduan::Selesai->value)
            ->whereNotNull('selesai_at')
            ->get(['created_at', 'selesai_at', 'kasus_berat'])
            ->each(function (Pengaduan $pengaduan) use (&$tepatWaktu, &$totalHariKerja, &$terhitung): void {
                $mulai = $pengaduan->created_at;
                $selesai = $pengaduan->selesai_at;

                if ($mulai === null || $selesai === null) {
                    return;
                }

                $terhitung++;
                // Hari kerja, bukan hari kalender, supaya satuan yang dipakai
                // di sini sama dengan batas SLA dan dengan yang dihitung
                // StatistikDashboard untuk sisi admin.
                $totalHariKerja += (int) abs($mulai->diffInWeekdays($selesai, true));

                // Kasus berat punya target yang lebih panjang, jadi dihitung
                // dengan batasnya sendiri supaya tidak otomatis masuk terlambat.
                if (Sla::zona($mulai, $selesai, $pengaduan->kasus_berat) === ZonaSla::TepatWaktu) {
                    $tepatWaktu++;
                }
            });

        return [
            'total' => $total,
            'selesai' => $selesai,
            'aktif' => $aktif,
            'tepat_waktu' => $tepatWaktu,
            'terhitung' => $terhitung,
            'rata_rata_hari_kerja' => $terhitung > 0 ? round($totalHariKerja / $terhitung, 1) : null,
        ];
    }

    private static function persen(int $bagian, int $total): string
    {
        return number_format($total > 0 ? $bagian / $total * 100 : 0, 1, ',', '.').'%';
    }
}
