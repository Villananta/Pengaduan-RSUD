<?php

namespace App\Support;

use App\Enums\StatusInvestigasi;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Data tampilan untuk halaman Monitor Disposisi & SLA Unit.
 *
 * Halaman ini melihat satu sisi dari proses pengaduan, yaitu sisi unit:
 * apakah sebuah tiket yang sudah ditugaskan ke instalasi masih belum
 * dibuka, sedang ditelusuri, sudah dijawab dan tinggal diracik, atau
 * sudah ditutup. Karena itu semua query di sini dibatasi ke pengaduan
 * yang benar-benar tertaut ke MASTER_UNITS, sedangkan pengaduan yang
 * ditangani humas langsung tidak pernah ikut dihitung.
 *
 * Angka batas hari kerja tidak ditulis ulang di kelas ini. Semuanya
 * diambil dari App\Support\Sla dan config pengaduan supaya halaman ini
 * tidak menjadi sumber ketiga yang bisa berbeda dari beranda dan daftar.
 *
 * Kolom "Belum Dibuka Unit" sengaja dipisah dari "Sedang Investigasi"
 * meski keduanya berasal dari status investigasi yang sama. Enum
 * StatusInvestigasi tidak ditambah casenya supaya tab Lapis 2 pada
 * daftar pengaduan tidak ikut berubah bentuknya.
 */
final class MonitorDisposisi
{
    /** Pilihan saring tabel eskalasi, urut dari yang paling umum. */
    private const SARING = ['semua', 'lewat', 'mendek'];

    /** Jumlah tiket yang ditampilkan pada tiap kolom kanban. */
    private const KARTU_PER_KOLOM = 2;

    /** Jumlah baris tabel eskalasi sebelum dipangkas. */
    private const BATAS_ESKALASI = 10;

    /**
     * @param  array<int, array<string, mixed>>  $kartu  Empat kartu angka di bawah banner.
     * @param  array<int, array<string, mixed>>  $papan  Kolom kanban status disposisi unit.
     * @param  array{baris: array<int, array<string, mixed>>, jumlah: array<string, int>, saring: string}  $eskalasi
     */
    public function __construct(
        public readonly array $kartu,
        public readonly array $papan,
        public readonly array $eskalasi,
        public readonly int $aktif,
        public readonly int $ditugaskan,
        public readonly int $telaah,
        public readonly int $unitTerhubung,
        public readonly int $unitTotal,
        public readonly int $hariKerja,
        public readonly int $hariInvestigasi,
        public readonly int $standarKepatuhan,
    ) {}

    /**
     * Susun halaman monitor dari parameter query.
     *
     * Saring yang tidak dikenal jatuh ke "semua" supaya tautan lama atau
     * crafted tidak membuat tabel kosong tanpa penjelasan.
     */
    public static function dariRequest(Request $request): self
    {
        $diminta = (string) $request->query('saring', 'semua');
        $saring = in_array($diminta, self::SARING, true) ? $diminta : 'semua';

        $kritis = StatistikDashboard::ringkasanKritis();
        $aktif = StatistikDashboard::aktif();
        $eskalasi = self::eskalasi($saring);

        return new self(
            kartu: self::kartu($kritis, $aktif),
            papan: self::papan(),
            eskalasi: $eskalasi,
            aktif: $aktif,
            // Angka ini hanya menghitung tiket aktif yang sudah ditautkan ke
            // unit, karena papan kanban ini tidak memuat tiket yang masih
            // di tangan humas dan belum punya unit tujuan.
            ditugaskan: (int) ($eskalasi['jumlah']['semua'] ?? 0),
            telaah: (int) ($kritis['telaah'] ?? 0),
            unitTerhubung: StatistikDashboard::unitTerhubung(),
            unitTotal: MasterUnit::query()->count(),
            hariKerja: Sla::hariKerja(),
            hariInvestigasi: Sla::hariInvestigasi(),
            standarKepatuhan: (int) config('pengaduan.kepatuhan_standar_persen', 90),
        );
    }

    /** Pilihan saring beserta jumlah tiket yang dipilihnya. */
    public function pilihanSaring(): array
    {
        $jumlah = $this->eskalasi['jumlah'];

        return [
            'semua' => 'Semua Tiket Ditugaskan ('.$jumlah['semua'].')',
            'lewat' => 'Lewat Batas '.$this->hariInvestigasi.' Hari Kerja ('.$jumlah['lewat'].')',
            'mendek' => 'Mendekati Batas ('.$jumlah['mendek'].')',
        ];
    }

