<?php

namespace App\Support;

use App\Enums\KategoriPengaduan;
use App\Enums\StatusInvestigasi;
use App\Enums\StatusPengaduan;
use App\Enums\ZonaSla;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Data tampilan untuk halaman Daftar Pengaduan di konsol admin.
 *
 * Halaman ini menampilkan seluruh pengaduan dalam bentuk tabel yang bisa
 * disaring, jadi angka dan keterangan pada tabel harus berasal dari query
 * nyata. Class ini menahan query, penyusunan kalimat, dan pemilihan warna
 * supaya template hanya bekerja dengan data yang sudah jadi.
 *
 * Jumlah pada tab Lapis 1 dan Lapis 2 dihitung tanpa filter dimensi itu
 * sendiri: tab Lapis 1 menampilkan sebaran seluruh pengaduan pada tiap
 * tahap, bukan sebaran dari hasil pencarian yang sedang aktif. Dengan begitu
 * angka pada tab tidak ikut berubah-ubah saat kata kunci diketik.
 */
final class DaftarPengaduan
{
    /** Jumlah baris per halaman yang boleh dipilih. */
    public const UKURAN_HALAMAN = [10, 25, 50, 100];

    /**
     * @param  array<string, int>  $jumlahTahap  Indeks 'semua' dan nilai enum tahap.
     * @param  array<string, int>  $jumlahInvestigasi  Indeks nilai enum StatusInvestigasi.
     * @param  array<string, mixed>  $ringkas  Angka untuk strip konteks, kartu kaki, dan paginasi.
     */
    public function __construct(
        public readonly LengthAwarePaginator $daftar,
        public readonly array $jumlahTahap,
        public readonly array $jumlahInvestigasi,
        public readonly ?StatusPengaduan $status,
        public readonly ?StatusInvestigasi $investigasi,
        public readonly ?KategoriPengaduan $kategori,
        public readonly ?MasterUnit $unit,
        public readonly string $cari,
        public readonly int $perHalaman,
        public readonly Collection $pilihanUnit,
        public readonly array $ringkas,
    ) {}

    /**
     * Susun halaman daftar pengaduan dari parameter query.
     *
     * Nilai filter yang tidak dikenal diabaikan, bukan dipakai sebagai
     * kondisi query, supaya tautan lama atau crafted tidak bisa membuat
     * hasil yang tidak terduga.
     */
    public static function dariRequest(Request $request): self
    {
        $status = StatusPengaduan::dariNilai($request->query('status'));
        $investigasi = StatusInvestigasi::dariNilai($request->query('investigasi'));
        $kategori = self::kategori($request->query('kategori'));
        $unit = self::unit($request->query('unit'));
        $cari = trim((string) $request->query('q', ''));
        $perHalaman = self::perHalaman($request->query('per_halaman'));

        // Closure ini adalah query dasar tanpa filter dimensinya sendiri,
        // dipakai ulang untuk menghitung tiap tab dan untuk menarik baris.
        $dasar = fn (): Builder => Pengaduan::query()
            ->when($cari !== '', fn (Builder $q): Builder => $q->where(
                fn (Builder $w): Builder => $w
                    ->where('kode_tiket', 'like', '%'.$cari.'%')
                    ->orWhere('subjek', 'like', '%'.$cari.'%')
                    ->orWhere('nama_lengkap', 'like', '%'.$cari.'%')
                    ->orWhere('nrm', 'like', '%'.$cari.'%')
                    ->orWhere('unit', 'like', '%'.$cari.'%')
            ))
            ->when($unit !== null, fn (Builder $q): Builder => $q->where('master_unit_id', $unit->id))
            ->when($kategori !== null, fn (Builder $q): Builder => $q->where('kategori', $kategori->value));

        $jumlahTahap = ['semua' => $dasar()->count()];

        foreach (StatusPengaduan::cases() as $tahap) {
            $jumlahTahap[$tahap->value] = $dasar()->where('status', $tahap->value)->count();
        }

        $jumlahInvestigasi = [];

        foreach (StatusInvestigasi::cases() as $pilihan) {
            $jumlahInvestigasi[$pilihan->value] = $pilihan->terapkan($dasar())->count();
        }

        $daftar = $dasar()
            ->when($status !== null, fn (Builder $q): Builder => $q->where('status', $status->value))
            ->when($investigasi !== null, fn (Builder $q): Builder => $investigasi->terapkan($q))
            ->with('masterUnit')
            // Hanya balasan unit yang dihitung untuk kalimat "N balasan unit
            // masuk". Pesan pembuka otomatis dan balasannya pelapor ikut
            // tersimpan di tabel pesan, tapi keduanya bukan jawaban unit,
            // sehingga kalau ikut dihitung angkanya selalu lebih besar
            // daripada kenyataan. Setiap tiket baru punya satu pesan pembuka,
            // jadi tanpa penyaring ini minimal selalu kelebihan satu.
            ->withCount(['pesan as pesan_count' => fn (Builder $q): Builder => $q->where('peran', 'admin')])
            ->withExists(['pesan as sudah_dibalas' => fn (Builder $q): Builder => $q->where('peran', 'admin')])
            // Tiket yang belum ditutup didahulukan, lalu yang terbaru.
            ->orderByRaw('case when status = ? then 1 else 0 end', [StatusPengaduan::Selesai->value])
            ->latest('created_at')
            ->paginate($perHalaman)
            ->withQueryString();

        return new self(
            daftar: $daftar,
            jumlahTahap: $jumlahTahap,
            jumlahInvestigasi: $jumlahInvestigasi,
            status: $status,
            investigasi: $investigasi,
            kategori: $kategori,
            unit: $unit,
            cari: $cari,
            perHalaman: $perHalaman,
            pilihanUnit: MasterUnit::query()->orderBy('kode')->get(),
            ringkas: self::ringkas($jumlahTahap),
        );
    }

