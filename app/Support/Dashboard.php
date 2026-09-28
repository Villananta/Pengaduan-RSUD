<?php

namespace App\Support;

use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Support\Collection;

/**
 * Data tampilan untuk beranda konsol admin.
 *
 * Controller hanya meneruskan objek ini ke view supaya penyusunan
 * kalimat ringkasan tidak tersebar di dalam template.
 */
final class Dashboard
{
    /**
     * @param  array<string, int>  $perTahap
     * @param  Collection<int, Pengaduan>  $perluTindakan
     * @param  Collection<int, MasterUnit>  $unitTerbebani
     * @param  Collection<int, MasterUnit>  $statusKoneksi
     * @param  array{lewat: Collection<int, array<string, mixed>>, telaah: int, total_lewat: int}  $kritis
     */
    public function __construct(
        public readonly array $perTahap,
        public readonly ?float $kepatuhan,
        public readonly ?float $rataRata,
        public readonly int $aktif,
        public readonly Collection $perluTindakan,
        public readonly Collection $unitTerbebani,
        public readonly int $puncakBeban,
        public readonly array $kritis,
        public readonly Collection $statusKoneksi,
        public readonly int $unitTerhubung,
        public readonly int $hariKerja,
        public readonly int $hariInvestigasi,
    ) {}

    /** Jumlah pengaduan pada satu tahap. */
    public function jumlah(StatusPengaduan $tahap): int
    {
        return $this->perTahap[$tahap->value] ?? 0;
    }

    /** Jumlah pengaduan yang jawabannya sudah masuk dan tinggal diracik humas. */
    public function telaah(): int
    {
        return $this->kritis['telaah'];
    }

    /** Jumlah tiket yang investigasi unitnya sudah melewati batas hari kerja. */
    public function totalLewat(): int
    {
        return $this->kritis['total_lewat'];
    }

    /**
     * Kalimat ringkasan untuk banner peringatan kritis.
     *
     * Bentuk kalimat menyesuaikan kondisi supaya banner tetap terbaca
     * baik saat semua tiket aman maupun saat ada yang terlambat.
     */
    public function kalimatKritis(): string
    {
        $totalLewat = $this->totalLewat();

        $investigasi = $totalLewat > 0
            ? 'Terdeteksi '.$totalLewat.' tiket yang melewati batas investigasi unit '
                .$this->hariInvestigasi.' hari kerja ('.$this->sebutkanTiket().')'
            : 'Tidak ada tiket yang melewati batas investigasi unit '.$this->hariInvestigasi.' hari kerja';

        return $investigasi
            .', dan '.$this->telaah().' telaah jawaban unit siap diracik humas'
            .' untuk menjaga ambang '.$this->hariKerja.' hari kerja Permenkes RI.';
    }

    /** True bila ada tiket yang investigasinya sudah melewati batas. */
    public function adaPelanggaran(): bool
    {
        return $this->totalLewat() > 0;
    }

    /** Porsi beban unit terhadap unit tersibuk, dalam persen. */
    public function porsi(MasterUnit $unit): int
    {
        return StatistikDashboard::porsiBeban($unit->beban_aktif, $this->puncakBeban);
    }

    /**
     * Empat sub-status sinkronisasi unit.
     *
     * Tiga tahap pertama dipetakan langsung dari tahap pengaduan,
     * sedangkan tahap keempat memakai telaah jawaban yang benar-benar
     * sudah masuk, bukan pengaduan yang sudah ditutup.
     *
     * @return array<int, array{status: StatusPengaduan, jumlah: int, keterangan: string, url: string, bolehDitutup: bool}>
     */
    public function subStatus(): array
    {
        $daftarUrl = route('admin.pengaduan.index');

        return [
            [
                'status' => StatusPengaduan::Diterima,
                'jumlah' => $this->jumlah(StatusPengaduan::Diterima),
                'keterangan' => 'Menunggu unit membuka tiket',
                'url' => route('admin.pengaduan.index', ['status' => StatusPengaduan::Diterima->value]),
                'bolehDitutup' => true,
            ],
            [
                'status' => StatusPengaduan::Diproses,
                'jumlah' => $this->jumlah(StatusPengaduan::Diproses),
                'keterangan' => 'Sedang diinvestigasi unit',
                'url' => route('admin.pengaduan.index', ['status' => StatusPengaduan::Diproses->value]),
                'bolehDitutup' => true,
            ],
            [
                'status' => StatusPengaduan::Revisi,
                'jumlah' => $this->jumlah(StatusPengaduan::Revisi),
                'keterangan' => 'Unit butuh informasi tambahan',
                'url' => route('admin.pengaduan.index', ['status' => StatusPengaduan::Revisi->value]),
                'bolehDitutup' => true,
            ],
            [
                'status' => StatusPengaduan::Selesai,
                'jumlah' => $this->telaah(),
                'keterangan' => 'Menunggu racikan resmi humas',
                'url' => $daftarUrl.'?'.http_build_query(['telaah' => 1]),
                'bolehDitutup' => false,
            ],
        ];
    }

    /** Tahapan prosedur internal untuk stepper sidebar. */
    public function prosedur(): array
    {
        return config('pengaduan.prosedur', []);
    }

    /**
     * Sebutkan sebagian tiket terlambat beserta unitnya.
     */
    private function sebutkanTiket(): string
    {
        $sebutan = $this->kritis['lewat']
            ->map(fn (array $baris): string => $baris['kode'].' di '.$baris['unit'].' ('.$baris['lewat'].' HK)')
            ->all();

        $sisa = $this->totalLewat() - count($sebutan);

        if ($sisa > 0) {
            $sebutan[] = 'dan '.$sisa.' tiket lain';
        }

        return implode(' serta ', $sebutan);
    }
}
