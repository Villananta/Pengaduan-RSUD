<?php

namespace Database\Seeders;

use App\Enums\DisposisiUnit;
use App\Enums\KategoriPengaduan;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Pengaduan contoh untuk mengisi konsol admin saat diuji atau didemokan.
 *
 * Isinya disengaja beragam supaya tiap kartu dashboard punya polanya
 * sendiri: ada tiket yang baru masuk, tiket yang sudah lewat SLA, tiket
 * kasus berat, dan tiket yang sudah selesai. Dengan begitu perhitungan SLA,
 * stepper, dan panel keputusan bisa langsung dicoba.
 *
 * Seeder ini idempoten lewat kode tiket, jadi `db:seed` boleh dijalankan
 * berulang tanpa menghasilkan tiket ganda.
 */
class PengaduanDemoSeeder extends Seeder
{
    public function run(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->first()
            ?? MasterUnit::query()->firstOrFail();

        foreach ($this->data() as $baris) {
            $pengaduan = Pengaduan::updateOrCreate(
                ['kode_tiket' => $baris['kode_tiket']],
                $baris['atribut'] + [
                    'unit' => $unit->nama,
                    'master_unit_id' => $unit->id,
                ],
            );

            // Waktu tiket diterima ditulis ulang supaya SLA-nya benar-benar
            // dihitung dari masa lalu. Tanpa ini semua tiket contoh berstatus
            // baru pada hari seeding dan tidak ada yang terlihat lewat SLA.
            $pengaduan->forceFill([
                'created_at' => $baris['diterima_pada'],
                'updated_at' => $baris['diterima_pada'],
            ])->saveQuietly();

            $this->isiPercakapan($pengaduan, $baris['percakapan']);
            $this->isiRiwayat($pengaduan, $baris['riwayat']);
        }

        $unit->update(['disposisi' => DisposisiUnit::SedangInvestigasi]);
    }

