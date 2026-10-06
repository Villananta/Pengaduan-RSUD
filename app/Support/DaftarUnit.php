<?php

namespace App\Support;

use App\Enums\KategoriUnit;
use App\Enums\PeranAksesUnit;
use App\Enums\StatusAksesUnit;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Data tampilan untuk halaman Master Data Unit & Instalasi.
 *
 * Halaman ini adalah sisi(unit) dari data pengaduan: siapa yang bisa
 * dihubungi ketika sebuah tiket selesai ditugaskan. Karena itu semua angka
 * unit di sini diambil dari MASTER_UNITS dan pengaduan yang benar-benar
 * tertaut ke unit tersebut, bukan angka rekaan yang diketik di view.
 *
 * Angka batas hari kerja tidak ditulis ulang di kelas ini. Semuanya diambil
 * dari App\Support\Sla supaya halaman ini tidak menjadi sumber ketiga yang
 * bisa berbeda dari beranda, daftar pengaduan, dan monitor disposisi.
 *
 * Angka pada kartu ringkasan selalu menggambarkan seluruh unit, tidak
 * terpengaruh saring tabel. Saring hanya mempersempit baris yang terlihat,
 * karena kartu dipakai sebagai gambaran kondisi keseluruhan, bukan sebagai
 * hasil pencarian.
 */
final class DaftarUnit
{
    /** Jumlah baris per halaman pada tabel unit. */
    private const PER_HALAMAN = 10;

    /** Urutan tabel yang boleh dipilih, urut dari yang paling sering dipakai. */
    private const URUTAN = ['beban', 'nama', 'kategori', 'kode'];

    /** Nilai standing sebagai ganti filter koneksi yang tidak berlaku. */
    private const KONEKSI = ['semua', 'terhubung', 'terputus', 'belum_ditugaskan'];

    /** Nilai standing untuk saring beban antrean unit. */
    private const BEBAN = ['semua', 'ada', 'kosong'];

    /**
     * @param  LengthAwarePaginator<int, MasterUnit>  $unit  Baris tabel yang sudah disaring.
     * @param  array<int, array<string, mixed>>  $kartu  Empat kartu ringkasan di atas tabel.
     * @param  array<int, array<string, mixed>>  $distribusi  Sebaran unit per kategori layanan.
     * @param  array<int, float>  $kepatuhan  Persentase SLA per unit, indeks master_unit_id.
     * @param  array<int, int>  $lewat  Tiket yang lewat batas investigasi, indeks master_unit_id.
     * @param  array<string, string|null>  $saring  Nilai saring aktif per kunci.
     * @param  int  $totalSemua  Jumlah seluruh unit terdaftar di database.
     */
    public function __construct(
        public readonly LengthAwarePaginator $unit,
        public readonly array $kartu,
        public readonly array $distribusi,
        public readonly array $kepatuhan,
        public readonly array $lewat,
        public readonly array $saring,
        public readonly int $totalSemua,
    ) {}

    /** Susun halaman master unit dari parameter query. */
    public static function dariRequest(Request $request): self
    {
        $saring = [
            'q' => trim((string) $request->query('q', '')),
            'kategori' => self::saringEnum($request->query('kategori'), KategoriUnit::class),
            'koneksi' => self::saringBawaan($request->query('koneksi'), self::KONEKSI),
            'akses' => self::saringEnum($request->query('akses'), StatusAksesUnit::class),
            'beban' => self::saringBawaan($request->query('beban'), self::BEBAN),
            'urutan' => self::saringBawaan($request->query('urutan'), self::URUTAN, 'beban'),
        ];

        $lewat = self::lewatPerUnit();
        $kepatuhan = StatistikDashboard::kepatuhanSlaUnit();

        return new self(
            unit: self::tabel($saring, $lewat),
            kartu: self::kartu(),
            distribusi: self::distribusi(),
            kepatuhan: $kepatuhan,
            lewat: $lewat,
            saring: $saring,
            totalSemua: MasterUnit::query()->count(),
        );
    }

