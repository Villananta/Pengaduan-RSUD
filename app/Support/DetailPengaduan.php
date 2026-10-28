<?php

namespace App\Support;

use App\Enums\StatusInvestigasi;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use App\Models\RiwayatStatusPengaduan;
use App\Services\TahapService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Data tampilan untuk halaman Workspace & Detail satu tiket pengaduan.
 *
 * Halaman ini menumpuk banyak bagian sekaligus: identitas tiket, posisi
 * tahap pengaduan, status investigasi unit, posisi SLA, percakapan dengan
 * pelapor, dan jejak audit. Semua query dan penyusunan kalimat ditahan di
 * sini supaya template hanya membungkus data yang sudah jadi.
 *
 * Tombol yang ditampilkan tidak dihitung di sini. Ketersediaan tindakan
 * (pindah tahap, penanganan langsung, tandai kasus berat) diambil dari
 * App\Services\TahapService supaya tombol di layar dan penerimaan
 * di server memakai satu aturan yang sama.
 */
final class DetailPengaduan
{
    /**
     * @param  array<int, array<string, mixed>>  $pilar  Stepper empat tahap pengaduan.
     * @param  array<string, mixed>  $unit  Status investigasi unit pada tiket ini.
     * @param  array<string, mixed>  $sla  Posisi waktu kerja terhadap target.
     * @param  array<int, array<string, mixed>>  $lampiran  Berkas yang diunggah pelapor.
     * @param  array<int, array<string, mixed>>  $percakapan  Pesan kanal pelapor dengan humas.
     * @param  array<int, array<string, mixed>>  $koordinasi  Pesan kanal koordinasi humas dan unit.
     * @param  array<int, array<string, mixed>>  $audit  Jejak perpindahan tahap.
     * @param  array<int, array<string, mixed>>  $kegiatan  Log kronologis seluruh aktivitas tiket.
     * @param  Collection<int, MasterUnit>  $pilihanUnit  Unit yang boleh dipilih pada form disposisi.
     */
    public function __construct(
        public readonly Pengaduan $pengaduan,
        public readonly StatusInvestigasi $investigasi,
        public readonly array $pilar,
        public readonly array $unit,
        public readonly array $sla,
        public readonly array $lampiran,
        public readonly array $percakapan,
        public readonly array $koordinasi,
        public readonly array $audit,
        public readonly array $kegiatan,
        public readonly Collection $pilihanUnit,
    ) {}

    /**
     * Susun halaman detail dari kode tiket.
     *
     * Kode tiket sudah unik jadi cukup jadi kunci pencarian; tiket yang tidak
     * ada dibiarkan melempar 404 supaya admin tidak melihat halaman kosong
     * untuk tiket yang dihapus.
     *
     * Unit param dipakai oleh halaman sisi unit: bila diisi, pencarian ikut
     * dikunci pada master_unit_id itu sehingga tiket milik unit lain ikut
     * berujung 404, bukan terbuka oleh sekadar menebak kode tiket.
     */
    public static function dariKode(string $kode, ?MasterUnit $unit = null): self
    {
        $pengaduan = Pengaduan::query()
            ->with(['masterUnit', 'pesan', 'riwayatStatus.admin'])
            ->when($unit !== null, fn (Builder $q): Builder => $q
                ->where('master_unit_id', $unit->id)
                // Tiket yang masih 'Diterima' belum diteruskan Humas, jadi
                // unit belum berhak membukanya meski master_unit_id terisi.
                ->sudahDiteruskanHumas())
            ->where('kode_tiket', $kode)
            ->firstOrFail();

        // Hanya jawaban PIC unit yang menaikkan tiket ke Menunggu Racikan.
        // Balasan humas di kolom percakapan tidak boleh ikut terbaca sebagai
        // jawaban unit, karena humas bisa membalas tanpa menunggu unit.
        $unitMenjawab = $pengaduan->pesan->contains(
            fn (PesanPengaduan $pesan): bool => $pesan->dariUnit()
        );

        $investigasi = StatusInvestigasi::dariPengaduan($pengaduan, $unitMenjawab);

        return new self(
            pengaduan: $pengaduan,
            investigasi: $investigasi,
            pilar: self::pilar($pengaduan),
            unit: self::unit($pengaduan, $investigasi),
            sla: self::sla($pengaduan),
            lampiran: self::lampiran($pengaduan),
            percakapan: self::percakapan($pengaduan),
            koordinasi: self::koordinasi($pengaduan),
            audit: self::audit($pengaduan),
            kegiatan: self::kegiatan($pengaduan),
            // Unit nonaktif tidak ditawarkan lagi karena admin sudah sengaja
            // mematikannya dari halaman master unit. Menawarkannya di sini membuat
            // sakelar aktif di master data tidak punya efek apa pun.
            pilihanUnit: MasterUnit::query()->aktif()->orderBy('kode')->get(),
        );
    }