    /**
     * Susunan tiket contoh beserta kronologi dan jawabannya.
     *
     * @return array<int, array<string, mixed>>
     */
    private function data(): array
    {
        $hariIni = Carbon::today();

        return [
            [
                'kode_tiket' => 'DEMO-BARU-0001',
                'diterima_pada' => $hariIni->copy()->subDays(1)->setTime(8, 0),
                'atribut' => [
                    'kategori' => KategoriPengaduan::Medis,
                    'nama_lengkap' => 'Siti Rahmawati',
                    'nrm' => '12-34-56-78',
                    'no_wa' => '081234567891',
                    'email' => 'siti.rahmawati@example.test',
                    'alamat' => 'Jl. Darmo Permai III no. 45, Surabaya',
                    'waktu_kejadian' => $hariIni->copy()->subDays(2)->setTime(9, 30),
                    'subjek' => 'Obat tidak diterima sesuai resep',
                    'deskripsi' => 'Resep obat sudah ditandatangani dokter, tetapi obatnya '
                        .'tidak ditemukan di loket Apotik sejak pagi. Sudah dua kali antre '
                        .'tanpa penjelasan dari petugas.',
                    'lampiran' => [],
                    'status' => StatusPengaduan::Diterima,
                    'kasus_berat' => false,
                    'kasus_berat_at' => null,
                    'draf_jawaban' => null,
                ],
                'percakapan' => [
                    ['admin', 'Terima kasih atas laporannya. Pengaduan ini sedang kami teruskan ke Instalasi Farmasi Pusat untuk ditelusuri.'],
                ],
                'riwayat' => [
                    [StatusPengaduan::Diterima, null],
                ],
            ],
            [
                'kode_tiket' => 'DEMO-PROSES-0002',
                'diterima_pada' => $hariIni->copy()->subDays(5)->setTime(10, 30),
                'atribut' => [
                    'kategori' => KategoriPengaduan::Fasilitas,
                    'nama_lengkap' => 'Budi Santoso',
                    'nrm' => '23-45-67-89',
                    'no_wa' => '082345678912',
                    'email' => 'budi.santoso@example.test',
                    'alamat' => 'Jl. Pahlawan no. 12, Surabaya',
                    'waktu_kejadian' => $hariIni->copy()->subDays(6)->setTime(14, 15),
                    'subjek' => 'Toilet poliklinik tersumbat dan tidak ada air',
                    'deskripsi' => 'Satu kamar toilet poliklinik tersumbat dan tidak mengalirkan air. '
                        .'Kondisi ini mengganggu pasien yang sedang menunggu giliran pemeriksaan.',
                    'lampiran' => [],
                    'status' => StatusPengaduan::Diproses,
                    'kasus_berat' => false,
                    'kasus_berat_at' => null,
                    'draf_jawaban' => 'Terima kasih atas laporannya. Tim Sarana dan Prasarana sudah menjadwalkan '
                        .'perbaikan pada hari yang sama. Mohon maaf atas ketidaknyamanannya.',
                ],
                'percakapan' => [
                    ['pelapor', 'Sudah saya laporkan di loket, tetapi petugas piket meminta menunggu.'],
                    ['admin', 'Laporan sudah kami terima dan diteruskan ke bagian Sarana dan Prasarana.'],
                    ['admin', 'Mohon menunggu hasil perbaikan. Perkembangan akan kami kabarkan lewat halaman ini.'],
                ],
                'riwayat' => [
                    [StatusPengaduan::Diterima, null],
                    [StatusPengaduan::Diproses, 'Disposisi diberikan ke Instalasi Rawat Jalan untuk investigasi lapangan.'],
                ],
            ],
            [
                'kode_tiket' => 'DEMO-LEWAT-0003',
                'diterima_pada' => $hariIni->copy()->subDays(22)->setTime(9, 0),
                'atribut' => [
                    'kategori' => KategoriPengaduan::Fasilitas,
                    'nama_lengkap' => 'Dewi Lestari',
                    'nrm' => '34-56-78-90',
                    'no_wa' => '083456789013',
                    'email' => 'dewi.lestari@example.test',
                    'alamat' => 'Jl. Rungkut Industri no. 8, Surabaya',
                    'waktu_kejadian' => $hariIni->copy()->subDays(24)->setTime(8, 5),
                    'subjek' => 'Rujukan gagal dipakai saat pendaftaran',
                    'deskripsi' => 'Rujukan dari PoliklinikPenyakit Dalam tidak terbaca di loket '
                        .'Pemeriksaan sehingga pasien harus antre ulang dari awal. '
                        .'Kejadian ini sudah terjadi selama tiga hari terakhir.',
                    'lampiran' => [],
                    'status' => StatusPengaduan::Revisi,
                    'kasus_berat' => false,
                    'kasus_berat_at' => null,
                    'draf_jawaban' => null,
                ],
                'percakapan' => [
                    ['pelapor', 'Sudah lima kali saya mencoba memakai rujukan, tetap gagal terbaca.'],
                    ['admin', 'Mohon maaf atas ketidaknyamanannya. Tim TI sedang menelusuri kendalanya.'],
                ],
                'riwayat' => [
                    [StatusPengaduan::Diterima, null],
                    [StatusPengaduan::Diproses, 'Tim TI sedang menelusuri gangguan pembacaan rujukan.'],
                    [StatusPengaduan::Revisi, 'Dokumen rujukan ditanyakan kembali karena barcode gagal dibaca.'],
                ],
            ],
            [
                'kode_tiket' => 'DEMO-BERAT-0004',
                'diterima_pada' => $hariIni->copy()->subDays(7)->setTime(20, 15),
                'atribut' => [
                    'kategori' => KategoriPengaduan::Medis,
                    'nama_lengkap' => 'Ahmad Fauzi',
                    'nrm' => '45-67-89-01',
                    'no_wa' => '084567890124',
                    'email' => 'ahmad.fauzi@example.test',
                    'alamat' => 'Jl. Nginden Intan Timur no. 3, Surabaya',
                    'waktu_kejadian' => $hariIni->copy()->subDays(9)->setTime(19, 40),
                    'subjek' => 'Dugaan kecelkahan: salah obat diberikan di ruang ZZ',
                    'deskripsi' => 'Pengaduan ini menyangkut keselamatan pasien sehingga sesuai '
                        .'Protokol Keselamatan Pasien harus melalui telaah Komite Etik dan '
                        .'pemeriksaan ulang sebelum tiket dapat ditutup.',
                    'lampiran' => [],
                    'status' => StatusPengaduan::Diproses,
                    'kasus_berat' => true,
                    'kasus_berat_at' => $hariIni->copy()->subDays(8)->setTime(10, 0),
                ],
                'percakapan' => [
                    ['admin', 'Laporan ini masuk ke jalur telaah Komite Etik. Mohon maaf atas kejadian yang Anda alami.'],
                ],
                'riwayat' => [
                    [StatusPengaduan::Diterima, null],
                    [StatusPengaduan::Diproses, 'Kejadian menyangkut keselamatan pasien sehingga perlu telaah Komite Etik.'],
                ],
            ],
            [
                'kode_tiket' => 'DEMO-SELESAI-0005',
                'diterima_pada' => $hariIni->copy()->subDays(15)->setTime(9, 45),
                'atribut' => [
                    'kategori' => KategoriPengaduan::Fasilitas,
                    'nama_lengkap' => 'Rina Marlina',
                    'nrm' => '56-78-90-12',
                    'no_wa' => '085678901235',
                    'email' => 'rina.marlina@example.test',
                    'alamat' => 'Jl. Jagir Wonokromo no. 21, Surabaya',
                    'waktu_kejadian' => $hariIni->copy()->subDays(18)->setTime(11, 20),
                    'subjek' => 'Jam praktik dokter terlambat dimulai',
                    'deskripsi' => 'Jam praktik klinik dimulai satu jam lebih lambat dari jadwal yang '
                        .'ditempel di loket pendaftaran sehingga jadwal pasien menjadi membingungkan.',
                    'lampiran' => [],
                    'status' => StatusPengaduan::Selesai,
                    'kasus_berat' => false,
                    'kasus_berat_at' => null,
                    'draf_jawaban' => null,
                ],
                'percakapan' => [
                    ['admin', 'Jadwal yang tertempel sudah kami perbaiki dan petugas filling jadwal kami wajibkan setiap hari.'],
                ],
                'riwayat' => [
                    [StatusPengaduan::Diterima, null],
                    [StatusPengaduan::Diproses, 'Jadwal praktik sudah dikonfirmasi ke Poliklinik.'],
                    [StatusPengaduan::Selesai, 'Jadwal diperbarui dan hasilnya sudah disampaikan ke pelapor.'],
                ],
            ],
        ];
    }