    /** True bila ada filter selain pencarian teks yang sedang aktif. */
    public function adaFilterLain(): bool
    {
        return $this->status !== null
            || $this->investigasi !== null
            || $this->kategori !== null
            || $this->unit !== null;
    }

    /**
     * URL tanpa satu dimensi filter, dipakai untuk tab Lapis 1 dan Lapis 2.
     *
     * Halaman dan ukuran halaman dibuang supaya pindah tab selalu kembali
     * ke baris pertama.
     */
    public function urlTanpa(string $kunci, ?string $nilai = null): string
    {
        $parameter = request()->except([$kunci, 'page', 'per_halaman']);

        return $nilai === null || $nilai === ''
            ? route('admin.pengaduan.index', $parameter)
            : route('admin.pengaduan.index', array_merge($parameter, [$kunci => $nilai]));
    }

    /**
     * Satu baris tabel daftar pengaduan.
     *
     * @return array<string, mixed>
     */
    public function baris(Pengaduan $pengaduan): array
    {
        $sudahDibalas = (bool) $pengaduan->sudah_dibalas;
        $investigasi = StatusInvestigasi::dariPengaduan($pengaduan, $sudahDibalas);

        return [
            'kode' => $pengaduan->kode_tiket,
            'prioritas' => $this->prioritas($pengaduan),
            'pelapor' => $pengaduan->nama_lengkap,
            'nrm' => $pengaduan->nrm,
            'unit' => $pengaduan->masterUnit?->namaLengkap() ?? $pengaduan->unit,
            'tertaut' => $pengaduan->master_unit_id !== null,
            'kategori' => $pengaduan->kategori,
            'subjek' => $pengaduan->subjek,
            'kutipan' => $pengaduan->deskripsi,
            'status' => $pengaduan->status,
            'catatanStatus' => $this->catatanStatus($pengaduan),
            'investigasi' => $investigasi,
            'catatanInvestigasi' => $this->catatanInvestigasi($pengaduan, $investigasi),
            'sla' => $this->sla($pengaduan),
            'aksi' => $this->aksi($pengaduan),
            'redup' => $pengaduan->status->selesai(),
        ];
    }

