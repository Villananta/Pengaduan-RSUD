<?php

namespace App\Support;

use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Data tampilan untuk beranda konsol admin.
 *
 * Controller hanya meneruskan objek ini ke view supaya penyusunan
 * kalimat ringkasan, warna, dan daftar tombol tidak tersebar di
 * dalam template.
 */
final class Dashboard
{
    /**
     * @param  array<string, int>  $perTahap
     * @param  array{unit: int, humas: int}  $disposisi
     * @param  array{bulan_ini: int, selisih: int}  $selesaiBulan
     * @param  Collection<int, Pengaduan>  $perluTindakan
     * @param  Collection<int, MasterUnit>  $unitTerbebani
     * @param  array{lewat: Collection<int, array<string, mixed>>, telaah: int, total_lewat: int}  $kritis
     * @param  Collection<int, MasterUnit>  $statusKoneksi
     * @param  array<int, float>  $kepatuhanSlaUnit
     */
    public function __construct(
        public readonly array $perTahap,
        public readonly ?float $kepatuhan,
        public readonly ?float $rataRata,
        public readonly array $disposisi,
        public readonly array $selesaiBulan,
        public readonly int $aktif,
        public readonly Collection $perluTindakan,
        public readonly Collection $unitTerbebani,
        public readonly int $puncakBeban,
        public readonly array $kritis,
        public readonly Collection $statusKoneksi,
        public readonly array $kepatuhanSlaUnit,
        public readonly int $unitTerhubung,
        public readonly int $hariKerja,
        public readonly int $hariInvestigasi,
        public readonly int $standarKepatuhan,
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

    /** True bila ada tiket yang investigasinya sudah melewati batas. */
    public function adaPelanggaran(): bool
    {
        return $this->totalLewat() > 0;
    }

    /** Berapa persen lebih cepat rata-rata penyelesaian dari batas SLA. */
    public function persenLebihCepat(): ?int
    {
        if ($this->rataRata === null || $this->hariKerja === 0) {
            return null;
        }

        return (int) round(($this->hariKerja - $this->rataRata) / $this->hariKerja * 100);
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
            ? 'Terdeteksi '.$totalLewat.' tiket yang melewati batas '.$this->hariInvestigasi
                .' hari investigasi unit ('.$this->sebutkanTiket().')'
            : 'Tidak ada tiket yang melewati batas '.$this->hariInvestigasi.' hari investigasi unit';

        return $investigasi
            .', dan '.$this->telaah().' telaah jawaban unit sudah masuk'
            .' siap diracik humas untuk menjaga ambang '.$this->hariKerja.' hari kerja Permenkes RI.';
    }

    /** Porsi beban unit terhadap unit tersibuk, dalam persen. */
    public function porsi(MasterUnit $unit): int
    {
        return StatistikDashboard::porsiBeban($unit->beban_aktif, $this->puncakBeban);
    }

    /** Kepatuhan SLA unit dalam persen, null bila unit belum punya pengaduan selesai. */
    public function kepatuhanUnit(MasterUnit $unit): ?float
    {
        return $this->kepatuhanSlaUnit[$unit->id] ?? null;
    }

    /**
     * Empat kartu sinkronisasi sub-status unit layanan terkait.
     *
     * Tiga kartu pertama dipetakan langsung dari tahap pengaduan,
     * sedangkan kartu keempat memakai telaah jawaban yang benar-benar
     * sudah masuk, bukan pengaduan yang sudah ditutup.
     *
     * @return array<int, array<string, mixed>>
     */
    public function sinkronisasiUnit(): array
    {
        return [
            $this->kartuSinkronisasi(
                eyebrow: 'Tindakan Diperlukan',
                nada: 'text-error',
                judul: StatusPengaduan::Diterima->subStatus(),
                jumlah: $this->jumlah(StatusPengaduan::Diterima),
                ikon: 'mark_email_unread',
                warnaIkon: 'bg-error-container text-on-error-container',
                warnaAngka: 'text-error',
                catatan: 'Reminder otomatis aktif (H-1, H-3, H-5)',
                ikonCatatan: 'notifications_active',
                aksi: ['label' => 'Kirim Bell Ping (Manual)', 'ikon' => 'campaign'],
            ),
            $this->kartuSinkronisasi(
                eyebrow: 'Proses Medis/Layanan',
                nada: 'text-secondary',
                judul: StatusPengaduan::Diproses->subStatus(),
                jumlah: $this->jumlah(StatusPengaduan::Diproses),
                ikon: 'biotech',
                warnaIkon: 'bg-surface-container text-secondary',
                warnaAngka: 'text-on-surface',
                catatan: 'Limit '.$this->hariInvestigasi.' hari kerja',
            ),
            $this->kartuSinkronisasi(
                eyebrow: 'Butuh Putusan Humas',
                nada: 'text-tertiary-fixed-dim',
                judul: StatusPengaduan::Revisi->subStatus(),
                jumlah: $this->jumlah(StatusPengaduan::Revisi),
                ikon: 'contact_support',
                warnaIkon: 'bg-tertiary-container text-tertiary-fixed',
                warnaAngka: 'text-on-tertiary-container',
                catatan: 'Tanya Pasien',
                nadaCatatan: 'bg-tertiary-container/30 text-on-tertiary-container',
            ),
            $this->kartuSinkronisasi(
                eyebrow: 'Siap Dirumuskan',
                nada: 'text-secondary',
                judul: StatusPengaduan::Selesai->subStatus(),
                jumlah: $this->telaah(),
                ikon: 'rate_review',
                warnaIkon: 'bg-secondary-container text-on-secondary-container',
                warnaAngka: 'text-secondary',
                catatan: 'Menunggu Racikan Humas',
                nadaCatatan: 'bg-secondary-container text-on-secondary-container font-bold',
            ),
        ];
    }

    /**
     * Matriks waktu investigasi unit untuk stepper sidebar.
     *
     * @return array<int, array<string, string|bool>>
     */
    public function siklus(): array
    {
        $nada = [
            'selesai' => [
                'titik' => 'bg-secondary text-on-secondary',
                'judul' => 'text-secondary font-bold',
                'badge' => 'px-space-xs py-0.5 rounded-xs bg-secondary-container text-on-secondary-container font-bold',
                'ket' => 'text-on-surface-variant font-normal',
            ],
            'peringatan' => [
                'titik' => 'bg-surface-container-high text-on-surface-variant',
                'judul' => 'text-on-surface font-semibold',
                'badge' => 'text-on-surface-variant font-normal',
                'ket' => 'text-on-surface-variant font-normal',
            ],
            'batas' => [
                'titik' => 'bg-primary-container text-on-primary ring-4 ring-secondary-container/60 animate-pulse',
                'judul' => 'text-primary-container font-bold',
                'badge' => 'px-space-xs py-0.5 rounded-xs bg-primary-fixed text-primary-container font-bold',
                'ket' => 'text-on-surface font-medium',
            ],
            'eskalasi' => [
                'titik' => 'bg-error text-on-error ring-4 ring-error/20 animate-pulse',
                'judul' => 'text-error font-bold',
                'badge' => 'px-space-xs py-0.5 rounded-xs bg-error-container text-on-error-container font-bold',
                'ket' => 'text-on-surface-variant font-normal',
            ],
        ];

        return collect(config('pengaduan.siklus_investigasi', []))
            ->map(fn (array $pillar): array => [
                'judul' => $pillar['hari'].': '.$pillar['judul'],
                'badge' => $pillar['badge'],
                'ket' => $pillar['ket'],
                'ikon' => $pillar['ikon'],
                'kelas' => $nada[$pillar['nada']] ?? $nada['peringatan'],
            ])
            ->all();
    }

    /** Daftar unit beserta status koneksi SIMRS-nya untuk panel sidebar. */
    public function unitKoneksi(): array
    {
        return $this->statusKoneksi
            ->map(fn (MasterUnit $unit): array => [
                'nama' => $unit->namaLengkap(),
                'berkas' => $unit->beban_aktif,
                'aktif' => $unit->koneksiAktif(),
                'ringkas' => $unit->koneksiAktif()
                    ? 'Terhubung SIMRS Core'
                    : 'Koneksi SIMRS belum aktif',
            ])
            ->all();
    }

    /**
     * Susunan satu kartu tiket pada daftar pengaduan butuh tindakan segera.
     *
     * @return array<string, mixed>
     */
    public function kartuTiket(Pengaduan $pengaduan): array
    {
        $lewat = Sla::lewatInvestigasi($pengaduan->created_at);
        $sudahDibalas = (bool) $pengaduan->sudah_dibalas;
        $hariKe = Sla::hariKerjaLewat($pengaduan->created_at) + 1;
        $sisa = $pengaduan->sisaHariSla();

        return [
            'kode' => $pengaduan->kode_tiket,
            'subjek' => $pengaduan->subjek,
            'pelapor' => $pengaduan->nama_lengkap,
            'nrm' => $pengaduan->normalisasiNrm($pengaduan->nrm),
            'unit' => $pengaduan->namaUnit(),
            'lewat' => $lewat,
            'kartu' => match (true) {
                $lewat => 'bg-error-container/20',
                $pengaduan->status === StatusPengaduan::Diterima => 'bg-surface-container-lowest border border-secondary/30 ring-1 ring-secondary/20',
                default => 'bg-surface-container-low',
            },
            'status' => $pengaduan->status->label(),
            'nadaStatus' => match (true) {
                $pengaduan->status === StatusPengaduan::Revisi => 'bg-error text-on-error',
                $pengaduan->status === StatusPengaduan::Diterima => 'bg-surface-container-highest text-on-surface',
                default => 'bg-secondary-container text-on-secondary-container',
            },
            'tahap' => $this->tahapTiket($lewat, $sudahDibalas, $pengaduan),
            'sla' => $this->barisSla($lewat, $hariKe, $sisa),
            'catatan' => $this->catatanTiket($lewat, $sudahDibalas, $pengaduan),
            'tombol' => $this->tombolTiket($lewat, $sudahDibalas, $pengaduan),
        ];
    }

    /** Kalimat ringkasan pada footer daftar pengaduan butuh tindakan. */
    public function ringkasTindakan(): string
    {
        return 'Menampilkan '.count($this->perluTindakan)
            .' dari '.$this->aktif.' pengaduan butuh atensi aktif';
    }

    /**
     * Badge tahap pengerjaan pada kartu tiket.
     *
     * @return array{label: string, ikon: string, nada: string}
     */
    private function tahapTiket(bool $lewat, bool $sudahDibalas, Pengaduan $pengaduan): array
    {
        if ($sudahDibalas) {
            return [
                'label' => 'Jawaban Unit Masuk',
                'ikon' => 'task',
                'nada' => 'bg-secondary text-on-secondary',
            ];
        }

        if ($pengaduan->status === StatusPengaduan::Diterima) {
            return [
                'label' => 'Menunggu Triase',
                'ikon' => 'pending_actions',
                'nada' => 'bg-secondary-container text-on-secondary-container',
            ];
        }

        return [
            'label' => 'Belum Dibuka ('.$pengaduan->created_at->diffInHours(now()).' jam)',
            'ikon' => 'visibility_off',
            'nada' => 'bg-error text-on-error',
        ];
    }

    /**
     * Baris posisi hari kerja pada kartu tiket.
     *
     * @return array{label: string, ikon: string, nada: string}
     */
    private function barisSla(bool $lewat, int $hariKe, int $sisa): array
    {
        if ($lewat) {
            return [
                'label' => 'Hari ke-'.$hariKe.' (LEWAT BATAS)',
                'ikon' => 'warning',
                'nada' => 'text-error',
            ];
        }

        if ($sisa > 0) {
            return [
                'label' => 'Hari ke-'.$hariKe.', sisa '.$sisa.' hari kerja',
                'ikon' => 'timelapse',
                'nada' => 'text-secondary',
            ];
        }

        return [
            'label' => 'Hari ke-'.$hariKe.' (SLA '.$this->hariKerja.' Hari Kerja)',
            'ikon' => 'schedule',
            'nada' => 'text-secondary',
        ];
    }

    /**
     * Uraian singkat kondisi tiket pada kartu.
     *
     * @return array{label: string, nada: string}
     */
    private function catatanTiket(bool $lewat, bool $sudahDibalas, Pengaduan $pengaduan): array
    {
        if ($lewat) {
            return [
                'label' => 'Investigasi internal unit melampaui limit '.$this->hariInvestigasi.' hari kerja',
                'nada' => 'text-error font-medium',
            ];
        }

        if ($sudahDibalas) {
            return [
                'label' => 'Draf klarifikasi telaah sudah masuk dan siap diracik humas',
                'nada' => 'text-secondary font-medium',
            ];
        }

        if ($pengaduan->status === StatusPengaduan::Diterima) {
            return [
                'label' => 'Tiket baru masuk melalui portal, belum ditentukan penanganan',
                'nada' => 'text-on-surface-variant',
            ];
        }

        return [
            'label' => 'Disposisi terkirim ke unit, menunggu dibuka',
            'nada' => 'text-error font-medium',
        ];
    }

    /**
     * Tombol aksi yang sesuai dengan kondisi tiket.
     *
     * Tombol sengaja belum diarahkan ke halaman lain karena tiap fitur
     * pada konsol admin akan punya desain dan alurnya sendiri.
     *
     * @return array<int, array{label: string, ikon: string, nada: string}>
     */
    private function tombolTiket(bool $lewat, bool $sudahDibalas, Pengaduan $pengaduan): array
    {
        $nudge = [
            'label' => $lewat ? 'Follow Up Unit (Nudge)' : 'Follow Up Unit (Nudge Manual)',
            'ikon' => 'notifications_active',
            'nada' => 'px-space-md py-2 rounded-sm bg-surface-container-high text-on-surface font-semibold',
        ];

        $tinjau = [
            'label' => 'Tinjau Jawaban & Racik Balasan',
            'ikon' => 'rate_review',
            'nada' => 'px-space-md py-2 rounded-sm bg-secondary text-on-secondary font-bold shadow-sm',
        ];

        if ($sudahDibalas) {
            return $lewat
                ? [$this->tombolEskalasi(), $tinjau]
                : [$tinjau];
        }

        if ($pengaduan->status === StatusPengaduan::Diterima) {
            return [[
                'label' => 'Tentukan Penanganan',
                'ikon' => 'forward_to_inbox',
                'nada' => 'px-space-md py-2 rounded-sm bg-secondary text-on-secondary font-bold shadow-sm ring-2 ring-secondary/20',
            ]];
        }

        return $lewat
            ? [$this->tombolEskalasi(), $nudge]
            : [$nudge];
    }

    /** @return array{label: string, ikon: string, nada: string} */
    private function tombolEskalasi(): array
    {
        return [
            'label' => 'Eskalasi Segera',
            'ikon' => 'crisis_alert',
            'nada' => 'px-space-md py-2 rounded-sm bg-error text-on-error font-bold shadow-sm',
        ];
    }

    /**
     * @param  array{label?: string, ikon?: string, nada?: string}|null  $aksi
     * @return array<string, mixed>
     */
    private function kartuSinkronisasi(
        string $eyebrow,
        string $nada,
        string $judul,
        int $jumlah,
        string $ikon,
        string $warnaIkon,
        string $warnaAngka,
        ?string $catatan = null,
        ?string $ikonCatatan = null,
        ?string $nadaCatatan = null,
        ?array $aksi = null,
    ): array {
        return [
            'eyebrow' => $eyebrow,
            'nada' => $nada,
            'judul' => $judul,
            'jumlah' => $jumlah,
            'ikon' => $ikon,
            'warnaIkon' => $warnaIkon,
            'warnaAngka' => $warnaAngka,
            'catatan' => $catatan,
            'ikonCatatan' => $ikonCatatan,
            'nadaCatatan' => $nadaCatatan,
            'aksinya' => $aksi,
        ];
    }

    /** Daftar kode tiket yang sudah melewati batas, dipotong agar ringkas. */
    public function kodeTerlambat(): string
    {
        $kode = $this->kritis['lewat']->pluck('kode')->all();

        return Str::limit(implode(', ', $kode), 48);
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
