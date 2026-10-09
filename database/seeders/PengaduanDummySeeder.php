<?php

namespace Database\Seeders;

use App\Enums\KategoriPengaduan;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Pengaduan contoh bervolume besar untuk membuat portal dan konsol terlihat
 * hidup saat dikembangkan atau didemokan.
 *
 * Berbeda dengan PengaduanDemoSeeder yang isinya sengaja sedikit dan pas
 * untuk tiap kartu dashboard, seeder ini menebar ratusan tiket ke seluruh
 * unit dengan umur yang berbeda-beda. Tujuannya supaya tabel, paginasi,
 * tab status, dan zona SLA punya sebaran nyata, bukan beberapa baris yang
 * semuanya jatuh di hari yang sama.
 *
 * Isinya dibangkitkan secara acak, tetapi benih acaknya dipatok di awal
 * supaya `db:seed` berulang menghasilkan data yang kurang lebih sama dan
 * tidak ada tiket yang tiba-tiba berubah isi. Kode tiketnya unik per nomor,
 * jadi seeder ini aman dijalankan berkali-kali.
 */
class PengaduanDummySeeder extends Seeder
{
    /** Jumlah tiket yang dibangkitkan. Ubah satu angka ini bila ingin lebih banyak. */
    private const JUMLAH_TIKET = 180;

    /** Usia tiket paling lama dalam hari, dihitung mundur dari hari ini. */
    private const USIA_MAKSIMAL = 150;

    public function run(): void
    {
        // Benih yang tetap membuat urutan acak konsisten antar percobaan.
        mt_srand(20261010);

        $units = MasterUnit::query()->get();

        if ($units->isEmpty()) {
            $this->command?->warn('Master unit kosong; jalankan MasterUnitSeeder lebih dulu.');

            return;
        }

        for ($nomor = 1; $nomor <= self::JUMLAH_TIKET; $nomor++) {
            $kode = 'DUMMY-'.str_pad((string) $nomor, 4, '0', STR_PAD_LEFT);
            $unit = $units->random();
            $kategori = $this->pilihKategori();
            $kasusBerat = $kategori === KategoriPengaduan::Medis && mt_rand(1, 100) <= 8;
            $status = $this->pilihStatus($kasusBerat);
            $dibuat = $this->waktuDibuat();

            [$subjek, $deskripsi] = $this->cerita($kategori);

            $pengaduan = Pengaduan::updateOrCreate(
                ['kode_tiket' => $kode],
                [
                    'kategori' => $kategori,
                    'nama_lengkap' => $this->pilihNama(),
                    'nrm' => $this->nrmAcak(),
                    'no_wa' => '08'.$this->angka(11),
                    'email' => 'pelapor'.$nomor.'@example.test',
                    'alamat' => $this->alamatAcak(),
                    'waktu_kejadian' => $dibuat->copy()->subDays(mt_rand(1, 5))->setTime(mt_rand(6, 21), mt_rand(0, 59)),
                    'unit' => $unit->nama,
                    'master_unit_id' => $unit->id,
                    'subjek' => $subjek,
                    'deskripsi' => $deskripsi,
                    'lampiran' => [],
                    'status' => $status,
                    'kasus_berat' => $kasusBerat,
                    'kasus_berat_at' => $kasusBerat ? $dibuat->copy()->addDay() : null,
                    'draf_jawaban' => $this->drafJawaban($status),
                    'selesai_at' => null,
                ],
            );

            // Waktu terima ditulis ulang supaya SLA benar-benar dihitung dari
            // masa lalu. Tanpa ini semua tiket duduk di hari yang sama dan
            // kolom monitoring selalu tampak aman.
            $pengaduan->forceFill([
                'created_at' => $dibuat,
                'updated_at' => $dibuat,
            ])->saveQuietly();

            $selesai = $status->selesai() ? $this->waktuSelesai($dibuat) : null;

            if ($selesai !== null) {
                $pengaduan->forceFill(['selesai_at' => $selesai])->saveQuietly();
            }

            $this->isiPercakapan($pengaduan, $status, $dibuat, $selesai);
            $this->isiRiwayat($pengaduan, $status, $dibuat, $selesai);
        }

        $this->command?->info(self::JUMLAH_TIKET.' pengaduan dummy siap.');
    }

    /** Kategori diacak 55% fasilitas dan 45% medis agar keduanya terwakili. */
    private function pilihKategori(): KategoriPengaduan
    {
        return mt_rand(1, 100) <= 55
            ? KategoriPengaduan::Fasilitas
            : KategoriPengaduan::Medis;
    }