    /**
     * Badge prioritas di kolom nomor tiket.
     *
     * Prioritas diturunkan dari kondisi tiket yang benar-benar ada:
     * investigasi unit yang lewat batas, tiket yang menunggu kelengkapan
     * pelapor, tiket medis yang mendekati hari terakhir, dan tiket yang
     * sudah ditutup.
     *
     * @return array{label: string, ikon: string, nada: string}
     */
    private function prioritas(Pengaduan $pengaduan): array
    {
        if ($pengaduan->status->selesai()) {
            return ['label' => 'Selesai', 'ikon' => 'check', 'nada' => 'bg-surface-container text-on-surface'];
        }

        if ($pengaduan->status->perluAksi()) {
            return [
                'label' => 'Menunggu Pelapor',
                'ikon' => 'hourglass_top',
                'nada' => 'bg-surface-container text-on-surface',
            ];
        }

        if (Sla::lewatInvestigasi($pengaduan->created_at)) {
            return [
                'label' => 'Lewat Batas Unit',
                'ikon' => 'priority_high',
                'nada' => 'bg-error-container text-on-error-container',
            ];
        }

        if ($pengaduan->kategori === KategoriPengaduan::Medis && $pengaduan->sisaHariSla() <= 1) {
            return [
                'label' => 'Medis Kritis',
                'ikon' => 'bolt',
                'nada' => 'bg-error-container text-on-error-container',
            ];
        }

        if ($pengaduan->status->diterima()) {
            return [
                'label' => 'Menunggu Unit',
                'ikon' => 'pending_actions',
                'nada' => 'bg-tertiary-container text-tertiary-fixed',
            ];
        }

        return ['label' => 'Normal', 'ikon' => 'flag', 'nada' => 'bg-surface-container text-on-surface'];
    }

    /**
     * Uraian tambahan pada sel status utama di sisi humas.
     */
    private function catatanStatus(Pengaduan $pengaduan): string
    {
        $unit = $pengaduan->masterUnit;

        return match ($pengaduan->status) {
            StatusPengaduan::Diterima => 'Menunggu unit membuka tiket',
            StatusPengaduan::Diproses => $unit === null
                ? 'Klarifikasi ditangani humas langsung'
                : 'Disposisi humas ke '.$unit->kode,
            StatusPengaduan::Revisi => 'Menunggu kelengkapan dari pelapor',
            StatusPengaduan::Selesai => 'Jawaban resmi telah diterbitkan',
        };
    }

    /**
     * Uraian tambahan pada sel status investigasi unit.
     *
     * Kalimat statis pada enum menjelaskan kondisi, kalimat di sini
     * menjelaskan pergerakan tiket yang sedang berjalan.
     */
    private function catatanInvestigasi(Pengaduan $pengaduan, StatusInvestigasi $investigasi): string
    {
        return match ($investigasi) {
            StatusInvestigasi::MenungguRacikan => $pengaduan->pesan_count
                .' balasan unit masuk, siap diracik humas',
            StatusInvestigasi::LangsungHumas => 'Tanpa disposisi unit teknis',
            StatusInvestigasi::SedangInvestigasi => $this->lamaBerjalan($pengaduan),
            StatusInvestigasi::JawabanUnit => 'Arsip unit tersimpan sebagai bukti penyelesaian',
        };
    }

    /** Lama investigasi berjalan dalam bahasa yang enak dibaca. */
    private function lamaBerjalan(Pengaduan $pengaduan): string
    {
        $jam = max(1, $pengaduan->created_at->diffInHours(now()));

        return $jam >= 24
            ? 'Berjalan '.intdiv($jam, 24).' hari sejak tiket masuk'
            : 'Berjalan '.$jam.' jam sejak tiket masuk';
    }

    /**
     * Kolom monitoring SLA: posisi hari kerja, progres, dan sisa waktu.
     *
     * Tiket yang menunggu pelapor tetap memakai penomoran hari yang sama
     * seperti biasa, karena tidak ada mekanisme penghentian timer. Yang
     * dibedakan hanya kalimat judulnya, supaya admin tahu bahwa keterlambatan
     * pada tahap ini menunggu pelapor, bukan menunggu unit.
     *
     * @return array<string, mixed>
     */
    private function sla(Pengaduan $pengaduan): array
    {
        $selesai = $pengaduan->status->selesai();
        $jeda = $pengaduan->status->perluAksi();
        $hariKe = Sla::hariKe($pengaduan->created_at);
        $lamaSelesai = $this->hariKerjaSelesai($pengaduan);
        $zona = $pengaduan->zonaSla();

        $posisi = match (true) {
            $selesai => 'Tuntas '.$lamaSelesai.' hari kerja',
            $zona === ZonaSla::Terlambat => 'Hari ke-'.$hariKe.' (Lewat SLA)',
            $zona === ZonaSla::Mendek => 'Hari ke-'.$hariKe.' (Peringatan)',
            default => 'Hari ke-'.$hariKe.' (Aman)',
        };

        return [
            'jeda' => $jeda,
            'judul' => $jeda
                ? 'SLA '.Sla::hariKerja().' Hari Kerja (ditahan menunggu pelapor)'
                : 'SLA '.Sla::hariKerja().' Hari Kerja',
            'posisi' => $posisi,
            'nadaPosisi' => match (true) {
                $selesai => 'text-secondary',
                $zona === ZonaSla::Terlambat => 'text-error',
                $zona === ZonaSla::Mendek => 'text-on-tertiary-container',
                default => 'text-secondary',
            },
            'persen' => $pengaduan->progresPersen(),
            'warnaProgres' => match (true) {
                $jeda => 'bg-outline',
                $zona === ZonaSla::Terlambat => 'bg-error',
                default => 'bg-secondary',
            },
            'ringkas' => $selesai
                ? 'Total selesai: '.$lamaSelesai.' hari kerja'
                : 'Sisa '.$pengaduan->sisaHariSla().' hari kerja, target '
                    .$pengaduan->targetSla()->format('d M Y'),
        ];
    }