    /** Kode tiket, dipakai untuk judul halaman dan lencana. */
    public function kode(): string
    {
        return $this->pengaduan->kode_tiket;
    }

    /**
     * Instruksi disposisi terakhir dari humas untuk halaman sisi unit.
     *
     * Satu-satunya arahan humas yang tersimpan permanen pada tiket adalah
     * catatan pada baris riwayat perpindahan tahap, jadi halaman unit cukup
     * menarik yang paling baru tanpa membaca kolomnya sendiri. Baris penandaan
     * kasus berat, keparahan, dan kategori sengaja dilewati karena kalimatnya
     * ditulis sistem, bukan arahan kerja untuk unit. Null ketika belum ada admin
     * yang pernah menulis catatan, supaya template bisa menampilkan penjelas
     * pengganti alih-alih baris kosong.
     *
     * @return array<string, mixed>|null
     */
    public function instruksiDisposisi(): ?array
    {
        $terakhir = null;

        foreach ($this->audit as $baris) {
            if (filled($baris['catatan']) && ! $baris['kasusBerat'] && ! $baris['keparahan'] && ! $baris['kategori']) {
                $terakhir = $baris;
            }
        }

        return $terakhir;
    }

    /**
     * Alasan tiket belum bisa ditutup dari panel tahap, atau null kalau bisa.
     *
     * Diteruskan ke TahapService supaya keterangan di layar dan aturan di
     * server memakai satu sumber yang sama.
     */
    public function alasanTutup(): ?string
    {
        return TahapService::alasanTutup($this->pengaduan);
    }

    /**
     * Tahap yang boleh dipilih admin dari tombol di bawah halaman.
     *
     * Diambil dari aturan yang sama dengan pemindahan tahap supaya tombol
     * yang tampil di layar tidak mungkin lebih banyak daripada yang diizinkan
     * server.
     *
     * @return array<int, StatusPengaduan>
     */
    public function tujuanTersedia(): array
    {
        return TahapService::tujuanTersedia($this->pengaduan);
    }

    /**
     * True bila humas masih boleh menangani tiket ini tanpa unit teknis.
     *
     * Penanganan langsung memakai perpindahan tahap biasa ke Diproses, jadi
     * syaratnya mengikuti tujuanTersedia(). Dengan begitu tombol Jalur 2 dan
     * tombol Proses di bawah halaman tidak pernah berbeda pendapat.
     */
    public function bisaTanganiLangsung(): bool
    {
        return in_array(StatusPengaduan::Diproses, $this->tujuanTersedia(), true);
    }

    /**
     * Label, ikon, dan warna tombol untuk satu tahap tujuan.
     *
     * @return array{label: string, ikon: string, warna: string}
     */
    public function tombolTujuan(StatusPengaduan $tujuan): array
    {
        return TahapService::tombolTujuan($tujuan);
    }