    /** URL tabel eskalasi dengan satu pilihan saring. */
    public function urlSaring(string $nilai): string
    {
        return $nilai === 'semua'
            ? route('admin.monitor.index')
            : route('admin.monitor.index', ['saring' => $nilai]);
    }

    /** Total tiket yang cocok dengan saring aktif, sebelum dipangkas. */
    public function totalEskalasi(): int
    {
        return $this->eskalasi['jumlah'][$this->eskalasi['saring']];
    }

    /**
     * Empat kartu angka di atas papan kanban.
     *
     * Angka diambil dari StatistikDashboard supaya kartu ini sama dengan
     * yang terlihat di beranda. Nama unit yang disebut pada kartu tiket lewat
     * batas diambil dari daftar tiket terlambat supaya kartu itu langsung
     * menunjuk unit yang perlu ditegur, bukan hanya angkanya.
     *
     * @param  array<string, mixed>  $kritis  Hasil StatistikDashboard::ringkasanKritis().
     * @param  int  $aktif  Jumlah pengaduan yang masih berjalan, untuk menghitung porsi tiap kartu.
     * @return array<int, array<string, mixed>>
     */
    private static function kartu(array $kritis, int $aktif): array
    {
        $kepatuhan = StatistikDashboard::kepatuhanSlaPersen();
        $standar = (int) config('pengaduan.kepatuhan_standar_persen', 90);
        $rataRata = StatistikDashboard::rataRataHariKerja();
        $terlambat = $kritis['total_lewat'];

        $unitBermasalah = collect($kritis['lewat'])
            ->pluck('unit')
            ->unique()
            ->take(2)
            ->all();

        return [
            [
                'label' => 'Kepatuhan SLA Pengaduan',
                'ikon' => 'verified',
                'nilai' => $kepatuhan,
                'satuan' => '%',
                'desimal' => 1,
                'persen' => min(100, $kepatuhan ?? 0),
                'nadaAngka' => $kepatuhan === null || $kepatuhan < $standar ? 'text-error' : 'text-on-surface',
                'nadaIkon' => 'bg-surface-container-low text-secondary',
                'nadaBar' => $kepatuhan === null || $kepatuhan < $standar ? 'bg-error' : 'bg-secondary',
                'sisi' => match (true) {
                    $kepatuhan === null => 'Belum ada pengaduan selesai',
                    $kepatuhan >= $standar => 'Memenuhi ambang Kemenkes',
                    default => 'Di bawah ambang Kemenkes',
                },
                'ket' => 'Ambang Kemenkes &ge; '.$standar.'%, dihitung dari pengaduan yang sudah selesai.',
            ],
            [
                'label' => 'Tiket Lewat Batas Investigasi',
                'ikon' => 'crisis_alert',
                'nilai' => $terlambat,
                'satuan' => 'Tiket',
                'desimal' => 0,
                'persen' => $aktif > 0 ? (int) round($terlambat / $aktif * 100) : 0,
                'nadaAngka' => 'text-error',
                'nadaIkon' => 'bg-error-container text-on-error-container',
                'nadaBar' => 'bg-error',
                'sisi' => $terlambat > 0 ? 'Perlu eskalasi ke pimpinan' : 'Semua unit masih di bawah batas',
                'ket' => $unitBermasalah === []
                    ? 'Batas investigasi internal unit '.Sla::hariInvestigasi().' hari kerja.'
                    : 'Unit bermasalah: '.implode(' dan ', $unitBermasalah).'.',
            ],
            [
                'label' => 'Menunggu Racikan Humas',
                'ikon' => 'rate_review',
                'nilai' => $kritis['telaah'],
                'satuan' => 'Tiket',
                'desimal' => 0,
                'persen' => $aktif > 0 ? (int) round($kritis['telaah'] / $aktif * 100) : 0,
                'nadaAngka' => 'text-on-surface',
                'nadaIkon' => 'bg-tertiary-fixed text-on-tertiary-fixed',
                'nadaBar' => 'bg-tertiary-fixed-dim',
                'sisi' => 'Telaah unit sudah masuk',
                'ket' => 'Jawaban unit sudah diterima, tinggal diracik menjadi jawaban resmi.',
            ],
            [
                'label' => 'Rata-rata Penyelesaian',
                'ikon' => 'speed',
                'nilai' => $rataRata,
                'satuan' => 'Hari Kerja',
                'desimal' => 1,
                'persen' => $rataRata === null || Sla::hariKerja() === 0
                    ? 0
                    : (int) round($rataRata / Sla::hariKerja() * 100),
                'nadaAngka' => 'text-on-surface',
                'nadaIkon' => 'bg-surface-container-low text-primary-container',
                'nadaBar' => 'bg-secondary',
                'sisi' => $rataRata === null
                    ? 'Belum ada pengaduan selesai'
                    : 'Batas SLA '.Sla::hariKerja().' hari kerja',
                'ket' => 'Dihitung dari pengaduan selesai, kasus berat memakai targetnya sendiri.',
            ],
        ];
    }