    /** Lama penyelesaian dalam hari kerja, nol bila waktu selesai tidak tercatat. */
    private function hariKerjaSelesai(Pengaduan $pengaduan): int
    {
        if ($pengaduan->selesai_at === null) {
            return 0;
        }

        return (int) abs($pengaduan->created_at->diffInWeekdays($pengaduan->selesai_at, true));
    }

    /**
     * Tombol aksi pada kolom terakhir.
     *
     * Hanya "Buka Detail" yang sudah punya halaman, jadi hanya dia yang
     * membawa url. Sisanya masih berupa tombol nonaktif karena alur
     * disposisi dan nudging unit belum dibangun, sehingga tidak boleh ada
     * tautan palsu. Jumlah tombol dibatasi supaya tinggi baris tabel tetap
     * seragam.
     *
     * @return array<int, array{label: string, ikon: string, nada: string, url: ?string}>
     */
    private function aksi(Pengaduan $pengaduan): array
    {
        $aksi = [];

        if ($pengaduan->status->diterima()) {
            $aksi[] = [
                'label' => 'Tentukan Penanganan',
                'ikon' => 'forward_to_inbox',
                'nada' => 'bg-secondary text-on-secondary',
                'url' => null,
            ];
        }

        if (! $pengaduan->status->selesai() && ! $pengaduan->status->perluAksi()) {
            $aksi[] = [
                'label' => Sla::lewatInvestigasi($pengaduan->created_at)
                    ? 'Nudge (Eskalasi)'
                    : 'Nudge Unit',
                'ikon' => 'notifications_active',
                'nada' => 'bg-surface-container text-on-surface-variant',
                'url' => null,
            ];
        }

        $aksi[] = [
            'label' => $pengaduan->status->perluAksi() ? 'Buka Detail' : 'Buka Detail',
            'ikon' => 'visibility',
            'nada' => 'bg-primary-container text-on-primary',
            'url' => route('admin.pengaduan.show', $pengaduan->kode_tiket),
        ];

        return $aksi;
    }

    /**
     * Angka untuk strip konteks di atas dan kaki tabel.
     *
     * @param  array<string, int>  $jumlahTahap
     * @return array<string, mixed>
     */
    private static function ringkas(array $jumlahTahap): array
    {
        return [
            'total' => $jumlahTahap['semua'],
            'aktif' => $jumlahTahap['semua'] - $jumlahTahap[StatusPengaduan::Selesai->value],
            'unit_terhubung' => StatistikDashboard::unitTerhubung(),
            'unit_total' => MasterUnit::query()->count(),
            'hari_investigasi' => Sla::hariInvestigasi(),
            'lewat' => StatistikDashboard::ringkasanKritis()['total_lewat'],
        ];
    }

    private static function kategori(mixed $nilai): ?KategoriPengaduan
    {
        return is_string($nilai) && $nilai !== '' ? KategoriPengaduan::tryFrom($nilai) : null;
    }

    private static function unit(mixed $nilai): ?MasterUnit
    {
        return is_string($nilai) && $nilai !== ''
            ? MasterUnit::query()->where('kode', $nilai)->first()
            : null;
    }

    private static function perHalaman(mixed $nilai): int
    {
        $diminta = filter_var($nilai, FILTER_VALIDATE_INT);

        return in_array($diminta, self::UKURAN_HALAMAN, true) ? $diminta : 10;
    }
}
