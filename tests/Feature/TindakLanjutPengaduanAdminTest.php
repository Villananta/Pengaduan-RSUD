<?php

namespace Tests\Feature;

use App\Enums\DisposisiUnit;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Support\Sla;
use App\Support\StatistikDashboard;
use App\Support\TindakLanjutPengaduan;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tindak lanjut admin humas terhadap satu tiket dari halaman detail.
 *
 * Yang diuji di sini bukan hanya tampilan tombolnya, tetapi juga akibatnya
 * terhadap data: tahap tiket, jejak audit, percakapan, dan target SLA.
 */
class TindakLanjutPengaduanAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);
    }

    public function test_menyimpan_draf_tidak_mengubah_tahap_tiket_maupun_percakapan(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-DRAF-01');

        $this->post(route('admin.pengaduan.draf', $pengaduan->kode_tiket), [
            'draf' => 'Naskah jawaban masih disusun oleh tim.',
        ])->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $pengaduan->refresh();

        $this->assertSame('Naskah jawaban masih disusun oleh tim.', $pengaduan->draf_jawaban);
        $this->assertTrue($pengaduan->status->diproses());
        $this->assertCount(0, $pengaduan->pesan);
    }

    public function test_draf_kosong_justru_mengosongkan_kolom_draf(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-DRAF-02');
        $pengaduan->update(['draf_jawaban' => 'Naskah lama yang sudah dibuang.']);

        $this->post(route('admin.pengaduan.draf', $pengaduan->kode_tiket), ['draf' => ''])
            ->assertRedirect();

        $this->assertNull($pengaduan->fresh()->draf_jawaban);
    }

    public function test_aksi_a_menutup_tiket_dan_menyimpan_jawaban_resmi(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-TUTUP-01');

        $this->post(route('admin.pengaduan.jawaban', $pengaduan->kode_tiket), [
            'isi' => 'Terima kasih atas laporannya. Instalasi Farmasi Pusat sudah memeriksa catatan pemberian obat.',
            'catatan' => 'Klarifikasi Apotik dan Doktor Penanggung Jawab sudah lengkap.',
        ])->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $pengaduan->refresh();

        $this->assertTrue($pengaduan->status->selesai());
        $this->assertNotNull($pengaduan->selesai_at);

        $balasan = $pengaduan->pesan()->where('peran', 'admin')->first();
        $this->assertNotNull($balasan);

        $penutupan = $pengaduan->riwayatStatus()->where('ke', StatusPengaduan::Selesai)->first();
        $this->assertSame(StatusPengaduan::Diproses, $penutupan->dari);
        $this->assertSame('Klarifikasi Apotik dan Doktor Penanggung Jawab sudah lengkap.', $penutupan->catatan);
    }

    public function test_aksi_a_menolak_jawaban_resmi_yang_terlalu_pendek(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-TUTUP-02');

        $this->from(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->post(route('admin.pengaduan.jawaban', $pengaduan->kode_tiket), ['isi' => 'Selesai.'])
            ->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertSessionHasErrors('isi');

        $pengaduan->refresh();

        $this->assertTrue($pengaduan->status->diproses());
        $this->assertCount(0, $pengaduan->pesan);
    }

    public function test_aksi_a_menolak_tiket_yang_sudah_selesai(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-TUTUP-03', StatusPengaduan::Selesai);

        $this->from(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->post(route('admin.pengaduan.jawaban', $pengaduan->kode_tiket), [
                'isi' => 'Jawaban tambahan yang tidak seharusnya terkirim.',
            ])
            ->assertSessionHasErrors('isi');

        $pengaduan->refresh();

        $this->assertTrue($pengaduan->status->selesai());
        $this->assertCount(0, $pengaduan->pesan);
    }

    public function test_aksi_b_mengembalikan_tiket_ke_unit_lalu_menandai_unit_menunggu_investigasi(): void
    {
        $unit = MasterUnit::where('kode', 'IBT-04')->firstOrFail();

        $pengaduan = $this->tiketDiproses('ADUAN-KEMBALIK-01', StatusPengaduan::Revisi);
        $pengaduan->update(['master_unit_id' => $unit->id, 'unit' => $unit->nama]);

        $this->post(route('admin.pengaduan.kembalikan', $pengaduan->kode_tiket), [
            'catatan' => 'Hasil pemeriksaanalta labs belum dilampirkan.',
        ])->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $pengaduan->refresh();

        $this->assertTrue($pengaduan->status->diproses());
        $this->assertNull($pengaduan->selesai_at);
        $this->assertTrue($unit->fresh()->disposisi === DisposisiUnit::MenungguInvestigasi);

        $pengembalian = $pengaduan->riwayatStatus()->latest('id')->first();
        $this->assertSame(StatusPengaduan::Revisi, $pengembalian->dari);
        $this->assertSame(StatusPengaduan::Diproses, $pengembalian->ke);
    }

    public function test_aksi_b_menolak_tiket_yang_belum_ditugaskan_ke_unit(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-KEMBALIK-02', StatusPengaduan::Revisi);
        $pengaduan->update(['master_unit_id' => null]);

        $this->from(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->post(route('admin.pengaduan.kembalikan', $pengaduan->kode_tiket), [
                'catatan' => 'Mohon dilengkapi kembali.',
            ])
            ->assertSessionHasErrors('catatan');

        $pengaduan->refresh();

        $this->assertSame(StatusPengaduan::Revisi, $pengaduan->status);
    }

    public function test_tiket_diterima_hanya_menawarkan_tombol_proses(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-TAHAP-01', StatusPengaduan::Diterima);

        $tampilan = $this->get(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertSee('Proses')
            ->getContent();

        $this->assertSame(
            [StatusPengaduan::Diproses],
            TindakLanjutPengaduan::tujuanTersedia($pengaduan)
        );

        // Penyebutan tahap lain masih muncul di stepper, jadi yang diperiksa
        // adalah nilai tujuan yang benar-benar dikirim formulir.
        $this->assertStringContainsString('value="diproses"', $tampilan);
        $this->assertStringNotContainsString('value="selesai"', $tampilan);
    }

    public function test_tombol_proses_memindahkan_tiket_ke_tahap_diproses(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-TAHAP-02', StatusPengaduan::Diterima);

        $this->post(route('admin.pengaduan.tahap', $pengaduan->kode_tiket), [
            'tujuan' => StatusPengaduan::Diproses->value,
        ])->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertSessionHas('sukses');

        $pengaduan->refresh();

        $this->assertTrue($pengaduan->status->diproses());
        $this->assertNull($pengaduan->selesai_at);

        $perpindahan = $pengaduan->riwayatStatus()->latest('id')->first();
        $this->assertSame(StatusPengaduan::Diterima, $perpindahan->dari);
        $this->assertSame(StatusPengaduan::Diproses, $perpindahan->ke);
    }

    public function test_tiket_diproses_menawarkan_selesaikan_dan_revisi(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-TAHAP-03');

        $tampilan = $this->get(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertSee('Selesaikan')
            ->assertSee('Revisi')
            ->getContent();

        $this->assertSame(
            [StatusPengaduan::Selesai, StatusPengaduan::Revisi],
            TindakLanjutPengaduan::tujuanTersedia($pengaduan)
        );

        $this->assertStringContainsString('value="selesai"', $tampilan);
        $this->assertStringContainsString('value="revisi"', $tampilan);

        // Revisi memakai warna merah dengan huruf putih supaya tidak tertukar
        // dengan tombol yang menutup tiket.
        $this->assertStringContainsString('bg-error text-white', $tampilan);
    }

    public function test_tombol_revisi_memindahkan_tiket_ke_tahap_perlu_revisi(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-TAHAP-04');

        $this->post(route('admin.pengaduan.tahap', $pengaduan->kode_tiket), [
            'tujuan' => StatusPengaduan::Revisi->value,
            'catatan' => 'Hasil pemeriksaan laboratori belum dilampirkan.',
        ])->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $pengaduan->refresh();

        $this->assertSame(StatusPengaduan::Revisi, $pengaduan->status);
        $this->assertNull($pengaduan->selesai_at);

        $perpindahan = $pengaduan->riwayatStatus()->latest('id')->first();
        $this->assertSame('Hasil pemeriksaan laboratori belum dilampirkan.', $perpindahan->catatan);
    }

    public function test_tiket_revisi_hanya_menawarkan_tombol_selesaikan(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-TAHAP-05', StatusPengaduan::Revisi);

        $tampilan = $this->get(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertSee('Selesaikan')
            ->getContent();

        $this->assertSame([StatusPengaduan::Selesai], TindakLanjutPengaduan::tujuanTersedia($pengaduan));
        $this->assertStringContainsString('value="selesai"', $tampilan);
        $this->assertStringNotContainsString('value="revisi"', $tampilan);
    }

    public function test_tombol_selesaikan_menutup_tiket_dari_tahap_revisi(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-TAHAP-06', StatusPengaduan::Revisi);

        $this->post(route('admin.pengaduan.tahap', $pengaduan->kode_tiket), [
            'tujuan' => StatusPengaduan::Selesai->value,
        ])->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $pengaduan->refresh();

        $this->assertTrue($pengaduan->status->selesai());
        $this->assertNotNull($pengaduan->selesai_at);
    }

    public function test_tombol_tahap_ditolak_untuk_tujuan_yang_tidak_dilizinkan(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-TAHAP-07', StatusPengaduan::Diterima);

        $this->from(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->post(route('admin.pengaduan.tahap', $pengaduan->kode_tiket), [
                'tujuan' => StatusPengaduan::Selesai->value,
            ])
            ->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertSessionHasErrors('tujuan');

        $pengaduan->refresh();

        $this->assertTrue($pengaduan->status->diterima());
    }

    public function test_tombol_tahap_menolak_tiket_yang_sudah_selesai(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-TAHAP-08', StatusPengaduan::Selesai);

        $this->from(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->post(route('admin.pengaduan.tahap', $pengaduan->kode_tiket), [
                'tujuan' => StatusPengaduan::Diproses->value,
            ])
            ->assertSessionHasErrors('tujuan');

        $this->assertTrue($pengaduan->fresh()->status->selesai());
    }

    public function test_tombol_tahap_menolak_tujuan_yang_tidak_dikenal(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-TAHAP-09');

        $this->from(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->post(route('admin.pengaduan.tahap', $pengaduan->kode_tiket), ['tujuan' => 'dibatalkan'])
            ->assertSessionHasErrors('tujuan');

        $this->assertTrue($pengaduan->fresh()->status->diproses());
    }

    public function test_menandai_kasus_berat_memperpanjang_target_penyelesaian(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-BERAT-01');
        $targetStandar = $pengaduan->targetSla();

        $this->post(route('admin.pengaduan.kasus-berat', $pengaduan->kode_tiket), ['aktif' => 1])
            ->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $pengaduan->refresh();

        $this->assertTrue($pengaduan->kasus_berat);
        $this->assertNotNull($pengaduan->kasus_berat_at);
        $this->assertSame(
            Sla::hariKerja() + Sla::tambahanKasusBerat(),
            $pengaduan->totalHariKerjaSla()
        );
        $this->assertTrue($pengaduan->targetSla()->greaterThan($targetStandar));

        // Jejak auditnya harus menjelaskan bahwa penandaan baru saja diubah,
        // bukan mencatat perpindahan tahap yang sebenarnya tidak terjadi.
        $catatan = $pengaduan->riwayatStatus()->latest('id')->first();
        $this->assertSame($pengaduan->status, $catatan->dari);
        $this->assertSame($pengaduan->status, $catatan->ke);
    }

    public function test_melepas_penandaan_kasus_berat_mengembalikan_target_ke_standar(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-BERAT-02');
        $pengaduan->tandaiKasusBerat(true);

        $this->post(route('admin.pengaduan.kasus-berat', $pengaduan->kode_tiket), ['aktif' => 0])
            ->assertRedirect();

        $pengaduan->refresh();

        $this->assertFalse($pengaduan->kasus_berat);
        $this->assertNull($pengaduan->kasus_berat_at);
        $this->assertSame(Sla::hariKerja(), $pengaduan->totalHariKerjaSla());
    }

    public function test_halaman_detail_menampilkan_total_hari_kerja_kasus_berat(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-BERAT-03');
        $pengaduan->tandaiKasusBerat(true);

        $this->get(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertSee('Kasus Berat Aktif')
            ->assertSee(Sla::tambahanKasusBerat().' hari kerja')
            ->assertSee(Sla::hariKerja().' hari kerja menjadi')
            ->assertSee('Lepas Penandaan');
    }

    public function test_tiket_selesai_tidak_menawarkan_tindakan_lagi(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-TUTUP-04', StatusPengaduan::Selesai);

        $tampilan = $this->get(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertSee('Tiket ini sudah selesai, tidak perlu jawaban resmi lagi.')
            ->getContent();

        // Alasannya tampil sebagai teks, tombolnya sendiri tetap nonaktif.
        $this->assertStringContainsString('disabled', $tampilan);
    }

    public function test_aksi_a_membuang_cache_angka_dashboard_setelah_menutup_tiket(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-CACHE-01');

        // Angka dashboard diambil lebih dulu supaya nilai lamanya masuk cache.
        $sebelum = StatistikDashboard::aktif();

        $this->post(route('admin.pengaduan.jawaban', $pengaduan->kode_tiket), [
            'isi' => 'Pengaduan sudah selesai dan tidak perlu tindak lanjut lagi.',
        ])->assertRedirect();

        $sesudah = StatistikDashboard::aktif();

        $this->assertSame($sebelum - 1, $sesudah);
    }

    public function test_tiket_yang_tidak_ada_menghasilkan_not_found_untuk_setiap_tindakan(): void
    {
        $kode = 'ADUAN-HILANG-99';

        $this->post(route('admin.pengaduan.tahap', $kode), ['tujuan' => 'diproses'])->assertNotFound();
        $this->post(route('admin.pengaduan.draf', $kode), ['draf' => 'x'])->assertNotFound();
        $this->post(route('admin.pengaduan.jawaban', $kode), ['isi' => 'x'])->assertNotFound();
        $this->post(route('admin.pengaduan.kembalikan', $kode), [])->assertNotFound();
        $this->post(route('admin.pengaduan.kasus-berat', $kode), ['aktif' => 1])->assertNotFound();
    }

    private function tiketDiproses(string $kode, ?StatusPengaduan $status = null): Pengaduan
    {
        return Pengaduan::factory()
            ->status($status ?? StatusPengaduan::Diproses)
            ->create(['kode_tiket' => $kode]);
    }
}
