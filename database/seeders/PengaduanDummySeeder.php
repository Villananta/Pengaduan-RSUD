<?php

namespace Database\Seeders;

use App\Enums\KanalPengaduan;
use App\Enums\KategoriPengaduan;
use App\Enums\StatusPengaduan;
use App\Enums\TingkatKeparahan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\User;
use App\Support\Sla;
use App\Support\StatistikDashboard;
use App\Support\StatistikPengaduan;
use App\Support\StatistikUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * 150 pengaduan contoh yang disebar rata ke seluruh fitur konsol.
 *
 * Komposisi tiap dimensi ditetapkan lebih dulu, bukan diundi satu per satu,
 * supaya angka pada chip filter, kartu dashboard, dan tab investigasi selalu
 * sama setiap kali seeder dijalankan. Tiap unit mendapat porsi tiket yang
 * sama besar, dan semua pilihan status, kategori, keparahan, kanal, serta
 * zona SLA kebagian isi.
 *
 * Benih acaknya dipatok agar nama, cerita, dan tanggal tidak berubah antar
 * percobaan. Lampiran dan notifikasi lonceng sengaja tidak dibuat di sini:
 * keduanya menambah berkas dan baris yang tidak diperlukan untuk menguji
 * tampilan daftar.
 */
class PengaduanDummySeeder extends Seeder
{
    private const JUMLAH_TIKET = 150;

    /** Jatah tiket bertaut unit untuk tiap instalasi. */
    private const TIKET_PER_UNIT = 15;

    /** Tiket tanpa unit agar tab "Ditangani Langsung Humas" ada isinya. */
    private const TIKET_LANGSUNG_HUMAS = 15;

    private const JUMLAH_KASUS_BERAT = 12;

    public function run(): void
    {
        // Benih tetap: urutan acak dan isi cerita konsisten antar percobaan.
        mt_srand(20261106);

        $units = MasterUnit::query()->where('aktif', true)->orderBy('id')->get();

        if ($units->isEmpty()) {
            $this->command?->warn('Master unit kosong; jalankan MasterUnitSeeder lebih dulu.');

            return;
        }

        $admin = User::query()->where('peran', 'admin')->orderBy('id')->first();

        $rencana = $this->rencana($units);
        $this->sebarKategori($rencana);
        $this->sebarKanal($rencana);
        $this->tandaiKasusBerat($rencana);
        $this->sebarKeparahan($rencana);

        // Urutan baris diacak supaya daftar tidak mengelompok per unit atau
        // per status. Semua dimensi sudah menempel pada barisnya, jadi
        // pengacakan tidak mengubah komposisi.
        shuffle($rencana);

        $jumlahSelesai = $this->hitungBaris(
            $rencana,
            fn (array $baris): bool => $baris['status']->selesai(),
        );

        $jumlahTerlambat = $this->hitungBaris(
            $rencana,
            fn (array $baris): bool => ! $baris['status']->selesai() && $baris['zona'] === 'terlambat',
        );

        $hitung = ['selesai' => 0, 'terlambat' => 0];
        $kodeTerpakai = [];

        foreach ($rencana as $nomor => $baris) {
            $kasusBerat = $baris['kasus_berat'];
            $unit = $baris['unit'];

            [$dibuat, $selesai] = $this->tanggal($baris, $hitung, $jumlahSelesai, $jumlahTerlambat);

            $kode = $this->kodeTiket($dibuat, $kodeTerpakai);
            [$terverifikasi, $otp, $otpKedaluwarsa] = $this->statusEmail($nomor, $dibuat);
            [$token, $tokenKedaluwarsa] = $this->tokenAkses($kode, $dibuat);
            [$subjek, $deskripsi] = $this->cerita($baris['kategori']);

            $pengaduan = Pengaduan::updateOrCreate(
                ['kode_tiket' => $kode],
                [
                    'kategori' => $baris['kategori'],
                    'nama_lengkap' => $this->pilihNama(),
                    'nrm' => $this->nrmAcak(),
                    'no_wa' => '08'.$this->angka(10),
                    'email' => 'pelapor'.$nomor.'@example.test',
                    'email_verified_at' => $terverifikasi,
                    'email_otp' => $otp,
                    'email_otp_expires_at' => $otpKedaluwarsa,
                    'alamat' => $this->alamatAcak(),
                    'waktu_kejadian' => $dibuat->copy()->subDays(mt_rand(1, 5))->setTime(mt_rand(6, 21), mt_rand(0, 59)),
                    'unit' => $unit?->nama ?? 'Hubungan Masyarakat',
                    'master_unit_id' => $unit?->id,
                    'subjek' => $subjek,
                    'deskripsi' => $deskripsi,
                    'kanal' => $baris['kanal'],
                    'lampiran' => [],
                    'status' => $baris['status'],
                    'kasus_berat' => $kasusBerat,
                    'tingkat_keparahan' => $baris['keparahan'],
                    'draf_jawaban' => $this->drafJawaban($baris['status'], $nomor),
                    'akses_token' => $token,
                    'akses_token_expires_at' => $tokenKedaluwarsa,
                ],
            );

            // Waktu terima, waktu tutup, dan penanda kasus berat ditulis
            // terpisah karena tidak masuk daftar kolom yang boleh diisi
            // massal.
            $pengaduan->forceFill([
                'created_at' => $dibuat,
                'updated_at' => $selesai ?? $dibuat->copy()->addHours(6),
                'selesai_at' => $selesai,
                'kasus_berat_at' => $kasusBerat ? $dibuat->copy()->addDay() : null,
            ])->saveQuietly();

            $this->isiPercakapan($pengaduan, $baris['status'], $baris['jawaban_unit'], $dibuat, $selesai);
            $this->isiRiwayat($pengaduan, $baris, $dibuat, $selesai, $admin?->id, $nomor);
        }

        StatistikDashboard::lupaCache();
        StatistikPengaduan::lupaCache();
        StatistikUnit::lupaCache();

        $this->command?->info(self::JUMLAH_TIKET.' pengaduan dummy siap.');
    }