    /**
     * Empat kolom kanban status disposisi unit.
     *
     * Keempat kolom harus saling lepas dan mencakup semua tiket yang sudah
     * ditugaskan. Kalau tidak, satu tiket bisa muncul di dua kolom atau hilang
     * dari papan, sehingga angka di monitor dan di halaman lain tidak sama.
     * Pembagiannya berurutan dari lifecycle tiket:
     *
     * 1. Sudah selesai, apa pun isi pesannya, masuk Jawaban di Humas. Tiket
     *    bisa ditutup tanpa balasan apa pun karena tombol Selesaikan memang
     *    tidak memaksa pesan.
     * 2. Masih Diterima dan belum ada balasan, masuk Belum Dibuka Unit.
     * 3. Sudah ada balasan unit, masuk Menunggu Racikan. Syarat ini yang
     *    mencegah tiket Diterima muncul di kolom pertama sekaligus ketiga.
     * 4. Sisanya sudah ditelusuri unit tapi belum menjawab, masuk Sedang
     *    Investigasi.
     *
     * WithExists di bawah tetap dipakai untuk menandai kartu mana yang sudah
     * dibaca PIC, bukan untuk menghitung kolom.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function papan(): array
    {
        $dasar = fn (): Builder => Pengaduan::query()
            ->whereNotNull('master_unit_id')
            ->with('masterUnit')
            ->withExists(['pesan as sudah_dibalas' => fn (Builder $q): Builder => $q->jawaban()]);

        $sudahDibalas = fn (Builder $q): Builder => $q->whereHas(
            'pesan',
            fn (Builder $p): Builder => $p->jawaban()
        );
        $belumDibalas = fn (Builder $q): Builder => $q->whereDoesntHave(
            'pesan',
            fn (Builder $p): Builder => $p->jawaban()
        );

        $kolom = [
            [
                'kunci' => 'belum_dibuka',
                'judul' => 'Belum Dibuka Unit',
                'ringkas' => 'Disposisi sudah turun, unit belum membuka tiket.',
                'ikon' => 'visibility_off',
                'titik' => 'bg-error',
                'latar' => 'bg-error-container/40',
                'nadaJudul' => 'text-on-error-container',
                'nadaAngka' => 'bg-error text-on-error',
                'syarat' => fn (): Builder => $belumDibalas(
                    $dasar()->aktif()->where('status', StatusPengaduan::Diterima->value)
                ),
            ],
            [
                'kunci' => 'sedang_investigasi',
                'judul' => 'Sedang Investigasi',
                'ringkas' => 'Unit sedang menelusuri kronologi dan berkas.',
                'ikon' => 'biotech',
                'titik' => 'bg-secondary',
                'latar' => 'bg-secondary-container/40',
                'nadaJudul' => 'text-on-secondary-container',
                'nadaAngka' => 'bg-secondary text-on-secondary',
                'syarat' => fn (): Builder => $belumDibalas(
                    $dasar()->aktif()->whereIn('status', [
                        StatusPengaduan::Diproses->value,
                        StatusPengaduan::Revisi->value,
                    ])
                ),
            ],
            [
                'kunci' => 'menunggu_racikan',
                'judul' => 'Menunggu Racikan',
                'ringkas' => 'Unit sudah menjawab, humas tinggal menyusun jawaban resmi.',
                'ikon' => 'edit_note',
                'titik' => 'bg-tertiary-fixed-dim',
                'latar' => 'bg-tertiary-container/30',
                'nadaJudul' => 'text-tertiary',
                'nadaAngka' => 'bg-tertiary-fixed-dim text-on-tertiary-fixed',
                'syarat' => fn (): Builder => $sudahDibalas($dasar()->aktif()),
            ],
            [
                'kunci' => 'jawaban_unit',
                'judul' => 'Jawaban di Humas',
                'ringkas' => 'Arsip unit tersimpan, tiket ditutup dengan jawaban resmi.',
                'ikon' => 'task_alt',
                'titik' => 'bg-primary-container',
                'latar' => 'bg-surface-container-high',
                'nadaJudul' => 'text-on-surface',
                'nadaAngka' => 'bg-primary-container text-on-primary',
                'syarat' => fn (): Builder => $dasar()->selesai(),
            ],
        ];

        return collect($kolom)
            ->map(function (array $data) {
                $daftar = ($data['syarat'])()
                    ->orderBy('created_at')
                    ->get();

                unset($data['syarat']);

                $sisa = $daftar->count() - self::KARTU_PER_KOLOM;

                return [
                    ...$data,
                    'total' => $daftar->count(),
                    'sisa' => max(0, $sisa),
                    'tiket' => $daftar
                        ->take(self::KARTU_PER_KOLOM)
                        ->map(fn (Pengaduan $pengaduan): array => self::kartuTiket($pengaduan))
                        ->all(),
                ];
            })
            ->all();
    }

    /**
     * Satu kartu tiket di dalam kolom kanban.
     *
     * @return array<string, mixed>
     */
    private static function kartuTiket(Pengaduan $pengaduan): array
    {
        $sudahDibalas = (bool) $pengaduan->sudah_dibalas;
        $lewat = Sla::lewatInvestigasi($pengaduan->created_at);

        return [
            'kode' => $pengaduan->kode_tiket,
            'unit' => $pengaduan->namaUnit(),
            'pelapor' => $pengaduan->nama_lengkap,
            'subjek' => $pengaduan->subjek,
            'kasus_berat' => (bool) $pengaduan->kasus_berat,
            'hari' => Sla::hariKe($pengaduan->created_at),
            'posisi' => $lewat
                ? 'Hari ke-'.Sla::hariKe($pengaduan->created_at).' Lewat Batas Unit'
                : 'Hari ke-'.Sla::hariKe($pengaduan->created_at).' dari '.Sla::hariKerja().' hari kerja',
            'nadaPosisi' => $lewat ? 'bg-error-container text-on-error-container' : 'bg-surface-container text-on-surface',
            'nadaGaris' => $lewat ? 'bg-error' : 'bg-outline-variant',
            'catatan' => StatusInvestigasi::dariPengaduan($pengaduan, $sudahDibalas)->ringkas(),
            'tombol' => [
                [
                    'label' => 'Buka Detail',
                    'ikon' => 'visibility',
                    'nada' => 'bg-primary-container text-on-primary',
                    'url' => route('admin.pengaduan.show', $pengaduan->kode_tiket),
                ],
                [
                    // Nudge unit belum jadi endpoint mana pun. Menampilkan
                    // tombol yang menggoda tapi tidak pernah menghubungi
                    // siapa pun membuat admin percaya tiket sudah dikejar,
                    // jadi tombolnya sengaja dimatikan.
                    'label' => 'Nudge Unit',
                    'ikon' => 'notifications_active',
                    'nada' => 'bg-surface-container-high text-on-surface-variant',
                    'url' => null,
                ],
            ],
        ];
    }

