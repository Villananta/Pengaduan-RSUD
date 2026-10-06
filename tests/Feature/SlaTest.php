<?php

namespace Tests\Feature;

use App\Enums\ZonaSla;
use App\Support\Sla;
use Carbon\Carbon;
use Tests\TestCase;

class SlaTest extends TestCase
{
    /** Tiket masuk hari Senin 5 Oktober 2026 pukul 08:00 waktu aplikasi. */
    private const MASUK = '2026-10-05 08:00:00';

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_hari_kerja_masuk_dihitung_sebagai_hari_pertama(): void
    {
        $masuk = Carbon::parse(self::MASUK);

        Carbon::setTestNow($masuk);

        // Tiket yang baru masuk hari ini sudah hari ke-1, bukan hari ke-0.
        $this->assertSame(1, Sla::hariKe($masuk));
        $this->assertSame(0, Sla::hariKerjaLewat($masuk));
    }

    public function test_tanggal_target_jatuh_pada_hari_kerja_ke_dua_belas(): void
    {
        $masuk = Carbon::parse(self::MASUK);

        // 5 Okt = hari ke-1, jadi 20 Okt = hari ke-12 dan 21 Okt sudah
        // hari ke-13 yang di luar standar.
        $this->assertSame('2026-10-20', Sla::target($masuk)->toDateString());
    }

    public function test_nomor_hari_dan_sisa_hari_menjumlah_standar_sampai_depart_target(): void
    {
        $masuk = Carbon::parse(self::MASUK);
        $total = Sla::hariKerja();

        foreach ([
            '2026-10-05 09:00:00',
            '2026-10-09 09:00:00',
            '2026-10-16 09:00:00',
            '2026-10-19 09:00:00',
            '2026-10-20 09:00:00',
        ] as $tanggal) {
            Carbon::setTestNow(Carbon::parse($tanggal));

            // Inilah yang rusak sebelumnya: hari masuk sudah tampil
            // "Hari ke-1" sementara sisa hari masih menyebut dua belas hari
            // kerja tersisa, sehingga keduanya melebihi standar.
            $this->assertSame(
                $total,
                Sla::hariKe($masuk) + Sla::sisaHariKerja($masuk),
                "Pada {$tanggal} nomor hari dan sisa hari tidak sama dengan standar {$total}."
            );
        }
    }

    public function test_tiket_baru_menampilkan_sembilan_belas_bukan_dua_belas_hari_tersisa(): void
    {
        $masuk = Carbon::parse(self::MASUK);

        Carbon::setTestNow($masuk);

        // 1 + 11 = 12, jadi sisa hari kerja saat tiket baru masuk sebelas,
        // bukan dua belas seperti sebelumnya.
        $this->assertSame(Sla::hariKerja() - 1, Sla::sisaHariKerja($masuk));
    }

    public function test_sisa_hari_nol_setelah_tiket_lewat_target(): void
    {
        $masuk = Carbon::parse(self::MASUK);

        Carbon::setTestNow(Carbon::parse('2026-10-26 09:00:00'));

        // Dulu selisih tanggal ke tanggal target menghasilkan angka negatif
        // atau satu, sehingga tiket yang sudah terlambat masih terbaca punya
        // sisa satu hari kerja.
        $this->assertSame(0, Sla::sisaHariKerja($masuk));
    }

    public function test_hari_deadline_masih_dihitung_tepat_waktu(): void
    {
        $masuk = Carbon::parse(self::MASUK);

        // 20 Oktober adalah hari kerja ke-12, yaitu hari terakhir standar.
        // Tiket yang sudah sampai di hari itu belum boleh ditandai terlambat.
        Carbon::setTestNow(Carbon::parse('2026-10-20 09:00:00'));

        $this->assertSame(Sla::hariKerja(), Sla::hariKe($masuk));
        $this->assertSame(0, Sla::sisaHariKerja($masuk));
        $this->assertNotSame(ZonaSla::Terlambat, Sla::zona($masuk));
    }