    /**
     * Stepper empat tahap pengaduan.
     *
     * Waktu pada tiap pilar diambil dari baris riwayat saat tiket pertama
     * kali masuk ke tahap itu, bukan dari tanggal pengaduan, supaya tahap
     * yang dilalui saat reopen tidak menampilkan tanggal yang sama.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function pilar(Pengaduan $pengaduan): array
    {
        $masukTahap = [];

        foreach ($pengaduan->riwayatStatus as $baris) {
            $masukTahap[$baris->ke->value] ??= $baris->created_at;
        }

        $posisi = $pengaduan->status->tahap();

        return array_map(function (StatusPengaduan $tahap) use ($masukTahap, $posisi): array {
            $urut = $tahap->tahap();

            return [
                'urut' => $urut + 1,
                'label' => $tahap->label(),
                'subStatus' => $tahap->subStatus(),
                'keadaan' => $urut < $posisi ? 'tuntas' : ($urut === $posisi ? 'berjalan' : 'menunggu'),
                // Tahap yang sudah dilalui memakai centang, tahap yang sedang
                // berjalan dan yang belum tiba memakai ikon statusnya sendiri.
                'ikon' => $urut < $posisi ? 'check' : $tahap->ikon(),
                'waktu' => isset($masukTahap[$tahap->value])
                    ? $masukTahap[$tahap->value]->translatedFormat('d M Y, H:i')
                    : null,
            ];
        }, StatusPengaduan::cases());
    }

    /**
     * Status Lapis 2, yaitu sisi unit pelayanan.
     *
     * @return array<string, mixed>
     */
    private static function unit(Pengaduan $pengaduan, StatusInvestigasi $investigasi): array
    {
        $unit = $pengaduan->masterUnit;

        if ($unit === null) {
            return [
                'tertaut' => false,
                // Tiket tanpa unit berarti ditangani humas, jadi kolom "Unit
                // Tujuan" dan lencana atas menyebut Humas, bukan unit yang
                // pelapor sebut (nama itu tetap terbaca di kronologi).
                'nama' => 'Hubungan Masyarakat',
                'status' => $investigasi->label(),
                'ringkas' => $investigasi->ringkas(),
                'nada' => $investigasi->nada(),
                'keterangan' => 'Belum ada disposisi ke instalasi teknis',
                'terhubung' => false,
                'disposisi' => null,
            ];
        }

        return [
            'tertaut' => true,
            'nama' => $unit->namaLengkap(),
            // Kode ikut dibawa karena lembar disposisi yang dicetak ke PDF
            // harus menyebut kode unit pada baris penugasan.
            'kode' => $unit->kode,
            'status' => $investigasi->label(),
            'ringkas' => $investigasi->ringkas(),
            'nada' => $investigasi->nada(),
            'keterangan' => $investigasi->ringkasPendek(),
            'terhubung' => $unit->koneksiAktif(),
            'disposisi' => $unit->disposisi,
        ];
    }

    /**
     * Posisi hari kerja terhadap target SLA.
     *
     * @return array<string, mixed>
     */
    private static function sla(Pengaduan $pengaduan): array
    {
        $zona = $pengaduan->zonaSla();

        return [
            'hariKerja' => Sla::hariKerja(),
            'hariKe' => Sla::hariKe($pengaduan->created_at),
            'sisa' => $pengaduan->sisaHariSla(),
            'target' => $pengaduan->targetSla()->translatedFormat('d M Y'),
            'persen' => $pengaduan->progresPersen(),
            'zona' => $zona->label(),
            'zonaNilai' => $zona->value,
            'nadaZona' => $zona->badgeAdmin(),
            'ringkas' => $pengaduan->ringkasSla(),
            'lewatUnit' => $pengaduan->lewatInvestigasiUnit(),
            'hariInvestigasi' => Sla::hariInvestigasi(),

            // Kasus berat menggeser target, jadi ringkasan di bawah harus
            // menyebut total hari kerja yang benar, bukan angka default.
            'kasusBerat' => $pengaduan->kasus_berat,
            'kasusBeratAt' => $pengaduan->kasus_berat_at?->translatedFormat('d M Y, H:i'),
            'totalHariKerja' => $pengaduan->totalHariKerjaSla(),
            'tambahanHariKerja' => Sla::tambahanKasusBerat(),
            'keparahan' => $pengaduan->tingkat_keparahan,
        ];
    }

