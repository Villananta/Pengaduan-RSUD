<?php

namespace App\Support;

use App\Enums\StatusInvestigasi;
use App\Enums\StatusPengaduan;
use App\Enums\ZonaSla;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Data siap tampil untuk dashboard unit layanan.
 *
 * Seluruh angka sudah dihitung di App\Support\StatistikUnit, jadi template
 * cukup memanggil method di kelas ini. Tidak ada perhitungan sama sekali
 * di dalam Blade, mengikuti aturan yang berlaku untuk konsol admin.
 */
final class DashboardUnit
{
    /**
     * @param  array<string, int>  $perTahap
     * @param  Collection<int, Pengaduan>  $mendesak
     * @param  Collection<int, Pengaduan>  $terbaru
     */
    public function __construct(
        public readonly MasterUnit $unit,
        public readonly array $perTahap,
        public readonly int $total,
        public readonly int $aktif,
        public readonly int $terlambat,
        public readonly int $mendek,
        public readonly ?float $rataRataHariKerja,
        public readonly int $selesaiBulanIni,
        public readonly Collection $mendesak,
        public readonly Collection $terbaru,
        public readonly int $hariKerja,
        public readonly int $hariInvestigasi,
    ) {}

    /** Jumlah pengaduan pada satu tahap. */
    public function jumlah(StatusPengaduan $tahap): int
    {
        return $this->perTahap[$tahap->value] ?? 0;
    }

    /** Jumlah pengaduan yang sudah ditutup. */
    public function selesai(): int
    {
        return $this->jumlah(StatusPengaduan::Selesai);
    }

    /** True bila ada pengaduan unit yang melewati batas SLA. */
    public function adaPelanggaran(): bool
    {
        return $this->terlambat > 0;
    }

    /** True bila ada pengaduan memasuki hari-hari terakhir sebelum batas. */
    public function adaPeringatan(): bool
    {
        return $this->mendek > 0;
    }

    /**
     * Kalimat ringkasan kondisi SLA unit untuk banner peringatan.
     *
     * Bentuk kalimat menyesuaikan kondisi supaya banner tetap terbaca baik
     * saat ada keterlambatan, saat hanya mendek batas, maupun saat semuanya
     * masih aman.
     */
    public function kalimatSla(): string
    {
        if ($this->adaPelanggaran()) {
            $kode = $this->kodeTerlambat();

            return 'Terdeteksi '.$this->terlambat.' pengaduan melewati batas '
                .$this->hariKerja.' hari kerja'
                .($kode !== '' ? ' ('.$kode.')' : '')
                .'. Periksa daftar pengaduan butuh tindakan di bawah.';
        }

        if ($this->adaPeringatan()) {
            return $this->mendek.' pengaduan memasuki dua hari kerja terakhir '
                .'sebelum batas '.$this->hariKerja.' hari kerja. '
                .'Tuntaskan lebih dulu sebelum masuk zona terlambat.';
        }

        return 'Seluruh pengaduan unit masih dalam batas '.$this->hariKerja
            .' hari kerja, dengan '.$this->selesaiBulanIni.' pengaduan selesai bulan berjalan.';
    }

    /** Kode tiket terlambat pada daftar mendesak, dipotong supaya ringkas. */
    public function kodeTerlambat(): string
    {
        $kode = $this->mendesak
            ->filter(fn (Pengaduan $pengaduan): bool => $pengaduan->zonaSla() === ZonaSla::Terlambat)
            ->pluck('kode_tiket')
            ->all();

        return Str::limit(implode(', ', $kode), 48);
    }

    /** Kalimat ringkasan pada kaki daftar pengaduan butuh tindakan. */
    public function ringkasMendesak(): string
    {
        return 'Menampilkan '.$this->mendesak->count()
            .' pengaduan dari '.$this->aktif
            .' pengaduan aktif, diurutkan dari yang paling mendesak';
    }

    /** Tautan ke daftar pengaduan konsol admin yang sudah menyaring unit ini. */
    public function urlLihatSemua(): string
    {
        return route('admin.pengaduan.index', ['unit' => $this->unit->kode]);
    }

    /** Status investigasi sebuah pengaduan, untuk keterangan sisi unit. */
    public function investigasi(Pengaduan $pengaduan): StatusInvestigasi
    {
        return StatusInvestigasi::dariPengaduan($pengaduan, (bool) $pengaduan->sudah_dibalas);
    }

    /**
     * Susunan satu kartu pengaduan pada daftar butuh tindakan.
     *
     * @return array<string, mixed>
     */
    public function kartuMendesak(Pengaduan $pengaduan): array
    {
        $zona = $pengaduan->zonaSla();

        return [
            'kode' => $pengaduan->kode_tiket,
            'subjek' => $pengaduan->subjek,
            'pelapor' => $pengaduan->nama_lengkap,
            'nrm' => Pengaduan::normalisasiNrm($pengaduan->nrm),
            'status' => $pengaduan->status,
            'kasusBerat' => $pengaduan->kasus_berat,
            'investigasi' => $this->investigasi($pengaduan),
            'kartu' => match ($zona) {
                ZonaSla::Terlambat => 'bg-error-container/20',
                ZonaSla::Mendek => 'bg-surface-container-lowest border border-secondary/30 ring-1 ring-secondary/20',
                default => 'bg-surface-container-low',
            },
            'sla' => $this->barisSla($zona, Sla::hariKe($pengaduan->created_at), $pengaduan->sisaHariSla()),
            'url' => route('admin.pengaduan.show', $pengaduan->kode_tiket),
        ];
    }

    /**
     * Satu baris tabel pengaduan terbaru unit.
     *
     * @return array<string, mixed>
     */
    public function barisTerbaru(Pengaduan $pengaduan): array
    {
        $zona = $pengaduan->zonaSla();

        return [
            'kode' => $pengaduan->kode_tiket,
            'subjek' => $pengaduan->subjek,
            'pelapor' => $pengaduan->nama_lengkap,
            'nrm' => Pengaduan::normalisasiNrm($pengaduan->nrm),
            'tanggal' => $pengaduan->created_at->format('d M Y, H:i'),
            'status' => $pengaduan->status,
            'investigasi' => $this->investigasi($pengaduan),
            'sla' => $this->barisSla($zona, Sla::hariKe($pengaduan->created_at), $pengaduan->sisaHariSla()),
            'redup' => $pengaduan->status->selesai(),
            'url' => route('admin.pengaduan.show', $pengaduan->kode_tiket),
        ];
    }

    /**
     * Baris posisi hari kerja pada kartu dan tabel.
     *
     * Posisi diambil dari zona SLA (ambang 12 hari kerja), bukan dari limit
     * investigasi unit, supaya angka yang tampil di sini sama dengan yang
     * dibaca pelapor lewat halaman lacak tiket.
     *
     * @return array{label: string, ikon: string, nada: string}
     */
    private function barisSla(ZonaSla $zona, int $hariKe, int $sisa): array
    {
        return match ($zona) {
            ZonaSla::Terlambat => [
                'label' => 'Hari ke-'.$hariKe.' (LEWAT BATAS)',
                'ikon' => 'warning',
                'nada' => 'text-error',
            ],
            ZonaSla::Mendek => [
                'label' => 'Hari ke-'.$hariKe.', sisa '.$sisa.' hari kerja',
                'ikon' => 'timelapse',
                'nada' => 'text-on-tertiary-container',
            ],
            default => [
                'label' => 'Hari ke-'.$hariKe.', sisa '.$sisa.' hari kerja',
                'ikon' => 'schedule',
                'nada' => 'text-secondary',
            ],
        };
    }
}