    public function test_sehari_setelah_deadline_baru_ditandai_terlambat(): void
    {
        $masuk = Carbon::parse(self::MASUK);

        Carbon::setTestNow(Carbon::parse('2026-10-21 09:00:00'));

        $this->assertSame(Sla::hariKerja() + 1, Sla::hariKe($masuk));
        $this->assertSame(0, Sla::sisaHariKerja($masuk));
        $this->assertSame(ZonaSla::Terlambat, Sla::zona($masuk));
    }

    public function test_tiket_yang_selesai_tepat_pada_hari_deadline_dihitung_tepat_waktu(): void
    {
        $masuk = Carbon::parse(self::MASUK);

        $this->assertSame(
            ZonaSla::TepatWaktu,
            Sla::zona($masuk, Carbon::parse('2026-10-20 09:00:00'))
        );
    }

    public function test_tiket_yang_selesai_sesudah_hari_deadline_dihitung_terlambat(): void
    {
        $masuk = Carbon::parse(self::MASUK);

        // 21 Oktober adalah hari kerja ke-13. Versi lama menambah dua belas
        // minggu kerja penuh sehingga hari ini masih lolos tepat waktu,
        // asalkan jam penyelesaiannya tidak melewati jam tiket masuk.
        $this->assertSame(
            ZonaSla::Terlambat,
            Sla::zona($masuk, Carbon::parse('2026-10-21 07:00:00'))
        );
    }

    public function test_tiket_selesai_di_hari_yang_sama_tidak_terlambat(): void
    {
        $masuk = Carbon::parse(self::MASUK);

        $this->assertSame(
            ZonaSla::TepatWaktu,
            Sla::zona($masuk, Carbon::parse('2026-10-05 15:00:00'))
        );
    }

    public function test_peringatan_muncul_pada_dua_hari_terakhir(): void
    {
        $masuk = Carbon::parse(self::MASUK);

        // Hari ke-10 masih aman, dua hari terakhir masuk zona peringatan.
        Carbon::setTestNow(Carbon::parse('2026-10-16 09:00:00'));
        $this->assertSame(ZonaSla::TepatWaktu, Sla::zona($masuk));

        Carbon::setTestNow(Carbon::parse('2026-10-19 09:00:00'));
        $this->assertSame(ZonaSla::Mendek, Sla::zona($masuk));

        Carbon::setTestNow(Carbon::parse('2026-10-20 09:00:00'));
        $this->assertSame(ZonaSla::Mendek, Sla::zona($masuk));
    }

    public function test_kasus_berat_memperpanjang_standar_dan_nomor_hari_ikut_ikutan(): void
    {
        $masuk = Carbon::parse(self::MASUK);

        Carbon::setTestNow($masuk);

        $total = Sla::hariKerja() + Sla::tambahanKasusBerat();

        $this->assertSame(1, Sla::hariKe($masuk));
        $this->assertSame($total - 1, Sla::sisaHariKerja($masuk, null, true));
        $this->assertSame('2026-10-30', Sla::target($masuk, true)->toDateString());
    }

    public function test_kasus_berat_masih_tepat_waktu_sampai_hari_ke_dua_puluh(): void
    {
        $masuk = Carbon::parse(self::MASUK);

        Carbon::setTestNow(Carbon::parse('2026-10-30 09:00:00'));
        $this->assertNotSame(ZonaSla::Terlambat, Sla::zona($masuk, null, true));

        Carbon::setTestNow(Carbon::parse('2026-11-02 09:00:00'));
        $this->assertSame(ZonaSla::Terlambat, Sla::zona($masuk, null, true));
    }

    public function test_batas_investigasi_unit_tidak_ikut_berubah(): void
    {
        $masuk = Carbon::parse(self::MASUK);

        // Batas investigasi unit dihitung dari hari kerja yang benar-benar
        // lewat, jadi lima hari investigasi tetap lima hari dan tidak ikut
        // bergeser bersama perubahan konvensi SLA resolutions.
        Carbon::setTestNow(Carbon::parse('2026-10-09 09:00:00'));
        $this->assertFalse(Sla::lewatInvestigasi($masuk));

        Carbon::setTestNow(Carbon::parse('2026-10-12 09:00:00'));
        $this->assertTrue(Sla::lewatInvestigasi($masuk));
    }
}