    /**
     * Berkas yang diunggah pelapor beserta jenis gambarnya.
     *
     * @return array<int, array{nama: string, url: string, gambar: bool}>
     */
    private static function lampiran(Pengaduan $pengaduan): array
    {
        return array_map(fn (string $path): array => [
            'nama' => $pengaduan->namaLampiran($path),
            'url' => $pengaduan->urlLampiran($path),
            'gambar' => str($path)->endsWith(['.jpg', '.jpeg', '.png']),
        ], $pengaduan->daftarLampiran());
    }

    /**
     * Percakapan pada kanal pelapor: pesan yang boleh dibaca di halaman lacak.
     *
     * Pesan kanal koordinasi humas dan unit sengaja tidak ikut, supaya admin
     * melihat bahan rahasia kerja internal tidak tercampur obrolan dengan
     * pelapor. Jawaban unit lama yang dulu satu percakapan tetap terbaca di
     * sini karena data ringkasan masih menaruhnya pada kanal pelapor.
     *
     * Lampiran selalu berupa daftar, walaupun kosong, supaya template cukup
     * melakukan foreach tanpa memeriksa null dulu.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function percakapan(Pengaduan $pengaduan): array
    {
        return $pengaduan->pesan
            ->filter(fn (PesanPengaduan $pesan): bool => $pesan->jalurPelapor())
            ->map(fn (PesanPengaduan $pesan): array => self::petakanPesan($pesan))
            ->values()
            ->all();
    }

    /**
     * Percakapan pada kanal koordinasi humas dan unit.
     *
     * Berisi jawaban PIC unit dan catatan admin humas yang tidak terlihat
     * oleh pelapor. Kanal ini yang ditampilkan pada Tab "Koordinasi Unit" di
     * konsol humas dan pada panel chat halaman disposisi sisi unit.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function koordinasi(Pengaduan $pengaduan): array
    {
        return $pengaduan->pesan
            ->filter(fn (PesanPengaduan $pesan): bool => $pesan->jalurUnit())
            ->map(fn (PesanPengaduan $pesan): array => self::petakanPesan($pesan))
            ->values()
            ->all();
    }

    /**
     * Bentuk satu pesan menjadi array yang siap digambar di template.
     *
     * @return array<string, mixed>
     */
    private static function petakanPesan(PesanPengaduan $pesan): array
    {
        return [
            // Key memakai dariHumas karena sapaan pembuka juga digambar di sisi kanan.
            'dariHumas' => $pesan->dariHumas(),
            // Dipecah sendiri supaya template bisa memberi label "PIC Unit"
            // tanpa menebak-nebak peran dari isi pesannya.
            'dariUnit' => $pesan->dariUnit(),
            // Peran mentah ikut dibawa karena satu label tiga kemungkinan
            // (pelapor, humas, unit) tidak bisa diturunkan dari dua penanda
            // di atas: sapaan pembuka memang terbaca sebagai pesan humas.
            'peran' => $pesan->peran,
            'isi' => $pesan->isi,
            'waktu' => $pesan->created_at->translatedFormat('d M Y, H:i'),
            'lampiran' => array_map(fn (string $path): array => [
                'nama' => basename($path),
                'url' => $pesan->urlLampiran($path),
                'gambar' => $pesan->lampiranGambar($path),
            ], $pesan->daftarLampiran()),
        ];
    }