    /**
     * Sebaran status tiket biasa.
     *
     * Kasus berat sengaja tidak pernah ditutup cepat karena secara aturan
     * memang harus lewat telaah komite, jadi statusnya selalu berjalan.
     */
    private function pilihStatus(bool $kasusBerat): StatusPengaduan
    {
        if ($kasusBerat) {
            return mt_rand(1, 100) <= 70 ? StatusPengaduan::Diproses : StatusPengaduan::Revisi;
        }

        $undian = mt_rand(1, 100);

        return match (true) {
            $undian <= 25 => StatusPengaduan::Diterima,
            $undian <= 60 => StatusPengaduan::Diproses,
            $undian <= 70 => StatusPengaduan::Revisi,
            default => StatusPengaduan::Selesai,
        };
    }

    /** Waktu tiket masuk, tersebar pada 150 hari terakhir. */
    private function waktuDibuat(): Carbon
    {
        return Carbon::now()
            ->subDays(mt_rand(1, self::USIA_MAKSIMAL))
            ->setTime(mt_rand(7, 20), mt_rand(0, 59));
    }

    /**
     * Waktu tiket ditutup, diambil beberapa hari setelah tiket masuk.
     *
     * Batasnya tidak boleh melewati saat ini, kalau tidak tiket akan tercatat
     * selesai di masa depan dan zona SLA-nya jadi tidak masuk akal.
     */
    private function waktuSelesai(Carbon $dibuat): Carbon
    {
        $selesai = $dibuat->copy()->addWeekdays(mt_rand(2, 12));

        return $selesai->greaterThan(now())
            ? now()->subHours(mt_rand(1, 12))
            : $selesai;
    }

    /** Draf jawaban hanya masuk akal pada tiket yang masih berjalan. */
    private function drafJawaban(StatusPengaduan $status): ?string
    {
        if ($status->selesai() || mt_rand(1, 100) > 40) {
            return null;
        }

        return 'Terima kasih atas laporannya. Tim terkait sedang menelusuri '
            .'kejadian ini dan hasilnya akan kami sampaikan melalui halaman ini.';
    }

    /**
     * Isi percakapan sesuai seberapa jauh tiket berjalan.
     *
     * Pesan pembuka selalu ada karena setiap tiket baru meninggalkan sapaan
     * otomatis. Tiket yang sudah diproses menambah balasan humas, dan tiket
     * yang menunggu pelapor menambah pertanyaan kelengkapan. Jalur unit diisi
     * terpisah supaya koordinasi humas&harr;unit juga terlihat di detail.
     */
    private function isiPercakapan(Pengaduan $pengaduan, StatusPengaduan $status, Carbon $dibuat, ?Carbon $selesai): void
    {
        $pengaduan->pesan()->delete();

        $daftar = [
            [
                'kanal' => 'pelapor',
                'peran' => 'pembuka',
                'isi' => 'Terima kasih telah menyampaikan pengaduan. Laporan Anda '
                    .'sudah kami terima dengan nomor tiket '.$pengaduan->kode_tiket.'.',
                'waktu' => $dibuat->copy()->addHours(2),
            ],
        ];

        if (! $status->diterima()) {
            $daftar[] = [
                'kanal' => 'pelapor',
                'peran' => 'admin',
                'isi' => 'Laporan sudah kami teruskan ke '.$pengaduan->namaUnit()
                    .' untuk ditelusuri. Perkembangan akan kami kabarkan lewat halaman ini.',
                'waktu' => $dibuat->copy()->addDay(),
            ];
        }

        if ($status->perluAksi()) {
            $daftar[] = [
                'kanal' => 'pelapor',
                'peran' => 'admin',
                'isi' => 'Untuk melanjutkan pemeriksaan, mohon kirimkan foto bukti '
                    .'atau dokumen pendukung yang relevan melalui kolom balasan.',
                'waktu' => $dibuat->copy()->addDays(2),
            ];
            $daftar[] = [
                'kanal' => 'pelapor',
                'peran' => 'pelapor',
                'isi' => 'Baik, dokumennya saya siapkan. Terima kasih atas informasinya.',
                'waktu' => $dibuat->copy()->addDays(3),
            ];
        }

        if ($status->selesai()) {
            $daftar[] = [
                'kanal' => 'pelapor',
                'peran' => 'admin',
                'isi' => 'Terima kasih atas kesabarannya. Pengaduan ini sudah kami '
                    .'tindaklanjuti dan tiket ditutup. Semoga layanan kami ke depan lebih baik.',
                'waktu' => $selesai?->copy() ?? $dibuat->copy()->addDays(6),
            ];
        }

        if ($pengaduan->master_unit_id !== null && ! $status->diterima()) {
            $daftar[] = [
                'kanal' => 'unit',
                'peran' => 'admin',
                'isi' => 'Selamat pagi, mohon ditindaklanjuti pengaduan '
                    .$pengaduan->kode_tiket.' terkait '.$pengaduan->subjek.'.',
                'waktu' => $dibuat->copy()->addDay()->addHours(1),
            ];
            $daftar[] = [
                'kanal' => 'unit',
                'peran' => 'unit',
                'isi' => 'Noted, kami cek ke petugas jaga dan akan mengirim hasil '
                    .'investigasi hari ini juga.',
                'waktu' => $dibuat->copy()->addDay()->addHours(4),
            ];
        }

        foreach ($daftar as $pesan) {
            $pengaduan->pesan()->forceCreate([
                'kanal' => $pesan['kanal'],
                'peran' => $pesan['peran'],
                'isi' => $pesan['isi'],
                'created_at' => $pesan['waktu'],
                'updated_at' => $pesan['waktu'],
            ]);
        }
    }