    /**
     * Susun komposisi tiket sebelum diacak.
     *
     * Lima belas tiket per unit dipakai supaya kesembilan instalasi
     * mendapat porsi yang sama. Lima belas tiket sisanya tidak ditautkan
     * ke unit mana pun agar tab investigasi "Ditangani Langsung Humas"
     * ikut terisi.
     *
     * @param  Collection<int, MasterUnit>  $units
     * @return array<int, array<string, mixed>>
     */
    private function rencana(Collection $units): array
    {
        // Tiap unit memakai pola yang sama: 3 menunggu, 4 diproses,
        // 4 perlu revisi, dan 4 selesai. Di dalamnya sudah ditentukan
        // zona SLA dan apakah unit sudah membalas, sehingga susunan
        // tab investigasi tiap unit juga sebanding.
        $pola = [
            [StatusPengaduan::Diterima, 'tepat', false],
            [StatusPengaduan::Diterima, 'tepat', false],
            [StatusPengaduan::Diterima, 'tepat', false],
            [StatusPengaduan::Diproses, 'tepat', true],
            [StatusPengaduan::Diproses, 'mendek', true],
            [StatusPengaduan::Diproses, 'mendek', false],
            [StatusPengaduan::Diproses, 'terlambat', false],
            [StatusPengaduan::Revisi, 'tepat', true],
            [StatusPengaduan::Revisi, 'mendek', true],
            [StatusPengaduan::Revisi, 'mendek', false],
            [StatusPengaduan::Revisi, 'terlambat', false],
            [StatusPengaduan::Selesai, 'tepat', true],
            [StatusPengaduan::Selesai, 'tepat', true],
            [StatusPengaduan::Selesai, 'terlambat', true],
            [StatusPengaduan::Selesai, 'terlambat', true],
        ];

        $rencana = [];

        foreach ($units as $unit) {
            foreach ($pola as [$status, $zona, $jawabanUnit]) {
                $rencana[] = [
                    'status' => $status,
                    'unit' => $unit,
                    'zona' => $zona,
                    'jawaban_unit' => $jawabanUnit,
                    'kategori' => null,
                    'kanal' => null,
                    'keparahan' => null,
                    'kasus_berat' => false,
                ];
            }
        }

        // Sebelas tiket Diterima dipindahkan ke jalur humas supaya total
        // tiap tahap tetap 38/38/37/37. Dua tiket Diproses tanpa unit
        // dipakai agar kartu "diproses humas" pada dashboard tidak nol.
        $langsungHumas = array_merge(
            array_fill(0, 11, [StatusPengaduan::Diterima, 'tepat']),
            [
                [StatusPengaduan::Diproses, 'tepat'],
                [StatusPengaduan::Diproses, 'terlambat'],
                [StatusPengaduan::Revisi, 'terlambat'],
                [StatusPengaduan::Selesai, 'tepat'],
            ],
        );

        foreach ($langsungHumas as [$status, $zona]) {
            $rencana[] = [
                'status' => $status,
                'unit' => null,
                'zona' => $zona,
                'jawaban_unit' => false,
                'kategori' => null,
                'kanal' => null,
                'keparahan' => null,
                'kasus_berat' => false,
            ];
        }

        return $rencana;
    }

