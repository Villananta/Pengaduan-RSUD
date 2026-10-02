<?php

namespace App\Support;

use App\Enums\StatusInvestigasi;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use App\Models\RiwayatStatusPengaduan;
use Illuminate\Support\Collection;

/**
 * Data tampilan untuk halaman Workspace & Detail satu tiket pengaduan.
 *
 * Halaman ini menumpuk banyak bagian sekaligus: identitas tiket, posisi
 * tahap pengaduan, status investigasi unit, posisi SLA, percakapan dengan
 * pelapor, dan jejak audit. Semua query dan penyusunan kalimat ditahan di
 * sini supaya template hanya membungkus data yang sudah jadi.
 *
 * Bagian yang belum punya sumber data sengaja tidak dikarang di sini.
 * Menandai kasus berat, mengirim jawaban resmi, dan disposisi ke unit belum
 * punya kolom maupun endpoint, jadi template menampilkannya sebagai kontrol
 * nonaktif alih-alih mengirim data palsu ke server.
 */
final class DetailPengaduan
{
    /**
     * @param  array<int, array<string, mixed>>  $pilar  Stepper empat tahap pengaduan.
     * @param  array<string, mixed>  $unit  Status investigasi unit pada tiket ini.
     * @param  array<string, mixed>  $sla  Posisi waktu kerja terhadap target.
     * @param  array<int, array<string, mixed>>  $lampiran  Berkas yang diunggah pelapor.
     * @param  array<int, array<string, mixed>>  $percakapan  Pesan antara pelapor dan humas.
     * @param  array<int, array<string, mixed>>  $audit  Jejak perpindahan tahap.
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
        public readonly array $audit,
        public readonly Collection $pilihanUnit,
    ) {}

    /**
     * Susun halaman detail dari kode tiket.
     *
     * Kode tiket sudah unik jadi cukup jadi kunci pencarian; tiket yang tidak
     * ada dibiarkan melempar 404 supaya admin tidak melihat halaman kosong
     * untuk tiket yang dihapus.
     */
    public static function dariKode(string $kode): self
    {
        $pengaduan = Pengaduan::query()
            ->with(['masterUnit', 'pesan', 'riwayatStatus.admin'])
            ->where('kode_tiket', $kode)
            ->firstOrFail();

        $sudahDibalas = $pengaduan->pesan->contains(
            fn (PesanPengaduan $pesan): bool => $pesan->dariAdmin()
        );

        $investigasi = StatusInvestigasi::dariPengaduan($pengaduan, $sudahDibalas);

        return new self(
            pengaduan: $pengaduan,
            investigasi: $investigasi,
            pilar: self::pilar($pengaduan),
            unit: self::unit($pengaduan, $investigasi),
            sla: self::sla($pengaduan),
            lampiran: self::lampiran($pengaduan),
            percakapan: self::percakapan($pengaduan),
            audit: self::audit($pengaduan),
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
     * Draf jawaban admin yang belum pernah dikirim.
     *
     * Disimpan sebagai nilai terpisah supaya template tidak perlu membaca
     * kolom draf_jawaban langsung.
     */
    public function draf(): ?string
    {
        return $this->pengaduan->draf_jawaban;
    }

    /**
     * Tindakan yang boleh dijalankan pada tiket ini.
     *
     * Disalin dari App\Support\TindakLanjutPengaduan supaya template punya
     * satu sumber kebenaran untuk menentukan tombol aktif dan alasannya.
     *
     * @return array{tutup: bool, kembalikan: bool, alasanTutup: ?string, alasanKembalikan: ?string}
     */
    public function tindakan(): array
    {
        return TindakLanjutPengaduan::tindakanTersedia($this->pengaduan);
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
        return TindakLanjutPengaduan::tujuanTersedia($this->pengaduan);
    }

    /**
     * Label, ikon, dan warna tombol untuk satu tahap tujuan.
     *
     * @return array{label: string, ikon: string, warna: string}
     */
    public function tombolTujuan(StatusPengaduan $tujuan): array
    {
        return TindakLanjutPengaduan::tombolTujuan($tujuan);
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
                    ? $masukTahap[$tahap->value]->format('d M Y, H:i')
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
                'nama' => $pengaduan->unit,
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
            'hariKe' => Sla::hariKerjaLewat($pengaduan->created_at),
            'sisa' => $pengaduan->sisaHariSla(),
            'target' => $pengaduan->targetSla()->format('d M Y'),
            'persen' => $pengaduan->progresPersen(),
            'zona' => $zona->label(),
            'nadaZona' => $zona->badgeAdmin(),
            'ringkas' => $pengaduan->ringkasSla(),
            'lewatUnit' => Sla::lewatInvestigasi($pengaduan->created_at),
            'hariInvestigasi' => Sla::hariInvestigasi(),

            // Kasus berat menggeser target, jadi ringkasan di bawah harus
            // menyebut total hari kerja yang benar, bukan angka default.
            'kasusBerat' => $pengaduan->kasus_berat,
            'kasusBeratAt' => $pengaduan->kasus_berat_at?->format('d M Y, H:i'),
            'totalHariKerja' => $pengaduan->totalHariKerjaSla(),
            'tambahanHariKerja' => Sla::tambahanKasusBerat(),
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
     * Percakapan antara pelapor dan admin humas.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function percakapan(Pengaduan $pengaduan): array
    {
        return $pengaduan->pesan
            ->map(fn (PesanPengaduan $pesan): array => [
                'dariAdmin' => $pesan->dariAdmin(),
                'isi' => $pesan->isi,
                'waktu' => $pesan->created_at->format('d M Y, H:i'),
                'lampiran' => $pesan->adaLampiran() ? [
                    'nama' => basename($pesan->lampiran),
                    'url' => $pesan->urlLampiran(),
                    'gambar' => $pesan->lampiranGambar(),
                ] : null,
            ])
            ->all();
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
            ->map(fn (RiwayatStatusPengaduan $baris): array => [
                'sumber' => $baris->dariAwal() ? 'Sistem' : 'Humas',
                'nada' => $baris->dariAwal()
                    ? 'bg-surface-container-highest text-on-surface'
                    : 'bg-secondary-container text-on-secondary-container',
                'ikon' => $baris->dariAwal() ? 'inbox' : 'swap_horiz',
                'waktu' => $baris->created_at->format('d M Y, H:i').' WIB',
                'judul' => $baris->ke->label(),
                'kalimat' => $baris->dariAwal()
                    ? 'Pengaduan masuk melalui portal publik dan langsung disimpan sebagai '.$baris->ke->label().'.'
                    : 'Tahap pengaduan dipindahkan dari '.$baris->dari->label().' ke '.$baris->ke->label().'.',
                'catatan' => $baris->catatan,
                'aktor' => $baris->admin?->name ?? 'Sistem Pengaduan',
            ])
            ->all();
    }
}