    /** Isi ulang percakapan agar `db:seed` berulang tidak menggandakan pesan. */
    private function isiPercakapan(Pengaduan $pengaduan, array $percakapan): void
    {
        $pengaduan->pesan()->delete();

        foreach ($percakapan as $urutan => [$peran, $isi]) {
            $dikirim = $pengaduan->created_at?->copy()->addDays($urutan + 1);

            $pengaduan->pesan()->forceCreate([
                'peran' => $peran,
                'isi' => $isi,
                'created_at' => $dikirim,
                'updated_at' => $dikirim,
            ]);
        }
    }

    /**
     * Riwayat diisi manual karena DatabaseSeeder mematikan event model.
     *
     * Event `created` Pengaduan tidak berjalan di dalam seeder, sehingga baris
     * riwayat awal harus dibuat sendiri supaya halaman detail punya langkah
     * pertama dan tidak tampak kosong.
     */
    private function isiRiwayat(Pengaduan $pengaduan, array $riwayat): void
    {
        $pengaduan->riwayatStatus()->delete();

        $diterimaPada = $pengaduan->created_at ?? now();
        $terakhir = null;

        foreach ($riwayat as $urutan => [$tahap, $catatan]) {
            $terakhir = $diterimaPada->copy()->addDays($urutan * 2);

            $pengaduan->riwayatStatus()->forceCreate([
                'dari' => $urutan === 0 ? null : $riwayat[$urutan - 1][0],
                'ke' => $tahap,
                'catatan' => $catatan,
                'created_at' => $terakhir,
                'updated_at' => $terakhir,
            ]);
        }

        // Waktu penyelesaian diambil dari baris riwayat terakhir. Kalau diisi
        // dengan waktu sekarang, zona SLA tiket selesai selalu terlihat hijau
        // padahal tiketnya sudah lama ditutup.
        if ($pengaduan->status->selesai()) {
            $pengaduan->forceFill([
                'selesai_at' => $terakhir ?? $diterimaPada,
            ])->saveQuietly();
        }
    }
}