    /**
     * Bagi kategori tepat 75 fasilitas dan 75 medis.
     *
     * @param  array<int, array<string, mixed>>  $rencana
     */
    private function sebarKategori(array &$rencana): void
    {
        $setengah = intdiv(self::JUMLAH_TIKET, 2);

        $nilai = array_merge(
            array_fill(0, $setengah, KategoriPengaduan::Fasilitas),
            array_fill(0, $setengah, KategoriPengaduan::Medis),
        );

        shuffle($nilai);

        foreach ($rencana as $nomor => &$baris) {
            $baris['kategori'] = $nilai[$nomor];
        }

        unset($baris);
    }

    /**
     * Bagi kanal rata ke lima pilihan enum ditambah Portal Web.
     *
     * Portal Web diwakili nilai null karena tiket dari formulir web tidak
     * mengisi kolom kanal; filter di konsol menampilkannya dengan nama itu.
     *
     * @param  array<int, array<string, mixed>>  $rencana
     */
    private function sebarKanal(array &$rencana): void
    {
        $pilihan = [
            null,
            KanalPengaduan::Telepon,
            KanalPengaduan::Whatsapp,
            KanalPengaduan::TatapMuka,
            KanalPengaduan::Email,
            KanalPengaduan::Sms,
        ];

        $perKanal = intdiv(self::JUMLAH_TIKET, count($pilihan));
        $nilai = [];

        foreach ($pilihan as $kanal) {
            $nilai = array_merge($nilai, array_fill(0, $perKanal, $kanal));
        }

        shuffle($nilai);

        foreach ($rencana as $nomor => &$baris) {
            $baris['kanal'] = $nilai[$nomor];
        }

        unset($baris);
    }

    /**
     * Tandai kasus berat pada tiket medis yang masih berjalan.
     *
     * Dua belas tiket diambil bergiliran dari tiap unit supaya panel
     * prioritas di monitor disposisi tidak menumpuk di satu instalasi.
     * Kasus berat tidak pernah berstatus selesai karena aturannya harus
     * melewati telaah komite lebih dulu.
     *
     * @param  array<int, array<string, mixed>>  $rencana
     */
    private function tandaiKasusBerat(array &$rencana): void
    {
        $kandidat = [];

        foreach ($rencana as $nomor => $baris) {
            if ($baris['unit'] === null
                || $baris['kategori'] !== KategoriPengaduan::Medis
                || ! in_array($baris['status'], [StatusPengaduan::Diproses, StatusPengaduan::Revisi], true)) {
                continue;
            }

            $kandidat[$baris['unit']->id][] = $nomor;
        }

        $terpilih = [];
        $putaran = 0;

        while (count($terpilih) < self::JUMLAH_KASUS_BERAT) {
            $adaBarisBaru = false;

            foreach ($kandidat as $daftar) {
                if (! isset($daftar[$putaran])) {
                    continue;
                }

                $adaBarisBaru = true;
                $terpilih[] = $daftar[$putaran];

                if (count($terpilih) >= self::JUMLAH_KASUS_BERAT) {
                    break;
                }
            }

            // Semua unit sudah kehabisan kandidat sebelum target tercapai;
            // hentikan supaya tidak berputar tanpa akhir.
            if (! $adaBarisBaru) {
                break;
            }

            $putaran++;
        }

        foreach ($terpilih as $nomor) {
            $rencana[$nomor]['kasus_berat'] = true;
        }
    }