    /**
     * Daftar isi setiap select saring.
     *
     * Opsi tidak_unit dan status akses diambil dari enum, supaya pilihan di
     * form saring selalu sama dengan nilai yang benar-benar diterima model.
     *
     * @return array<string, array<string, string>>
     */
    public static function pilihanSaring(): array
    {
        $kategori = ['semua' => 'Semua Kategori'];

        foreach (KategoriUnit::cases() as $opsi) {
            $kategori[$opsi->value] = $opsi->label();
        }

        $akses = ['semua' => 'Semua Kondisi Akses'];

        foreach (StatusAksesUnit::cases() as $opsi) {
            $akses[$opsi->value] = $opsi->label();
        }

        return [
            'q' => ['nama' => 'Nama Unit', 'kode' => 'Kode Unit'],
            'kategori' => $kategori,
            'koneksi' => [
                'semua' => 'Semua Kondisi Koneksi',
                'terhubung' => 'Terhubung SIMRS',
                'terputus' => 'Terputus',
                'belum_ditugaskan' => 'Belum Ditugaskan Tiket',
            ],
            'akses' => $akses,
            'beban' => [
                'semua' => 'Semua Beban',
                'ada' => 'Punya Tiket Aktif',
                'kosong' => 'Antrian Kosong',
            ],
            'urutan' => [
                'beban' => 'Beban Tertinggi',
                'nama' => 'Nama Unit A-Z',
                'kategori' => 'Kategori',
                'kode' => 'Kode Unit',
            ],
        ];
    }

    /** URL halaman dengan satu nilai saring yang diganti, nilai lain dipertahankan. */
    public function urlSaring(string $kunci, ?string $nilai): string
    {
        $parameter = $this->saring;

        if ($nilai === null || $nilai === '' || $nilai === 'semua') {
            unset($parameter[$kunci]);
        } else {
            $parameter[$kunci] = $nilai;
        }

        $parameter = array_filter($parameter, fn ($isi): bool => $isi !== null && $isi !== '');

        return $parameter === []
            ? route('admin.unit.index')
            : route('admin.unit.index', $parameter);
    }

    /** True kalau ada saring lain dari sekadar urutan bawaan. */
    public function adaSaring(): bool
    {
        foreach ($this->saring as $kunci => $nilai) {
            if ($kunci === 'urutan') {
                continue;
            }

            if ($nilai !== null && $nilai !== '' && $nilai !== 'semua') {
                return true;
            }
        }

        return false;
    }

    /** Jumlah tiket aktif yang ditugaskan ke sebuah unit. */
    public function beban(MasterUnit $unit): int
    {
        return (int) ($unit->beban_aktif ?? 0);
    }

    /** Persentase kepatuhan SLA unit, atau null kalau belum punya pengaduan selesai. */
    public function kepatuhan(MasterUnit $unit): ?float
    {
        return $this->kepatuhan[$unit->id] ?? null;
    }

    /** Tiket aktif unit yang sudah lewat batas hari kerja investigasi. */
    public function lewat(MasterUnit $unit): int
    {
        return $this->lewat[$unit->id] ?? 0;
    }

    /**
     * Ambang kepatuhan SLA dari config pengaduan.
     *
     * Nilainya tidak diketik ulang di sini supaya tabel unit, beranda, dan
     * monitor disposisi semuanya menandai unit dengan batas yang sama.
     */
    public function standarKepatuhan(): int
    {
        return (int) config('pengaduan.kepatuhan_standar_persen', 90);
    }

    /**
     * Terapkan saring dan urutan pada query unit.
     *
     * Pencarian memakai satu LIKE untuk beberapa kolom sekaligus. Beban dan
     * kepatuhan SLA sengaja tidak ikut diurutkan lewat SQL karena keduanya
     * dihitung dari agregat yang butuh hitsunan, jadi urutannya cukup
     * berdasarkan kolom unit dan beban.
     *
     * @param  array<string, string|null>  $saring
     * @param  array<int, int>  $lewat
     * @return LengthAwarePaginator<int, MasterUnit>
     */
    private static function tabel(array $saring, array $lewat): LengthAwarePaginator
    {
        $query = MasterUnit::query()
            ->withCount([
                'pengaduan as beban_aktif' => self::pengaduanAktif(),
                'pengaduan as beban_selesai',
            ]);

        if (($saring['q'] ?? '') !== '') {
            $cari = '%'.$saring['q'].'%';

            $query->where(function (Builder $q) use ($cari): void {
                $q->where('kode', 'like', $cari)
                    ->orWhere('nama', 'like', $cari)
                    ->orWhere('pic', 'like', $cari)
                    ->orWhere('ekstensi', 'like', $cari);
            });
        }

        self::filterKategori($query, $saring['kategori'] ?? null);
        self::filterAkses($query, $saring['akses'] ?? null);
        self::filterKoneksi($query, $saring['koneksi'] ?? null);

        if (($saring['beban'] ?? 'semua') === 'ada') {
            $query->whereHas('pengaduan', self::pengaduanAktif());
        } elseif (($saring['beban'] ?? 'semua') === 'kosong') {
            $query->whereDoesntHave('pengaduan', self::pengaduanAktif());
        }

        match ($saring['urutan'] ?? 'beban') {
            'nama' => $query->orderBy('nama')->orderBy('kode'),
            'kategori' => $query->orderBy('kategori')->orderBy('nama'),
            'kode' => $query->orderBy('kode'),
            default => $query->orderByDesc('beban_aktif')->orderBy('nama')->orderBy('kode'),
        };

        return $query->paginate(self::PER_HALAMAN)->withQueryString();
    }