    /**
     * Baris tabel eskalasi beserta jumlah tiap pilihan saring.
     *
     * Zona SLA dihitung dengan diffInWeekdays yang tidak bisa diterjemahkan
     * ke SQL, jadi penyaringan dan pengurutan dikerjakan di dalam memori
     * seperti yang dilakukan StatistikDashboard::perluTindakan.
     *
     * @return array{baris: array<int, array<string, mixed>>, jumlah: array<string, int>, saring: string}
     */
    private static function eskalasi(string $saring): array
    {
        $terpantau = Pengaduan::query()
            ->aktif()
            ->whereNotNull('master_unit_id')
            ->with('masterUnit')
            ->get();

        $lewat = $terpantau->filter(fn (Pengaduan $p): bool => Sla::lewatInvestigasi($p->created_at));
        $mendek = $terpantau->filter(fn (Pengaduan $p): bool => self::dekatBatas($p));

        $jumlah = [
            'semua' => $terpantau->count(),
            'lewat' => $lewat->count(),
            'mendek' => $mendek->count(),
        ];

        $baris = $terpantau
            ->filter(fn (Pengaduan $p): bool => match ($saring) {
                'lewat' => Sla::lewatInvestigasi($p->created_at),
                'mendek' => self::dekatBatas($p),
                default => true,
            })
            // Yang lewat batas didahulukan, lalu yang paling mepet.
            ->sortByDesc(fn (Pengaduan $p): bool => Sla::lewatInvestigasi($p->created_at))
            ->sortByDesc(fn (Pengaduan $p): int => Sla::hariKerjaLewat($p->created_at))
            ->take(self::BATAS_ESKALASI)
            ->values();

        return [
            'baris' => $baris
                ->map(fn (Pengaduan $pengaduan): array => self::barisEskalasi($pengaduan))
                ->all(),
            'jumlah' => $jumlah,
            'saring' => $saring,
        ];
    }