    /**
     * Tetapkan keparahan merata untuk tiket yang sudah ditelaah admin.
     *
     * Tiap unit mendapat empat ringan, empat sedang, dan empat berat.
     * Tiket yang masih Diterima belum ditelaah sehingga kolomnya kosong.
     * Kasus berat selalu berlabel berat, jadi sisa kuota berat dihitung
     * setelah penandaan kasus berat.
     *
     * @param  array<int, array<string, mixed>>  $rencana
     */
    private function sebarKeparahan(array &$rencana): void
    {
        $kelompok = [];

        foreach ($rencana as $nomor => $baris) {
            if ($baris['status']->diterima()) {
                continue;
            }

            // Tiket langsung humas memakai kunci 0 supaya ikut mendapat
            // porsi, tetapi dengan komposisi yang lebih kecil.
            $kelompok[$baris['unit']?->id ?? 0][] = $nomor;
        }

        foreach ($kelompok as $kunci => $daftar) {
            $tetap = [];
            $bebas = [];

            foreach ($daftar as $nomor) {
                if ($rencana[$nomor]['kasus_berat']) {
                    $tetap[] = $nomor;
                } else {
                    $bebas[] = $nomor;
                }
            }

            // Unit: 4 ringan, 4 sedang, 4 berat. Langsung humas hanya
            // punya empat tiket yang sudah ditelaah, jadi 2/1/1.
            [$ringan, $sedang, $berat] = $kunci === 0
                ? [2, 1, 1]
                : [4, 4, 4];

            $nilai = array_merge(
                array_fill(0, max(0, $berat - count($tetap)), TingkatKeparahan::Berat),
                array_fill(0, $sedang, TingkatKeparahan::Sedang),
                array_fill(0, $ringan, TingkatKeparahan::Ringan),
            );

            shuffle($nilai);

            foreach ($bebas as $urutan => $nomor) {
                $rencana[$nomor]['keparahan'] = $nilai[$urutan];
            }

            foreach ($tetap as $nomor) {
                $rencana[$nomor]['keparahan'] = TingkatKeparahan::Berat;
            }
        }

        foreach ($rencana as &$baris) {
            if ($baris['status']->diterima()) {
                $baris['keparahan'] = null;
            }
        }

        unset($baris);
    }

    /**
     * Tanggal masuk dan, bila sudah selesai, tanggal tutup tiket.
     *
     * Tiket selesai disebar sepanjang enam bulan terakhir supaya kartu
     * "selesai bulan ini" dan filter rentang tanggal sama-sama punya isi.
     * Tiket aktif yang terlambat ikut disebar, sedangkan tiket yang masih
     * aman atau mendekati batas tidak mungkin tua karena zona itu memang
     * dihitung dari umur tiket.
     *
     * @param  array<string, mixed>  $baris
     * @param  array{selesai: int, terlambat: int}  $hitung
     * @return array{0: Carbon, 1: ?Carbon}
     */
    private function tanggal(array $baris, array &$hitung, int $jumlahSelesai, int $jumlahTerlambat): array
    {
        $status = $baris['status'];
        $total = Sla::totalHariKerja($baris['kasus_berat']);

        // Titik hitung dimundurkan ke hari kerja terdekat supaya subWeekdays()
        // selalu berakhir di hari kerja dan selisih hari kerja dengan
        // Sla::zona() tidak meleset satu hari.
        $dasar = now()->copy();

        if ($dasar->isWeekend()) {
            $dasar->previousWeekday();
        }

        if ($status->diterima()) {
            return [now()->subHours(mt_rand(1, 48)), null];
        }

        if ($status->selesai()) {
            $usia = 2 + intdiv($hitung['selesai'] * 175, max(1, $jumlahSelesai - 1));
            $hitung['selesai']++;

            $selesai = $dasar->copy()->subDays($usia);

            if ($selesai->isWeekend()) {
                $selesai->previousWeekday();
            }

            $selesai->setTime(mt_rand(13, 17), mt_rand(0, 59));

            $lama = $baris['zona'] === 'tepat'
                ? mt_rand(1, max(1, $total - 3))
                : mt_rand($total + 1, $total + 8);

            $dibuat = $selesai->copy()->subWeekdays($lama);

            return [$this->jamMasuk($dibuat), $selesai];
        }

        if ($baris['zona'] === 'mendek') {
            return [$this->jamMasuk($dasar->copy()->subWeekdays($total - 1)), null];
        }

        if ($baris['zona'] === 'terlambat') {
            $usia = $total + 2 + intdiv($hitung['terlambat'] * 120, max(1, $jumlahTerlambat - 1));
            $hitung['terlambat']++;

            return [$this->jamMasuk($dasar->copy()->subWeekdays($usia)), null];
        }

        return [$this->jamMasuk($dasar->copy()->subWeekdays(mt_rand(2, max(2, $total - 3)))), null];
    }