    /**
     * Saring kategori. Nilai enum yang tidak dikenal sudah dibuang sebelumnya. */
    private static function filterKategori(Builder $query, ?string $nilai): void
    {
        if ($nilai === null) {
            return;
        }

        $query->where('kategori', $nilai);
    }

    /**
     * Kriteria "pengaduan yang belum selesai" untuk filter dan aggregate.
     *
     * Kondisinya ditulis ulang di sini, bukan memakai scope aktif() milik
     * model Pengaduan. Alasannya whereHas() dan whereDoesntHave() mengirim
     * Query\Builder ke closure, sedangkan scope hanya tersedia di
     * Eloquent Builder. Nilai statusnya sendiri tetap dari enum supaya
     * definisi selesai tidak punya dua versi.
     *
     * @return Closure(mixed): mixed
     */
    private static function pengaduanAktif(): Closure
    {
        return fn ($q) => $q->where('status', '!=', StatusPengaduan::Selesai->value);
    }

    /** Saring kondisi akses akun PIC unit. */
    private static function filterAkses(Builder $query, ?string $nilai): void
    {
        if ($nilai === null) {
            return;
        }

        $query->where('status_akses', $nilai);
    }

    /**
     * Saring kondisi koneksi unit.
     *
     * Ketiga kelasnya dibuat lewat scope di model supaya angka di dalam
     * filter dan angka di kartu ringkasan di atasnya tidak pernah bisa
     * saling menyangkal. Urutan pemeriksaannya juga tidak dianggap remeh:
     * kelompok "belum ditugaskan" memang harus diperiksa terpisah dari
     * "terputus", karena kalau keduanya dicampur admin akan menganggap unit
     * yang baru terdaftar sama rusaknya dengan unit yang sedang tidak bisa
     * dihubungi.
     */
    private static function filterKoneksi(Builder $query, ?string $nilai): void
    {
        match ($nilai) {
            'terhubung' => $query->terhubung(),
            'terputus' => $query->terputus(),
            'belum_ditugaskan' => $query->belumDitugaskan(),
            default => null,
        };
    }

