<?php

namespace App\Support;

use App\Enums\StatusInvestigasi;
use App\Enums\StatusPengaduan;
use App\Models\Pengaduan;
use Illuminate\Database\Eloquent\Builder;

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
    /**
     * Jumlah tiket yang tampil langsung pada tiap kolom kanban.
     *
     * Sisanya tetap dikirim ke view, tapi disembunyikan sampai petugas
     * menekan tautan "tiket lainnya" pada kolom itu, sehingga papan tidak
     * membanjir kartu sekaligus namun tidak ada tiket yang benar-benar
     * hilang dari pandangan.
     */
    private const KARTU_PER_KOLOM = 4;

    /**
     * @param  array<int, array<string, mixed>>  $papan  Kolom kanban status disposisi unit.
     */
    public function __construct(
        public readonly array $papan,
        public readonly int $ditugaskan,
        public readonly int $hariInvestigasi,
    ) {}

    /**
     * Susun papan monitor.
     *
     * Halaman ini tidak punya filter query sendiri. Semua yang tampil
     * ditentukan oleh status tiket, jadi tidak ada parameter URL yang
     * perlu dibaca.
     */
    public static function dariRequest(): self
    {
        return new self(
            papan: self::papan(),
            // Angka ini hanya menghitung tiket aktif yang sudah ditautkan ke
            // unit, karena papan kanban ini tidak memuat tiket yang masih
            // di tangan humas dan belum punya unit tujuan.
            ditugaskan: Pengaduan::query()->aktif()->whereNotNull('master_unit_id')->count(),
            hariInvestigasi: Sla::hariInvestigasi(),
        );
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

                return [
                    ...$data,
                    'total' => $daftar->count(),
                    'sisa' => max(0, $daftar->count() - self::KARTU_PER_KOLOM),
                    'batas' => self::KARTU_PER_KOLOM,
                    'tiket' => $daftar
                        ->map(fn (Pengaduan $pengaduan): array => self::kartuTiket($pengaduan))
                        ->all(),
                ];
            })
            ->all();
    }

    /**
     * Satu kartu tiket di dalam kolom kanban.
     *
     * Kedua tombol di sini menuju halaman yang benar-benar ada. Tautan
     * "Lihat Tiket Unit" membuka daftar pengaduan yang sudah tersaring ke
     * unit tiket ini, jadi admin bisa melihat seluruh beban unit tanpa
     * berpindah-pindah halaman detail.
     *
     * @return array<string, mixed>
     */
    private static function kartuTiket(Pengaduan $pengaduan): array
    {
        $sudahDibalas = (bool) $pengaduan->sudah_dibalas;
        $lewat = Sla::lewatInvestigasi($pengaduan->created_at);
        $unit = $pengaduan->masterUnit;

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
                    'label' => 'Lihat Tiket Unit',
                    'ikon' => 'list_alt',
                    'nada' => 'bg-surface-container-high text-on-surface-variant',
                    'url' => $unit === null
                        ? null
                        : route('admin.pengaduan.index', ['unit' => $unit->kode]),
                ],
            ],
        ];
    }
}
