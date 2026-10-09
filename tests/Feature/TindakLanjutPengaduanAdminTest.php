<?php

namespace Tests\Feature;

use App\Enums\DisposisiUnit;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use App\Support\Sla;
use App\Support\StatistikDashboard;
use App\Support\TindakLanjutPengaduan;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_aksi_c_menugaskan_unit_dan_memindahkan_tiket_ke_diproses(): void
    {
        $unit = MasterUnit::where('kode', 'DFR-02')->firstOrFail();

        $pengaduan = $this->tiketDiproses('ADUAN-DISPOSISI-01', StatusPengaduan::Diterima);

        $this->post(route('admin.pengaduan.disposisi', $pengaduan->kode_tiket), [
            'unit' => $unit->kode,
            'catatan' => 'Tolong periksa serah terima resep di depo.',
        ])->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $pengaduan->refresh();

        $this->assertTrue($pengaduan->status->diproses());
        $this->assertSame($unit->id, $pengaduan->master_unit_id);
        $this->assertTrue($unit->fresh()->disposisi === DisposisiUnit::MenungguInvestigasi);

        $penugasan = $pengaduan->riwayatStatus()->latest('id')->first();
        $this->assertSame(StatusPengaduan::Diterima, $penugasan->dari);
        $this->assertSame(StatusPengaduan::Diproses, $penugasan->ke);
    }

    public function test_aksi_c_menolak_unit_yang_koneksinya_terputus(): void
    {
        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();

        $pengaduan = $this->tiketDiproses('ADUAN-DISPOSISI-02', StatusPengaduan::Diterima);
        $pengaduan->update(['master_unit_id' => null]);

        $this->from(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->post(route('admin.pengaduan.disposisi', $pengaduan->kode_tiket), [
                'unit' => $unit->kode,
            ])
            ->assertSessionHasErrors('unit');

        $pengaduan->refresh();

        $this->assertSame(StatusPengaduan::Diterima, $pengaduan->status);
        $this->assertNull($pengaduan->master_unit_id);
    }

    public function test_aksi_c_menolak_tiket_yang_sudah_selesai(): void
    {
        $unit = MasterUnit::where('kode', 'DFR-02')->firstOrFail();

        $pengaduan = $this->tiketDiproses('ADUAN-DISPOSISI-03', StatusPengaduan::Selesai);

        $this->from(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->post(route('admin.pengaduan.disposisi', $pengaduan->kode_tiket), [
                'unit' => $unit->kode,
            ])
            ->assertSessionHasErrors('unit');

        $this->assertSame(StatusPengaduan::Selesai, $pengaduan->fresh()->status);
    }

    public function test_jalur_2_melepas_unit_dan_menghilangkan_tiket_dari_daftar_disposisi_unit(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $pengaduan = $this->tiketDiproses('ADUAN-LANGSUNG-01', StatusPengaduan::Diterima);
        $pengaduan->update(['master_unit_id' => $unit->id]);

        // Sebelum diambil alih humas, tiket masih terlihat di daftar unit.
        $this->get(route('unit.disposisi.index', $unit))
            ->assertOk()
            ->assertSee($pengaduan->kode_tiket);

        $this->post(route('admin.pengaduan.tangani-langsung', $pengaduan->kode_tiket))
            ->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $pengaduan->refresh();

        $this->assertTrue($pengaduan->status->diproses());
        $this->assertNull($pengaduan->master_unit_id);

        // Pelepasan unit tetap terekam di riwayat, bukan hilang begitu saja.
        $pelepasan = $pengaduan->riwayatStatus->last();
        $this->assertStringContainsString($unit->namaLengkap(), $pelepasan->catatan);

        // Sesudah dilepas, tiket tidak lagi muncul di konsol unit.
        $this->get(route('unit.disposisi.index', $unit))
            ->assertOk()
            ->assertDontSee($pengaduan->kode_tiket);
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

        // Untuk tiket yang sudah tutup, alasan tampil sebagai teks dan tidak
        // ada formulir disposisi maupun tombol tahap yang masih bisa ditekan.
        $this->assertStringNotContainsString('action="'.route('admin.pengaduan.disposisi', $pengaduan->kode_tiket).'"', $tampilan);
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

    /**
     * Hitungan telaah diambil dari keberadaan balasan unit, bukan dari tahap.
     *
     * Angkanya harus langsung berubah begitu admin menulis balasan, bukan
     * menunggu cache dashboard yang masa berlakunya enam puluh detik.
     */
    public function test_balasan_admin_membuang_cache_angka_telaah(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-CACHE-02');

        // Angka dihangatkan lebih dulu supaya nilai lamanya masuk cache.
        $sebelum = StatistikDashboard::ringkasanKritis()['telaah'];

        $this->post(route('admin.pengaduan.balas', $pengaduan->kode_tiket), [
            'isi' => 'Instalasi sudah memeriksa catatan pemberian obat dan menyimpulkan tidak ada kesalahan dosis.',
        ])->assertRedirect();

        $sesudah = StatistikDashboard::ringkasanKritis()['telaah'];

        $this->assertSame($sebelum + 1, $sesudah);
    }

    public function test_admin_bisa_membalas_di_kolom_percakapan(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-CHAT-01');

        $this->post(route('admin.pengaduan.balas', $pengaduan->kode_tiket), [
            'isi' => 'Kami sudah menerima laporan Anda dan sedang memeriksa catatan service di instalasi.',
        ])->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $pengaduan->refresh();

        $balasan = $pengaduan->pesan()->where('peran', 'admin')->latest('id')->first();
        $this->assertNotNull($balasan);

        // Balasan percakapan tidak boleh ikut mengubah tahap tiket.
        $this->assertTrue($pengaduan->status->diproses());
        $this->assertNull($pengaduan->selesai_at);
    }

    public function test_admin_bisa_mengirim_koordinasi_ke_unit(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $pengaduan = Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'kode_tiket' => 'ADUAN-KOORD-01',
            'master_unit_id' => $farmasi->id,
        ]);

        $this->post(route('admin.pengaduan.koordinasi', $pengaduan->kode_tiket), [
            'isi' => 'Tolong sampaikan kronologi lengkap kejadian di kamar rawat.',
        ])->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $pesan = $pengaduan->pesan()
            ->where('peran', 'admin')
            ->where('kanal', PesanPengaduan::KANAL_UNIT)
            ->first();

        $this->assertNotNull($pesan);
        $this->assertSame('Tolong sampaikan kronologi lengkap kejadian di kamar rawat.', $pesan->isi);
        $this->assertTrue($pesan->jalurUnit());

        // Pesan koordinasi tidak mengubah tahap tiket.
        $this->assertSame(StatusPengaduan::Diproses, $pengaduan->fresh()->status);
    }

    public function test_koordinasi_unit_ditolak_saat_tiket_belum_ditugaskan(): void
    {
        $pengaduan = Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'kode_tiket' => 'ADUAN-KOORD-99',
            'master_unit_id' => null,
        ]);

        $this->post(route('admin.pengaduan.koordinasi', $pengaduan->kode_tiket), [
            'isi' => 'Pesan sebelum unit ditugaskan.',
        ])->assertSessionHasErrors('isi');

        $this->assertCount(0, $pengaduan->pesan()->where('kanal', PesanPengaduan::KANAL_UNIT)->get());
    }

    public function test_balasan_admin_bisa_melampiri_lebih_dari_satu_berkas(): void
    {
        Storage::fake('public');

        $pengaduan = $this->tiketDiproses('ADUAN-CHAT-05');

        $this->post(route('admin.pengaduan.balas', $pengaduan->kode_tiket), [
            'isi' => 'Bersama balasan ini kami lampirkan berita acara beserta fotonya.',
            'lampiran' => [
                UploadedFile::fake()->create('berita-acara.pdf', 90, 'application/pdf'),
                UploadedFile::fake()->create('foto.jpg', 60, 'image/jpeg'),
            ],
        ])->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $balasan = $pengaduan->pesan()->where('peran', 'admin')->latest('id')->first();

        $this->assertCount(2, $balasan->daftarLampiran());

        foreach ($balasan->daftarLampiran() as $path) {
            Storage::disk('public')->assertExists($path);
        }

        // Nama kedua berkas terbaca di kolom percakapan halaman detail.
        $tampilan = $this->get(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertOk()
            ->getContent();

        foreach ($balasan->daftarLampiran() as $path) {
            $this->assertStringContainsString(basename($path), $tampilan);
        }

        // Label tombol lampiran hanya mengubah teksnya, bukan seluruh isinya,
        // supaya ikon paperclip tetap ada dan input file tidak terhapus.
        $this->assertStringNotContainsString('this.parentNode.querySelector', $tampilan);
        $this->assertStringContainsString('name="lampiran[]"', $tampilan);
    }

    public function test_foto_dari_pelapor_tampil_sebagai_pratinjau_di_kolom_chat(): void
    {
        Storage::fake('public');

        $pengaduan = $this->tiketDiproses('ADUAN-FOTO-06');

        $this->verifikasiPelapor($pengaduan);

        $this->post(route('pengaduan.pesan', $pengaduan->kode_tiket), [
            'isi' => 'Foto antrean di loket pendaftaran pagi ini.',
            'lampiran' => [UploadedFile::fake()->create('antrean-loket.jpg', 80, 'image/jpeg')],
        ])->assertRedirect(route('pengaduan.lacak'));

        $foto = $pengaduan->pesan()->firstWhere('peran', 'pelapor')->daftarLampiran()[0];

        // Halaman admin harus memuat gambar itu sebagai pratinjau yang
        // dibungkus tautan, bukan sekadar nama berkas yang tidak terbaca foto.
        $this->get(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertSee('src="'.Storage::disk('public')->url($foto).'"', false)
            ->assertSee('<a href="'.Storage::disk('public')->url($foto).'" target="_blank" rel="noopener">', false);
    }

    public function test_foto_lampiran_aduan_tampil_sebagai_pratinjau_di_halaman_admin(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->create('bukti-parkir.jpg', 50, 'image/jpeg')->store('lampiran', 'public');

        $pengaduan = $this->tiketDiproses('ADUAN-FOTO-07');
        $pengaduan->forceFill(['lampiran' => [$path]])->save();

        $this->get(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertSee('src="'.Storage::disk('public')->url($path).'"', false);
    }

    public function test_balasan_admin_tidak_perlu_admin_tiket_sudah_selesai(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-CHAT-02', StatusPengaduan::Selesai);

        $this->post(route('admin.pengaduan.balas', $pengaduan->kode_tiket), [
            'isi' => 'Terima kasih sudah menunggu, beberapa keterangan saya kirimkan kembali.',
        ])->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $this->assertTrue($pengaduan->fresh()->status->selesai());
        $this->assertSame(1, $pengaduan->pesan()->where('peran', 'admin')->count());
    }

    public function test_balasan_kosong_ditolak(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-CHAT-03');

        $this->from(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->post(route('admin.pengaduan.balas', $pengaduan->kode_tiket), ['isi' => ''])
            ->assertSessionHasErrors('isi');

        $this->assertCount(0, $pengaduan->pesan()->where('peran', 'admin')->get());
    }

    public function test_halaman_detail_menampilkan_kolom_tulis_balasan(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-CHAT-04');

        $this->get(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertSee('Tulis Balasan untuk Pelapor')
            ->assertSee('Kirim Balasan');
    }

    public function test_tiket_yang_tidak_ada_menghasilkan_not_found_untuk_setiap_tindakan(): void
    {
        $kode = 'ADUAN-HILANG-99';

        $this->post(route('admin.pengaduan.balas', $kode), ['isi' => 'x'])->assertNotFound();
        $this->post(route('admin.pengaduan.koordinasi', $kode), ['isi' => 'x'])->assertNotFound();
        $this->post(route('admin.pengaduan.tahap', $kode), ['tujuan' => 'diproses'])->assertNotFound();
        $this->post(route('admin.pengaduan.draf', $kode), ['draf' => 'x'])->assertNotFound();
        $this->post(route('admin.pengaduan.jawaban', $kode), ['isi' => 'x'])->assertNotFound();
        $this->post(route('admin.pengaduan.kembalikan', $kode), [])->assertNotFound();
        $this->post(route('admin.pengaduan.disposisi', $kode), ['unit' => 'IFP-01'])->assertNotFound();
        $this->post(route('admin.pengaduan.tangani-langsung', $kode))->assertNotFound();
        $this->post(route('admin.pengaduan.kasus-berat', $kode), ['aktif' => 1])->assertNotFound();
    }

    private function tiketDiproses(string $kode, ?StatusPengaduan $status = null): Pengaduan
    {
        return Pengaduan::factory()
            ->status($status ?? StatusPengaduan::Diproses)
            ->create(['kode_tiket' => $kode]);
    }

    /** Verifikasi kode tiket seperti langkah pelapor supaya sesi boleh mengirim pesan. */
    private function verifikasiPelapor(Pengaduan $pengaduan): void
    {
        $this->post(route('pengaduan.verifikasi'), [
            'kode' => $pengaduan->kode_tiket,
        ])->assertRedirect(route('pengaduan.lacak'));
    }
}