    /**
     * Empat kartu ringkasan di atas tabel unit.
     *
     * Angka koneksi memakai definisi yang sama dengan beranda dan monitor
     * disposisi, yaitu heartbeat SIMRS yang masih dalam 15 menit terakhir.
     * Kalau definisinya dibedakan di sini, jumlah unit terhubung bisa
     * berbeda antar halaman untuk data yang sama.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function kartu(): array
    {
        $total = MasterUnit::query()->count();
        $aktif = MasterUnit::query()->aktif()->count();

        $terhubung = MasterUnit::query()->terhubung()->count();

        $terputus = MasterUnit::query()->terputus()->count();

        $punyaAntrean = MasterUnit::query()
            ->aktif()
            ->whereHas('pengaduan', self::pengaduanAktif())
            ->count();

        $tiketAktif = Pengaduan::query()->aktif()->whereNotNull('master_unit_id')->count();

        $akunAktif = MasterUnit::query()
            ->where('status_akses', StatusAksesUnit::AkunAktif->value)
            ->count();

        $belumDiundang = MasterUnit::query()
            ->where('status_akses', StatusAksesUnit::BelumDiundang->value)
            ->count();

        return [
            [
                'label' => 'Total Unit Terdaftar',
                'nilai' => $total,
                'satuan' => 'unit',
                'ikon' => 'domain',
                'nadaAngka' => 'text-on-surface',
                'nadaBar' => 'bg-primary',
                'persen' => self::persen($aktif, $total),
                'sisi' => $aktif.' unit aktif, '.($total - $aktif).' unit nonaktif',
                'ket' => 'Unit nonaktif disembunyikan dari pilihan tujuan disposisi pengaduan.',
            ],
            [
                'label' => 'Unit Terhubung SIMRS',
                'nilai' => $terhubung,
                'satuan' => 'unit',
                'ikon' => 'cloud_done',
                'nadaAngka' => 'text-secondary',
                'nadaBar' => 'bg-secondary',
                'persen' => self::persen($terhubung, $total),
                'sisi' => $terputus.' unit terputus',
                'ket' => 'Koneksi dianggap hidup bila heartbeat SIMRS masuk dalam '.MasterUnit::BATAS_KONEKSI.' menit terakhir.',

                // Angka tetap dihitung karena masih dipakai tes dan saringan
                // 'terhubung'/'terputus', tapi kartunya tidak ditampilkan:
                // tidak ada endpoint yang menulis heartbeat, jadi angkanya
                // selalu terlihat hidup padahal belum tersambung apa pun.
                'sembunyi' => true,
            ],
            [
                'label' => 'Unit dengan Beban Aktif',
                'nilai' => $punyaAntrean,
                'satuan' => 'unit',
                'ikon' => 'pending_actions',
                'nadaAngka' => 'text-tertiary-fixed-dim',
                'nadaBar' => 'bg-tertiary',
                'persen' => self::persen($punyaAntrean, $total),
                'sisi' => $tiketAktif.' pengaduan masih diproses unit',
                'ket' => 'Beban dihitung dari pengaduan yang belum selesai, bukan akumulasi historis.',
            ],
            [
                'label' => 'Akun PIC Aktif',
                'nilai' => $akunAktif,
                'satuan' => 'unit',
                'ikon' => 'badge',
                'nadaAngka' => $belumDiundang > 0 ? 'text-error' : 'text-secondary',
                'nadaBar' => $belumDiundang > 0 ? 'bg-error' : 'bg-secondary',
                'persen' => self::persen($akunAktif, $total),
                'sisi' => $belumDiundang.' unit belum diundang',
                'ket' => 'Status akses dicatat manual karena alur undangan aktivasi belum dibangun.',
            ],
        ];
    }

    /**
     * Sebaran unit per kategori layanan, siap ditampilkan sebagai bar.
     *
     * Kategori tanpa unit tetap ikut ditampilkan dengan nilai nol supaya
     * grafiknya menunjukkan seluruh categories layanan, bukan hanya yang
     * kebetulan sudah terisi.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function distribusi(): array
    {
        $jumlah = MasterUnit::query()
            ->selectRaw('kategori, count(*) as jumlah')
            ->groupBy('kategori')
            ->pluck('jumlah', 'kategori');

        $puncak = (int) ($jumlah->max() ?? 0);

        return array_map(function (KategoriUnit $kategori) use ($jumlah, $puncak): array {
            $total = (int) ($jumlah[$kategori->value] ?? 0);

            return [
                'kategori' => $kategori,
                'jumlah' => $total,
                'persen' => StatistikDashboard::porsiBeban($total, $puncak),
            ];
        }, KategoriUnit::cases());
    }

    /** Persentase aman untuk dipakai sebagai lebar bar. */
    private static function persen(int $bagian, int $total): int
    {
        return $total > 0 ? (int) round($bagian / $total * 100) : 0;
    }

    /**
     * Hitung tiket yang sudah lewat batas investigasi untuk tiap unit.
     *
     * Perhitungannya tidak bisa dipindahkan ke SQL karena batas SLA memakai
     * hitungan hari kerja. Jumlah tiketnya kecil, jadi cukup difilter di
     * memori seperti yang dilakukan statistik dashboard.
     *
     * @return array<int, int>
     */
    private static function lewatPerUnit(): array
    {
        return Pengaduan::query()
            ->aktif()
            ->whereNotNull('master_unit_id')
            ->get(['master_unit_id', 'created_at'])
            ->filter(fn (Pengaduan $pengaduan): bool => Sla::lewatInvestigasi($pengaduan->created_at))
            ->countBy('master_unit_id')
            ->map(fn ($jumlah): int => (int) $jumlah)
            ->all();
    }

    /**
     * Terima nilai saring enum hanya kalau memang ada di enum tersebut.
     *
     * Nilai enum yang tidak dikenal dibuang, bukan lewat exception, supaya
     * tautan lama atau parameter crafted tidak membuat halaman error 500.
     *
     * @param  class-string  $enum
     */
    private static function saringEnum(mixed $nilai, string $enum): ?string
    {
        $nilai = (string) $nilai;

        if ($nilai === '') {
            return null;
        }

        return $enum::tryFrom($nilai)?->value;
    }

    /**
     * Terima nilai saring bebas hanya kalau ada di daftar standing.
     *
     * @param  array<int, string>  $pilihan
     */
    private static function saringBawaan(mixed $nilai, array $pilihan, string $bawaan = 'semua'): string
    {
        $nilai = (string) $nilai;

        return in_array($nilai, $pilihan, true) ? $nilai : $bawaan;
    }

    /**
     * Pilihan peran akses untuk form unit.
     *
     * @return array<string, string>
     */
    public static function pilihanPeran(): array
    {
        return array_combine(
            array_map(fn (PeranAksesUnit $p): string => $p->value, PeranAksesUnit::cases()),
            array_map(fn (PeranAksesUnit $p): string => $p->label(), PeranAksesUnit::cases()),
        );
    }
}