    /**
     * Satu baris tabel eskalasi.
     *
     * @return array<string, mixed>
     */
    private static function barisEskalasi(Pengaduan $pengaduan): array
    {
        $lewat = Sla::lewatInvestigasi($pengaduan->created_at);
        $hariKe = Sla::hariKe($pengaduan->created_at);
        $sisa = $pengaduan->sisaHariSla();

        return [
            'kode' => $pengaduan->kode_tiket,
            'pelapor' => $pengaduan->nama_lengkap,
            'unit' => $pengaduan->namaUnit(),
            'kategori' => $pengaduan->kategori->label(),
            'lewat' => $lewat,
            'kasus_berat' => (bool) $pengaduan->kasus_berat,
            'hari' => $hariKe,
            'sisa' => $sisa,
            'persen' => min(100, (int) round(
                Sla::hariKerjaLewat($pengaduan->created_at) / Sla::hariInvestigasi() * 100
            )),
            'nadaAngka' => $lewat ? 'text-error' : 'text-secondary',
            'nadaLencana' => $pengaduan->kasus_berat
                ? 'bg-primary-fixed text-primary-container'
                : ($lewat ? 'bg-error text-on-error' : 'bg-secondary-container text-on-secondary-container'),
            'lencana' => $pengaduan->kasus_berat
                ? 'Kasus Berat (+'.Sla::tambahanKasusBerat().' Hari Kerja)'
                : ($lewat ? 'Lewat Batas Unit' : 'Sisa '.$sisa.' Hari Kerja'),
            'subjek' => $pengaduan->subjek,
            'kendala' => $lewat
                ? 'Belum ada progres sejak disposisi diteruskan, sudah melewati batas '
                    .Sla::hariInvestigasi().' hari kerja investigasi internal.'
                : 'Menunggu telaah unit, masih aman karena '.Sla::hariInvestigasi().' hari kerja belum habis.',
            'tombol' => [
                [
                    'label' => 'Buka Detail',
                    'ikon' => 'open_in_new',
                    'nada' => 'bg-primary-container text-on-primary',
                    'url' => route('admin.pengaduan.show', $pengaduan->kode_tiket),
                ],
                [
                    // Eskalasi ke Wadir dan Komite Medis adalah tindakan ke
                    // atasan yang harus punya catatan dan notifikasi, sedangkan
                    // di aplikasi ini belum ada salah satu pun. Karena itu
                    // tombolnya ditampilkan sebagai teguran nonaktif, bukan
                    // tautan yang tidak mengerjakan apa pun.
                    'label' => $lewat ? 'Eskalasi Wadir' : 'Teguran Unit',
                    'ikon' => $lewat ? 'outgoing_mail' : 'campaign',
                    'nada' => 'bg-surface-container-high text-on-surface-variant',
                    'url' => null,
                ],
            ],
        ];
    }

    /** Margin peringatan SLA dalam hari kerja. */
    private static function peringatan(): int
    {
        return (int) config('pengaduan.sla.peringatan_hari_kerja', 2);
    }

    /**
     * True bila tiket masih di dalam batas investigasi tetapi sudah mepet.
     *
     * Yang dihitung di sini adalah siklus investigasi, bukan SLA penyelesaian
     * akhir. Memakai sisaHariSla() akan membuat tiket berumur tiga hari lolos
     * sebagai "mendek" padahal batas investigasinya lima hari kerja, sehingga
     * tabel eskalasi gagal memperingatkan unit yang sudah harus dugung.
     * Tiket yang sudah lewat batasnya dipisahkan oleh lewatInvestigasi
     * supaya tidak terhitung dua kali.
     */
    private static function dekatBatas(Pengaduan $pengaduan): bool
    {
        if (Sla::lewatInvestigasi($pengaduan->created_at)) {
            return false;
        }

        $sisa = Sla::hariInvestigasi() - Sla::hariKerjaLewat($pengaduan->created_at);

        return $sisa <= self::peringatan();
    }
}