    /**
     * Jejak audit perpindahan tahap pengaduan.
     *
     * Baris pertama riwayat selalu punya kolom dari yang kosong, itu sebabnya
     * kalimatnya dibedakan supaya tidak tertulis "dari tidak ada ke Diterima".
     *
     * @return array<int, array<string, mixed>>
     */
    private static function audit(Pengaduan $pengaduan): array
    {
        return $pengaduan->riwayatStatus
            ->map(function (RiwayatStatusPengaduan $baris): array {
                $keparahan = $baris->perubahanKeparahan();
                $kategori = $baris->perubahanKategori();

                return [
                    'sumber' => $baris->dariAwal() ? 'Sistem' : 'Humas',
                    'nada' => match (true) {
                        $kategori => 'bg-brand-100 text-brand-800',
                        $keparahan => 'bg-tertiary-container text-on-tertiary-container',
                        $baris->dariAwal() => 'bg-surface-container-highest text-on-surface',
                        default => 'bg-secondary-container text-on-secondary-container',
                    },
                    'ikon' => match (true) {
                        $kategori => 'category',
                        $keparahan => 'flag',
                        $baris->dariAwal() => 'inbox',
                        default => 'swap_horiz',
                    },
                    'waktu' => $baris->created_at->translatedFormat('d M Y, H:i').' WIB',
                    'judul' => match (true) {
                        $kategori => 'Kategori',
                        $keparahan => 'Keparahan',
                        default => $baris->ke->label(),
                    },
                    'kalimat' => $kategori || $keparahan
                        ? $baris->catatan
                        : ($baris->dariAwal()
                            ? 'Pengaduan masuk melalui portal publik dan langsung disimpan sebagai '.$baris->ke->label().'.'
                            : 'Tahap pengaduan dipindahkan dari '.$baris->dari->label().' ke '.$baris->ke->label().'.'),
                    // Kalimat kategori dan keparahan sudah memuat perubahannya,
                    // jadi tidak diulang lagi sebagai catatan di lembar disposisi.
                    'catatan' => $kategori || $keparahan ? null : $baris->catatan,
                    'aktor' => $baris->admin?->name ?? 'Sistem Pengaduan',
                    'kasusBerat' => $baris->perubahanKasusBerat(),
                    'keparahan' => $keparahan,
                    'kategori' => $kategori,
                ];
            })
            ->all();
    }

    /**
     * Log kegiatan kronologis satu tiket.
     *
     * Sumbernya dua tabel yang sudah ada: riwayat status untuk keputusan
     * humas dan pesan pengaduan untuk percakapan kedua kanal. Digabung di
     * sini supaya template tidak perlu menyortir dua daftar berbeda, dan
     * supaya halaman humas serta halaman unit membaca urutan yang sama.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function kegiatan(Pengaduan $pengaduan): array
    {
        $gabungan = [];

        foreach ($pengaduan->riwayatStatus->values() as $urutan => $baris) {
            $gabungan[] = [
                'kunci' => self::kunciUrut($baris->created_at, 0, $urutan),
                'baris' => self::kejadianRiwayat($baris),
            ];
        }

        foreach ($pengaduan->pesan->values() as $urutan => $pesan) {
            // Sapaan pembuka ditulis otomatis tepat saat tiket dibuat, jadi
            // barisnya hanya akan mengulang kejadian "tiket masuk" di atas.
            if ($pesan->peran === 'pembuka') {
                continue;
            }

            $gabungan[] = [
                'kunci' => self::kunciUrut($pesan->created_at, 1, $urutan),
                'baris' => self::kejadianPesan($pengaduan, $pesan),
            ];
        }

        usort($gabungan, fn (array $a, array $b): int => $a['kunci'] <=> $b['kunci']);

        return array_map(fn (array $item): array => $item['baris'], $gabungan);
    }

    /**
     * Log kegiatan versi halaman unit.
     *
     * Unit hanya perlu aktivitas yang menyangkut pekerjaannya. Percakapan
     * pelapor dengan humas dan perubahan tingkat keparahan sengaja dibuang:
     * keduanya urusan internal humas dan tidak pernah tampil di layar unit.
     *
     * @return array<int, array<string, mixed>>
     */
    public function kegiatanUnit(): array
    {
        return array_values(array_filter(
            $this->kegiatan,
            fn (array $baris): bool => $baris['untukUnit'],
        ));
    }