    /** Jam masuk dibuat pagi supaya selisih hari tidak terpotong pembulatan. */
    private function jamMasuk(Carbon $tanggal): Carbon
    {
        return $tanggal->copy()->setTime(mt_rand(7, 11), mt_rand(0, 59));
    }

    /**
     * Kode tiket dibuat dengan bentuk yang sama seperti tiket sungguhan.
     *
     * @param  array<string, bool>  $terpakai
     */
    private function kodeTiket(Carbon $dibuat, array &$terpakai): string
    {
        do {
            $huruf = '';

            for ($i = 0; $i < 5; $i++) {
                $huruf .= chr(mt_rand(65, 90));
            }

            $kode = 'ADUAN-'.$dibuat->format('Ymd').'-'.$huruf;
        } while (isset($terpakai[$kode]));

        $terpakai[$kode] = true;

        return $kode;
    }

    /**
     * Sebagian besar pelapor sudah memverifikasi email; sisanya dibiarkan
     * punya OTP aktif atau belum menerima OTP sama sekali, supaya halaman
     * verifikasi punya ketiga keadaan.
     *
     * @return array{0: ?Carbon, 1: ?string, 2: ?Carbon}
     */
    private function statusEmail(int $nomor, Carbon $dibuat): array
    {
        $sisa = $nomor % 10;

        if ($sisa <= 6) {
            return [$dibuat->copy()->addHour(), null, null];
        }

        if ($sisa <= 8) {
            return [
                null,
                str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT),
                $dibuat->copy()->addMinutes(15),
            ];
        }