    /**
     * Jejak audit tahap diisi manual karena event model dimatikan seeder.
     *
     * Urutan tahapnya mengikuti alur biasanya: Diterima lalu Diproses, dan
     * berakhir pada Revisi atau Selesai sesuai kondisi tiket.
     */
    private function isiRiwayat(Pengaduan $pengaduan, StatusPengaduan $status, Carbon $dibuat, ?Carbon $selesai): void
    {
        $pengaduan->riwayatStatus()->delete();

        $tahapan = [[StatusPengaduan::Diterima, null]];

        if (! $status->diterima()) {
            $tahapan[] = [StatusPengaduan::Diproses, 'Disposisi diteruskan ke '.$pengaduan->namaUnit().' untuk investigasi.'];
        }

        if ($status->perluAksi()) {
            $tahapan[] = [StatusPengaduan::Revisi, 'Dokumen pendukung diminta kembali dari pelapor.'];
        }

        if ($status->selesai()) {
            $tahapan[] = [StatusPengaduan::Selesai, 'Jawaban resmi diterbitkan dan tiket ditutup.'];
        }

        foreach ($tahapan as $urutan => [$tahap, $catatan]) {
            $waktu = $urutan === 0
                ? $dibuat
                : $dibuat->copy()->addDays($urutan * 2);

            if ($selesai !== null && $tahap->selesai()) {
                $waktu = $selesai;
            }

            $pengaduan->riwayatStatus()->forceCreate([
                'dari' => $urutan === 0 ? null : $tahapan[$urutan - 1][0],
                'ke' => $tahap,
                'catatan' => $catatan,
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ]);
        }
    }

    private function pilihNama(): string
    {
        $nama = [
            'Siti Rahmawati', 'Budi Santoso', 'Dewi Lestari', 'Ahmad Fauzi',
            'Rina Marlina', 'Agus Setiawan', 'Nur Aisyah', 'Joko Purnomo',
            'Sri Wahyuni', 'Andi Pratama', 'Maya Sari', 'Rizky Ramadhan',
            'Indah Permata', 'Hendra Gunawan', 'Lina Kusuma', 'Bambang Susilo',
            'Fitri Handayani', 'Eko Prasetyo', 'Yuni Astuti', 'Fajar Nugroho',
            'Ratna Dewi', 'Dedi Kurniawan', 'Novi Anggraini', 'Gunawan Saputra',
            'Wulan Sari', 'Hariyanto', 'Megawati Putri', 'Iwan Setiawan',
            'Dian Puspita', 'Arif Hidayat', 'Sinta Bella', 'Yusuf Maulana',
            'Ayu Ningsih', 'Rudi Hartono', 'Tatik Hidayati', 'Fauzan Akbar',
            'Lestari Widodo', 'Bayu Adi', 'Reni Oktavi', 'Slamet Riyadi',
        ];

        return $nama[array_rand($nama)];
    }

    private function alamatAcak(): string
    {
        $jalan = [
            'Jl. Darmo Permai III', 'Jl. Pahlawan', 'Jl. Rungkut Industri',
            'Jl. Nginden Intan Timur', 'Jl. Jagir Wonokromo', 'Jl. Mayjen Sungkono',
            'Jl. Diponegoro', 'Jl. Ahmad Yani', 'Jl. Basuki Rahmat',
            'Jl. Raya Gubeng', 'Jl. Kedungdoro', 'Jl. Manyar Kertoarjo',
            'Jl. Arif Rahman Hakim', 'Jl. Karang Menjangan', 'Jl. Airlangga',
            'Jl. Prof. Dr. Moestopo', 'Jl. Kertajaya', 'Jl. Kenjeran',
            'Jl. Bratang Binangun', 'Jl. Ketintang',
        ];

        return $jalan[array_rand($jalan)].' no. '.mt_rand(1, 120).', Surabaya';
    }

    private function nrmAcak(): string
    {
        return $this->angka(2).'-'.$this->angka(2).'-'.$this->angka(2).'-'.$this->angka(2);
    }