    /**
     * Satu baris riwayat status dalam bentuk log kegiatan.
     *
     * Urutan pemeriksaan penting: baris kategori, keparahan, dan kasus berat
     * semuanya memakai tahap asal dan tujuan yang sama, jadi penandanya harus
     * dibaca lebih dulu sebelum baris itu disebut disposisi biasa.
     *
     * @return array<string, mixed>
     */
    private static function kejadianRiwayat(RiwayatStatusPengaduan $baris): array
    {
        $aktor = $baris->admin?->name ?? 'Sistem Pengaduan';
        $catatan = $baris->catatan;

        if ($baris->perubahanKeparahan()) {
            return self::kejadian($baris->created_at, [
                'jenis' => 'Keparahan',
                'judul' => 'Tingkat keparahan diperbarui',
                'isi' => $catatan,
                'aktor' => $aktor,
                'ikon' => 'flag',
                'nada' => 'bg-tertiary-container text-on-tertiary-container',
                'untukUnit' => false,
            ]);
        }

        if ($baris->perubahanKategori()) {
            return self::kejadian($baris->created_at, [
                'jenis' => 'Kategori',
                'judul' => 'Kategori tiket diperbarui',
                'isi' => $catatan,
                'aktor' => $aktor,
                'ikon' => 'category',
                'nada' => 'bg-brand-100 text-brand-800',
            ]);
        }

        if ($baris->perubahanKasusBerat()) {
            $ditandai = $catatan === Pengaduan::CATATAN_KASUS_BERAT_AKTIF;

            return self::kejadian($baris->created_at, [
                'jenis' => 'Kasus Berat',
                'judul' => $ditandai ? 'Tiket ditandai sebagai kasus berat' : 'Penandaan kasus berat dilepas',
                'isi' => $ditandai
                    ? 'Tenggat tiket bertambah karena penanganannya butuh perhatian khusus.'
                    : 'Tiket kembali memakai tenggat kerja biasa.',
                'aktor' => $aktor,
                'ikon' => 'crisis_alert',
                'nada' => $ditandai
                    ? 'bg-error-container text-on-error-container'
                    : 'bg-surface-container-highest text-on-surface',
            ]);
        }

        if ($baris->is_nudge || str_starts_with((string) $catatan, 'Nudge manual ke unit:')) {
            return self::kejadian($baris->created_at, [
                'jenis' => 'Nudge',
                'judul' => 'Humas mengingatkan unit',
                // Prefiksnya hanya penanda jenis baris, jadi tidak diulang
                // lagi di badan log yang sudah punya judul sendiri.
                'isi' => str((string) $catatan)->after('Nudge manual ke unit: ')->value(),
                'aktor' => $aktor,
                'ikon' => 'notifications_active',
                'nada' => 'bg-gold-100 text-gold-800',
            ]);
        }

        if (str_starts_with((string) $catatan, 'Eskalasi ')) {
            return self::kejadian($baris->created_at, [
                'jenis' => 'Eskalasi',
                'judul' => 'Peringatan SLA otomatis',
                'isi' => $catatan,
                'aktor' => $aktor,
                'ikon' => 'notification_important',
                'nada' => 'bg-error-container text-on-error-container',
            ]);
        }

        if ($baris->dariAwal()) {
            return self::kejadian($baris->created_at, [
                'jenis' => 'Tiket Masuk',
                'judul' => 'Tiket pengaduan dibuat',
                'isi' => 'Pengaduan masuk melalui portal publik dan tercatat pada tahap '.$baris->ke->label().'.',
                'aktor' => $aktor,
                'ikon' => 'inbox',
                'nada' => 'bg-primary text-on-primary',
            ]);
        }

        if ($baris->dari === $baris->ke) {
            return self::kejadian($baris->created_at, [
                'jenis' => 'Disposisi',
                'judul' => 'Catatan penanganan humas',
                'isi' => $catatan ?? 'Penanganan tiket diperbarui oleh humas.',
                'aktor' => $aktor,
                'ikon' => 'swap_horiz',
                'nada' => 'bg-secondary-container text-on-secondary-container',
            ]);
        }

        return self::kejadian($baris->created_at, [
            'jenis' => 'Pindah Tahap',
            'judul' => 'Tahap: '.$baris->ke->label(),
            'isi' => filled($catatan)
                ? $catatan
                : 'Tahap pengaduan dipindahkan dari '.$baris->dari->label().' ke '.$baris->ke->label().'.',
            'aktor' => $aktor,
            'ikon' => 'swap_vert',
            'nada' => 'bg-secondary-container text-on-secondary-container',
        ]);
    }