        return [null, null, null];
    }

    /**
     * Tiga dari sepuluh tiket punya token lacak. Token yang sudah lewat masa
     * berlakunya tetap disimpan supaya jalur penolakan ikut terwakili.
     *
     * Token dibuat deterministik dari kode_tiket supaya re-run seeder tidak
     * menabrak unique constraint (shuffle mengubah urutan loop index).
     *
     * @return array{0: ?string, 1: ?Carbon}
     */
    private function tokenAkses(string $kodeTiket, Carbon $dibuat): array
    {
        // Hanya ~30% tiket punya token akses (berdasarkan hash kode_tiket)
        $hash = hash('sha256', $kodeTiket);
        if (hexdec(substr($hash, 0, 2)) > 51) { // ~30%
            return [null, null];
        }

        return [hash('sha256', 'akses-'.$kodeTiket), $dibuat->copy()->addDays(30)];
    }

    /** Draf jawaban hanya masuk akal pada tiket yang masih berjalan. */
    private function drafJawaban(StatusPengaduan $status, int $nomor): ?string
    {
        if ($status->diterima() || $status->selesai() || $nomor % 5 < 3) {
            return null;
        }

        return 'Terima kasih atas laporannya. Tim terkait sedang menelusuri '
            .'kejadian ini dan hasilnya akan kami sampaikan melalui halaman ini.';
    }

    /**
     * Isi percakapan mengikuti sejauh mana tiket berjalan.
     *
     * Pesan pembuka selalu ada, jalur unit diisi hanya untuk tiket yang
     * sudah diteruskan dan unitnya benar-benar membalas, dan tiket selesai
     * ditutup dengan kalimat penutup pada waktu yang sama dengan selesai_at.
     */
    private function isiPercakapan(
        Pengaduan $pengaduan,
        StatusPengaduan $status,
        bool $jawabanUnit,
        Carbon $dibuat,
        ?Carbon $selesai,
    ): void {
        $pengaduan->pesan()->delete();

        $daftar = [
            [
                'kanal' => 'pelapor',
                'peran' => 'pembuka',
                'isi' => 'Terima kasih telah menyampaikan pengaduan. Laporan Anda '
                    .'sudah kami terima dengan nomor tiket '.$pengaduan->kode_tiket.'.',
                'waktu' => $dibuat->copy()->addMinutes(30),
            ],
        ];

        if (! $status->diterima()) {
            $daftar[] = [
                'kanal' => 'pelapor',
                'peran' => 'admin',
                'isi' => $pengaduan->master_unit_id === null
                    ? 'Laporan ini ditangani langsung oleh tim Humas tanpa disposisi ke unit teknis. '
                        .'Perkembangan akan kami kabarkan lewat halaman ini.'
                    : 'Laporan sudah kami teruskan ke '.$pengaduan->namaUnit()
                        .' untuk ditelusuri. Perkembangan akan kami kabarkan lewat halaman ini.',
                'waktu' => $dibuat->copy()->addHours(5),
            ];
        }

        if ($status->perluAksi()) {
            $daftar[] = [
                'kanal' => 'pelapor',
                'peran' => 'admin',
                'isi' => 'Untuk melanjutkan pemeriksaan, mohon kirimkan foto bukti '
                    .'atau dokumen pendukung yang relevan melalui kolom balasan.',
                'waktu' => $dibuat->copy()->addDay()->addHours(2),
            ];
            $daftar[] = [
                'kanal' => 'pelapor',
                'peran' => 'pelapor',
                'isi' => 'Baik, dokumennya saya siapkan. Terima kasih atas informasinya.',
                'waktu' => $dibuat->copy()->addDay()->addHours(6),
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

        if ($jawabanUnit) {
            $daftar[] = [
                'kanal' => 'unit',
                'peran' => 'admin',
                'isi' => 'Selamat pagi, mohon ditindaklanjuti pengaduan '
                    .$pengaduan->kode_tiket.' terkait '.$pengaduan->subjek.'.',
                'waktu' => $dibuat->copy()->addDay()->addHour(),
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
     * Jejak audit diisi manual karena event model tidak selalu berjalan
     * saat seeder dipanggil lewat DatabaseSeeder.
     *
     * Selain perpindahan tahap, sebagian tiket diberi baris penetapan
     * kategori, keparahan, dan nudge supaya halaman riwayat menampilkan
     * keempat jenis aksi, bukan hanya disposisi.
     *
     * @param  array<string, mixed>  $baris
     */
    private function isiRiwayat(
        Pengaduan $pengaduan,
        array $baris,
        Carbon $dibuat,
        ?Carbon $selesai,
        ?int $adminId,
        int $nomor,
    ): void {
        $pengaduan->riwayatStatus()->delete();

        $status = $baris['status'];
        $kasusBerat = $baris['kasus_berat'];

        $tahapan = [[StatusPengaduan::Diterima, null, $dibuat]];

        if (! $status->diterima()) {
            $tahapan[] = [
                StatusPengaduan::Diproses,
                $baris['unit'] === null
                    ? 'Ditangani langsung oleh Humas tanpa disposisi unit teknis.'
                    : 'Disposisi diteruskan ke '.$pengaduan->namaUnit().' untuk investigasi.',
                $dibuat->copy()->addDay(),
            ];
        }

        if ($status->perluAksi()) {
            $tahapan[] = [
                StatusPengaduan::Revisi,
                'Dokumen pendukung diminta kembali dari pelapor.',
                $dibuat->copy()->addDays(2),
            ];
        }

        if ($status->selesai()) {
            $tahapan[] = [
                StatusPengaduan::Selesai,
                'Jawaban resmi diterbitkan dan tiket ditutup.',
                $selesai ?? $dibuat->copy()->addDays(6),
            ];
        }

        foreach ($tahapan as $urutan => [$tahap, $catatan, $waktu]) {
            $pengaduan->riwayatStatus()->forceCreate([
                'dari' => $urutan === 0 ? null : $tahapan[$urutan - 1][0],
                'ke' => $tahap,
                'catatan' => $catatan,
                'admin_id' => $urutan === 0 ? null : $adminId,
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ]);
        }

        if ($nomor % 7 === 0) {
            $pengaduan->riwayatStatus()->forceCreate([
                'dari' => StatusPengaduan::Diterima,
                'ke' => StatusPengaduan::Diterima,
                'catatan' => 'Kategori diubah dari Belum Ditentukan ke '
                    .($pengaduan->kategori?->label() ?? 'Belum Ditentukan').'.',
                'admin_id' => $adminId,
                'is_kategori' => true,
                'created_at' => $dibuat->copy()->addHours(2),
                'updated_at' => $dibuat->copy()->addHours(2),
            ]);
        }

        if ($nomor % 6 === 0) {
            $pengaduan->riwayatStatus()->forceCreate([
                'dari' => StatusPengaduan::Diterima,
                'ke' => StatusPengaduan::Diterima,
                'catatan' => 'Tingkat keparahan diubah dari Belum Ditetapkan ke '
                    .($pengaduan->tingkat_keparahan?->label() ?? 'Belum Ditetapkan').'.',
                'admin_id' => $adminId,
                'is_keparahan' => true,
                'created_at' => $dibuat->copy()->addHours(3),
                'updated_at' => $dibuat->copy()->addHours(3),
            ]);
        }

        if ($kasusBerat) {
            $pengaduan->riwayatStatus()->forceCreate([
                'dari' => StatusPengaduan::Diproses,
                'ke' => StatusPengaduan::Diproses,
                'catatan' => Pengaduan::CATATAN_KASUS_BERAT_AKTIF,
                'admin_id' => $adminId,
                'created_at' => $dibuat->copy()->addDay()->addHours(2),
                'updated_at' => $dibuat->copy()->addDay()->addHours(2),
            ]);
        }

        if (! $status->diterima() && ! $status->selesai() && $nomor % 8 === 0) {
            $pengaduan->riwayatStatus()->forceCreate([
                'dari' => StatusPengaduan::Diproses,
                'ke' => StatusPengaduan::Diproses,
                'catatan' => 'Nudge manual ke unit: mohon percepat hasil investigasi.',
                'admin_id' => $adminId,
                'is_nudge' => true,
                'created_at' => $dibuat->copy()->addDay()->addHours(3),
                'updated_at' => $dibuat->copy()->addDay()->addHours(3),
            ]);
        }
    }

    /**
     * Hitung baris yang lolos syarat tanpa mengubah array rencana.
     *
     * @param  array<int, array<string, mixed>>  $rencana
     */
    private function hitungBaris(array $rencana, callable $syarat): int
    {
        return count(array_filter($rencana, $syarat));
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
            ['Air minum di ruang tunggu habis', 'Dispenser air minum di ruang tunggu anak tidak terisi ulang sejak pagi dan tidak ada petugas yang bisa dimintai bantuan.'],
            ['Kursi roda di lobi tidak tersedia', 'Kursi roda di lobi utama sedang dipakai semua sehingga pasien lanjut usia harus berjalan jauh menuju poliklinik.'],
            ['Waktu tunggu hasil rekam medis', 'Permintaan salinan rekam medis memakan waktu lebih dari tiga hari kerja tanpa kepastian kapan bisa diambil.'],
            ['Penerangan lorong IGD redup', 'Beberapa lampu di lorong IGD mati sehingga keluarga pasien kesulitan membaca papan informasi dan mencari ruangan.'],
            ['Antrean pengambilan obat tidak tertib', 'Tidak ada nomor antrean di apotek sehingga pasien saling mendahului dan petugas tampak kewalahan mengatur urutan.'],
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
            ['Jadwal kontrol tidak diinformasikan', 'Pasien rawat jalan tidak menerima informasi jadwal kontrol ulang sehingga datang pada hari yang salah.'],
            ['Nyeri pasca tindakan tidak ditangani', 'Keluhan nyeri setelah tindakan tidak mendapat penanganan cepat karena pasien menunggu berjam-jam tanpa kabar.'],
            ['Identitas pasien salah pada label', 'Label gelang identitas pasien tertukar dengan pasien lain sehingga keluarga khawatir tindakan akan keliru sasaran.'],
            ['Pemeriksaan ulang tanpa penjelasan', 'Pemeriksaan laboratorium harus diulang karena sampel dianggap tidak memenuhi syarat, tetapi alasannya tidak dijelaskan.'],
            ['Konsultasi terlalu singkat', 'Waktu konsultasi dengan dokter sangat singkat sehingga pertanyaan penting pasien tidak sempat terjawab.'],
        ];

        return $kategori === KategoriPengaduan::Medis
            ? $medis[array_rand($medis)]
            : $fasilitas[array_rand($fasilitas)];
    }
}