    /** Rangkaian angka acak sepanjang $panjang, boleh diawali nol. */
    private function angka(int $panjang): string
    {
        $hasil = '';

        for ($i = 0; $i < $panjang; $i++) {
            $hasil .= (string) mt_rand(0, 9);
        }

        return $hasil;
    }

    /**
     * Sepasang subjek dan deskripsi sesuai kategori pengaduan.
     *
     * @return array{0: string, 1: string}
     */
    private function cerita(KategoriPengaduan $kategori): array
    {
        $fasilitas = [
            ['AC ruang tunggu poliklinik tidak dingin', 'Pendingin ruangan di ruang tunggu poliklinik tidak berfungsi sejak beberapa hari terakhir sehingga pasien yang menunggu merasa gerah dan tidak nyaman.'],
            ['Antrean pendaftaran pasien terlalu lama', 'Antrean pendaftaran di loket pagi berjalan lambat dan pasien harus menunggu lebih dari satu jam sebelum mendapat nomor poliklinik.'],
            ['Toilet lantai dua kotor dan tidak ada air', 'Toilet umum di lantai dua dalam kondisi kotor, air keran tidak mengalir, dan tidak tersedia sabun maupun tisu sejak pagi.'],
            ['Lift gedung utama sering macet', 'Lift gedung utama beberapa kali berhenti di tengah perjalanan sehingga pasien dan pengantar harus memakai tangga.'],
            ['Parkir kendaraan tidak tertib', 'Area parkir rumah sakit penuh dan kendaraan diparkir sembarangan hingga menutup jalur pejalan kaki pasien.'],
            ['Rambu petunjuk arah kurang jelas', 'Papan petunjuk arah menuju instalasi rawat jalan sulit terlihat sehingga pasien baru sering tersesat mencari lokasi.'],
            ['Loket kasir tutup lebih awal', 'Loket pembayaran sudah tutup padahal jam layanan belum berakhir, sehingga pasien harus kembali keesokan harinya.'],
            ['Ruang tunggu kurang tempat duduk', 'Kursi tunggu di depan instalasi tidak mencukupi sehingga banyak pasien lanjut usia harus berdiri sambil menunggu.'],
            ['Kebersihan koridor lantai tiga', 'Koridor lantai tiga berdebu dan sampah di tempat sampah menumpuk karena baru dikosongkan menjelang sore.'],
            ['Waktu layanan apotek terlalu lama', 'Penyerahan obat di apotek memakan waktu hampir dua jam padahal resep sudah diserahkan sejak pagi.'],
        ];

        $medis = [
            ['Obat tidak sesuai resep dokter', 'Obat yang diserahkan di apotek berbeda dengan yang tertulis pada resep dokter, sehingga pasien ragu untuk meminumnya.'],
            ['Dokter datang terlambat dari jadwal', 'Dokter spesialis baru memulai praktik lebih dari satu jam setelah jadwal yang tertera di loket pendaftaran.'],
            ['Perawat kurang komunikatif saat tindakan', 'Perawat melakukan tindakan tanpa menjelaskan prosedurnya lebih dahulu sehingga pasien merasa cemas dan tidak siap.'],
            ['Informasi hasil laboratorium tidak jelas', 'Hasil pemeriksaan laboratorium diserahkan tanpa penjelasan dan pasien tidak diarahkan ke dokter mana untuk membacanya.'],
            ['Dugaan salah pemberian obat', 'Terdapat dugaan pemberian obat yang tertukar pada pasien rawat inap dan keluarga meminta kejelasan prosedur yang berlaku.'],
            ['Edukasi terapi kurang lengkap', 'Penjelasan aturan minum obat dan efek sampingnya sangat singkat sehingga pasien tidak paham cara memakai obatnya.'],
            ['Keterlambatan visitasi DPJP', 'Dokter penanggung jawab pasien tidak melakukan kunjungan sesuai waktu yang dijanjikan kepada keluarga pasien.'],
            ['Hasil radiologi tertukar', 'Hasil foto radiologi yang diterima tidak sesuai dengan pemeriksaan yang diminta sehingga harus diulang.'],
            ['Tindakan medis tanpa penjelasan risiko', 'Tindakan medis dilakukan tanpa memberi penjelasan risiko dan persetujuan tertulis dari pasien terlebih dahulu.'],
            ['Rujukan internal antar poli tidak jelas', 'Rujukan dari satu poliklinik ke poliklinik lain tidak diproses sehingga pasien harus mengulang pendaftaran dari awal.'],
        ];

        return $kategori === KategoriPengaduan::Medis
            ? $medis[array_rand($medis)]
            : $fasilitas[array_rand($fasilitas)];
    }
}