    /**
     * Satu pesan percakapan dalam bentuk log kegiatan.
     *
     * Isi pesan dipotong karena log dibaca sebagai ringkasan; teks lengkapnya
     * tetap tersedia di tab percakapan masing-masing.
     *
     * @return array<string, mixed>
     */
    private static function kejadianPesan(Pengaduan $pengaduan, PesanPengaduan $pesan): array
    {
        $ringkas = str($pesan->isi)->squish()->limit(180)->value();
        $lampiran = count($pesan->daftarLampiran());

        if ($pesan->dariUnit()) {
            return self::kejadian($pesan->created_at, [
                'jenis' => 'Jawaban Unit',
                'judul' => 'PIC unit menjawab',
                'isi' => $ringkas,
                'aktor' => 'PIC Unit',
                'ikon' => 'reply',
                'nada' => 'bg-secondary-container text-on-secondary-container',
                'lampiran' => $lampiran,
            ]);
        }

        if ($pesan->jalurUnit()) {
            return self::kejadian($pesan->created_at, [
                'jenis' => 'Arahan ke Unit',
                'judul' => 'Humas mengirim pesan ke unit',
                'isi' => $ringkas,
                'aktor' => 'Admin Humas',
                'ikon' => 'campaign',
                'nada' => 'bg-tertiary-container text-on-tertiary-container',
                'lampiran' => $lampiran,
            ]);
        }

        if ($pesan->dariHumas()) {
            return self::kejadian($pesan->created_at, [
                'jenis' => 'Balasan Humas',
                'judul' => 'Humas membalas pelapor',
                'isi' => $ringkas,
                'aktor' => 'Admin Humas',
                'ikon' => 'support_agent',
                'nada' => 'bg-primary text-on-primary',
                'untukUnit' => false,
                'lampiran' => $lampiran,
            ]);
        }

        return self::kejadian($pesan->created_at, [
            'jenis' => 'Pesan Pelapor',
            'judul' => 'Pelapor mengirim pesan',
            'isi' => $ringkas,
            'aktor' => $pengaduan->nama_lengkap,
            'ikon' => 'waving_hand',
            'nada' => 'bg-surface-container-highest text-on-surface',
            'untukUnit' => false,
            'lampiran' => $lampiran,
        ]);
    }

    /**
     * Bentuk seragam satu baris log kegiatan.
     *
     * @param  array<string, mixed>  $isi
     * @return array<string, mixed>
     */
    private static function kejadian(CarbonInterface $waktu, array $isi): array
    {
        return array_merge([
            'waktu' => $waktu->translatedFormat('d M Y, H:i').' WIB',
            'lampiran' => 0,
            // Kegiatan bercatatan humas dianggap terlihat unit; pengecualian
            // keparahan dan percakapan pelapor ditulis eksplisit di atas.
            'untukUnit' => true,
        ], $isi);
    }

    /**
     * Kunci urut kronologis untuk menggabungkan dua sumber kejadian.
     *
     * Detik dipakai sebagai basis, kelompok memisahkan riwayat dari pesan,
     * dan urutan baris menjaga hasil tetap sama saat waktunya kebetulan sama.
     */
    private static function kunciUrut(CarbonInterface $waktu, int $kelompok, int $urutan): int
    {
        return $waktu->getTimestamp() * 1_000_000 + $kelompok * 100_000 + $urutan;
    }
}
