<?php

namespace Tests\Feature;

use App\Enums\DisposisiUnit;
use App\Enums\StatusInvestigasi;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\PesanPengaduan;
use App\Models\User;
use App\Notifications\PengaduanKasusBerat;
use App\Services\TahapService;
use App\Support\DaftarUnit;
use App\Support\DetailPengaduan;
use App\Support\Sla;
use App\Support\StatistikDashboard;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
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

        $this->masukSebagaiAdmin();
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
        // Kolom disposisi unit tidak lagi ditulis oleh penugasan tiket.
        // Penulisan itulah yang dulu membuat status unit mengikuti tiket
        // terakhir dan menutupi tiket lain yang masih berjalan.
        $this->assertSame(DisposisiUnit::SedangInvestigasi, $unit->fresh()->disposisi);

        $penugasan = $pengaduan->riwayatStatus()->reorder('id', 'desc')->first();
        $this->assertSame(StatusPengaduan::Diterima, $penugasan->dari);
        $this->assertSame(StatusPengaduan::Diproses, $penugasan->ke);
    }

    public function test_mengalihkan_disposisi_tidak_menulis_status_kedua_unit(): void
    {
        $lama = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $baru = MasterUnit::where('kode', 'DFR-02')->firstOrFail();

        $pengaduan = $this->tiketDiproses('ADUAN-DISPOSISI-03');
        $pengaduan->update(['master_unit_id' => $lama->id]);

        $statusLama = $lama->disposisi;
        $statusBaru = $baru->disposisi;

        $this->post(route('admin.pengaduan.disposisi', $pengaduan->kode_tiket), [
            'unit' => $baru->kode,
        ])->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $this->assertSame($baru->id, $pengaduan->fresh()->master_unit_id);

        // Status unit lama dan baru sama-sama tidak disentuh. Dulu unit lama
        // dipaksa kembali "terhubung" dan unit baru ditandai "menunggu
        // investigasi", padahal keduanya bisa sedang memegang tiket lain.
        $this->assertSame($statusLama, $lama->fresh()->disposisi);
        $this->assertSame($statusBaru, $baru->fresh()->disposisi);
    }

    public function test_detail_tiket_membaca_status_investigasi_tiket_bukan_status_unit(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $pengaduan = $this->tiketDiproses('ADUAN-TAHAP-10', StatusPengaduan::Diterima);
        $pengaduan->update(['master_unit_id' => $unit->id]);

        // Unit sedang memegang tiket lain yang butuh data pelengkap. Status
        // itu tidak boleh menempel ke tiket ini.
        $unit->update(['disposisi' => DisposisiUnit::MenungguInfoTambahan]);

        $this->post(route('admin.pengaduan.tahap', $pengaduan->kode_tiket), [
            'tujuan' => StatusPengaduan::Diproses->value,
        ])->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $this->get(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertSee(StatusInvestigasi::SedangInvestigasi->label())
            ->assertDontSee(DisposisiUnit::MenungguInfoTambahan->label());
    }

    public function test_tahap_revisi_tidak_menulis_status_unit(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $pengaduan = $this->tiketDiproses('ADUAN-TAHAP-11');
        $pengaduan->update(['master_unit_id' => $unit->id]);

        $statusAwal = $unit->disposisi;

        $this->post(route('admin.pengaduan.tahap', $pengaduan->kode_tiket), [
            'tujuan' => StatusPengaduan::Revisi->value,
        ])->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $this->assertSame(StatusPengaduan::Revisi, $pengaduan->fresh()->status);
        $this->assertSame($statusAwal, $unit->fresh()->disposisi);
    }

    public function test_menutup_tiket_mengeluarkannya_dari_hitungan_penugasan_unit(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $pengaduan = $this->tiketDiproses('ADUAN-TUTUP-05');
        $pengaduan->update(['master_unit_id' => $unit->id]);

        $sebelum = $this->penugasanUnit($unit);
        $this->assertSame(1, $sebelum['investigasi']);

        $this->post(route('admin.pengaduan.tahap', $pengaduan->kode_tiket), [
            'tujuan' => StatusPengaduan::Selesai->value,
        ])->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $sesudah = $this->penugasanUnit($unit);

        $this->assertSame(0, $sesudah['investigasi']);
        $this->assertSame(0, $sesudah['racikan']);
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

    public function test_jalur_2_melepas_unit_dari_tiket_yang_ditangani_langsung_humas(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $pengaduan = $this->tiketDiproses('ADUAN-LANGSUNG-01', StatusPengaduan::Diterima);
        $pengaduan->update(['master_unit_id' => $unit->id]);
        $unit->update(['disposisi' => DisposisiUnit::MenungguInvestigasi]);

        // Pilihan unit dari pelapor belum diteruskan Humas, jadi tiketnya
        // belum muncul di daftar unit. Langkah pengamatan ini memakai akun PIC
        // karena konsol unit kini tertutup bagi admin humas.
        $this->masukSebagaiPicUntuk($unit);

        $this->get(route('unit.disposisi.index', $unit))
            ->assertOk()
            ->assertDontSee($pengaduan->kode_tiket);

        $this->masukSebagaiAdmin();

        $this->post(route('admin.pengaduan.tangani-langsung', $pengaduan->kode_tiket))
            ->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $pengaduan->refresh();

        $this->assertTrue($pengaduan->status->diproses());
        $this->assertNull($pengaduan->master_unit_id);

        // Pelepasan unit tetap terekam di riwayat, bukan hilang begitu saja.
        $pelepasan = $pengaduan->riwayatStatus->last();
        $this->assertStringContainsString($unit->namaLengkap(), $pelepasan->catatan);

        // Unit yang dilepas tidak perlu direset statusnya; tautan tiketnya
        // yang dilepas, sedangkan kolom disposisi unit tidak ikut ditulis.
        $this->assertSame(DisposisiUnit::MenungguInvestigasi, $unit->fresh()->disposisi);

        // Setelah Humas menangani sendiri, tautan unit tetap bersih —
        // diamati lagi sebagai PIC karena konsol unit tertutup bagi admin.
        $this->masukSebagaiPicUntuk($unit);

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
            TahapService::tujuanTersedia($pengaduan)
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

        $perpindahan = $pengaduan->riwayatStatus()->reorder('id', 'desc')->first();
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
            TahapService::tujuanTersedia($pengaduan)
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

        $perpindahan = $pengaduan->riwayatStatus()->reorder('id', 'desc')->first();
        $this->assertSame('Hasil pemeriksaan laboratori belum dilampirkan.', $perpindahan->catatan);
    }

    public function test_tiket_revisi_hanya_menawarkan_tombol_selesaikan(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-TAHAP-05', StatusPengaduan::Revisi);

        $tampilan = $this->get(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertSee('Selesaikan')
            ->getContent();

        $this->assertSame([StatusPengaduan::Selesai], TahapService::tujuanTersedia($pengaduan));
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
        $catatan = $pengaduan->riwayatStatus()->reorder('id', 'desc')->first();
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

    public function test_melepas_penandaan_kasus_berat_tidak_mengirim_notifikasi(): void
    {
        Notification::fake();

        $pengaduan = $this->tiketDiproses('ADUAN-BERAT-04');
        $pengaduan->tandaiKasusBerat(true);

        $this->post(route('admin.pengaduan.kasus-berat', $pengaduan->kode_tiket), ['aktif' => 0])
            ->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        // Pelepasan penandaan hanya koreksi humas; jejaknya sudah ada di
        // riwayat status, jadi tidak perlu kabar ke lonceng mana pun.
        Notification::assertNothingSent();
    }

    public function test_menandai_kasus_berat_mengabari_admin_lain_tanpa_diri_sendiri(): void
    {
        Notification::fake();

        $adminLain = User::factory()->admin()->create();
        $pengaduan = $this->tiketDiproses('ADUAN-BERAT-05');

        $this->post(route('admin.pengaduan.kasus-berat', $pengaduan->kode_tiket), ['aktif' => 1])
            ->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        Notification::assertSentTo($adminLain, PengaduanKasusBerat::class);
        Notification::assertNotSentTo(auth()->user(), PengaduanKasusBerat::class);
    }

    public function test_tautan_lampiran_pesan_tidak_pernah_berupa_pagar_kosong(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-LAMPIRAN-01');

        $pesan = $pengaduan->pesan()->create([
            'peran' => 'admin',
            'isi' => 'Lampiran bukti penerimaan obat.',
            'lampiran' => ['lampiran/bukti.jpg'],
        ]);

        // Induk pengaduan sengaja dilepas supaya jalur cadangan yang dulu
        // mengembalikan '#' ikut teruji.
        $pesan->setRelation('pengaduan', null);

        $this->assertSame(
            route('lampiran.pesan', ['pesan' => $pesan->id, 'file' => 'bukti.jpg']),
            $pesan->urlLampiran('lampiran/bukti.jpg'),
        );
    }

    public function test_halaman_detail_tidak_lagi_menampilkan_panel_kasus_berat(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-BERAT-03');
        $pengaduan->tandaiKasusBerat(true);

        $this->get(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertDontSee('Kasus Berat Aktif')
            ->assertDontSee('Tandai Kasus Berat')
            ->assertDontSee('Lepas Penandaan')
            ->assertDontSee(route('admin.pengaduan.kasus-berat', $pengaduan->kode_tiket));

        // Penandaan kasus berat tetap berlaku di balik layar: SLA masih
        // memakai tambahan hari kerjanya walaupun panelnya tidak tampil.
        $this->assertSame(
            Sla::hariKerja() + Sla::tambahanKasusBerat(),
            $pengaduan->fresh()->totalHariKerjaSla()
        );
    }

    public function test_tiket_selesai_tidak_menawarkan_tindakan_lagi(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-TUTUP-04', StatusPengaduan::Selesai);

        $tampilan = $this->get(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertSee('Tiket ini sudah selesai sehingga tidak ada tindakan lanjutan.')
            ->getContent();

        // Untuk tiket yang sudah tutup, alasan tampil sebagai teks dan tidak
        // ada formulir disposisi maupun tombol tahap yang masih bisa ditekan.
        $this->assertStringNotContainsString('action="'.route('admin.pengaduan.disposisi', $pengaduan->kode_tiket).'"', $tampilan);
    }

    public function test_menutup_tiket_membuang_cache_angka_dashboard(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-CACHE-01');

        // Angka dashboard diambil lebih dulu supaya nilai lamanya masuk cache.
        $sebelum = StatistikDashboard::aktif();

        $this->post(route('admin.pengaduan.tahap', $pengaduan->kode_tiket), [
            'tujuan' => StatusPengaduan::Selesai->value,
        ])->assertRedirect();

        $sesudah = StatistikDashboard::aktif();

        $this->assertSame($sebelum - 1, $sesudah);
    }

    /**
     * Balasan humas ke pelapor bukan jawaban unit.
     *
     * Angka telaah harus tetap karena yang membuktikan unit menjawab hanya
     * pesan berperan unit, dan balasan humas tidak membuang cache dashboard.
     */
    public function test_balasan_admin_tidak_menambah_angka_telaah(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-CACHE-02');

        // Angka dihangatkan lebih dulu supaya nilai lamanya masuk cache.
        $sebelum = StatistikDashboard::ringkasanKritis()['telaah'];

        $this->post(route('admin.pengaduan.balas', $pengaduan->kode_tiket), [
            'isi' => 'Instalasi sudah memeriksa catatan pemberian obat dan menyimpulkan tidak ada kesalahan dosis.',
        ])->assertRedirect();

        $sesudah = StatistikDashboard::ringkasanKritis()['telaah'];

        $this->assertSame($sebelum, $sesudah);
    }

    public function test_admin_bisa_membalas_di_kolom_percakapan(): void
    {
        $pengaduan = $this->tiketDiproses('ADUAN-CHAT-01');

        $this->post(route('admin.pengaduan.balas', $pengaduan->kode_tiket), [
            'isi' => 'Kami sudah menerima laporan Anda dan sedang memeriksa catatan service di instalasi.',
        ])->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $pengaduan->refresh();

        $balasan = $pengaduan->pesan()->where('peran', 'admin')->reorder('id', 'desc')->first();
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

    /**
     * Balasan humas tidak boleh terbaca sebagai jawaban unit.
     *
     * Balasan humas kepada pelapor dan sapaan humas ke jalur koordinasi
     * sama-sama berperan admin. Kalau keduanya ikut dihitung, tiket yang
     * unitnya masih menelusuri akan tampak selesai dan langsung melompat ke
     * Menunggu Racikan hanya karena humas menyapa.
     */
    public function test_balasan_humas_tidak_terbaca_sebagai_jawaban_unit(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $pengaduan = Pengaduan::factory()->status(StatusPengaduan::Diproses)->create([
            'kode_tiket' => 'ADUAN-HUMAS-BALAS',
            'master_unit_id' => $farmasi->id,
        ]);

        $this->post(route('admin.pengaduan.balas', $pengaduan->kode_tiket), [
            'isi' => 'Kami sudah menerima laporan Anda dan sedang memeriksa catatan service instalasi.',
        ])->assertRedirect();

        $this->post(route('admin.pengaduan.koordinasi', $pengaduan->kode_tiket), [
            'isi' => 'Mohon sampaikan kronologi lengkap kejadian di kamar rawat.',
        ])->assertRedirect();

        $detail = DetailPengaduan::dariKode($pengaduan->kode_tiket);

        $this->assertSame(StatusInvestigasi::SedangInvestigasi, $detail->investigasi);
        $this->assertCount(0, $pengaduan->pesan()->where('peran', 'unit')->get());
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

        $balasan = $pengaduan->pesan()->where('peran', 'admin')->reorder('id', 'desc')->first();

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

        $pesan = $pengaduan->pesan()->firstWhere('peran', 'pelapor');
        $url = route('lampiran.pesan', [
            'pesan' => $pesan->id,
            'file' => basename($pesan->daftarLampiran()[0]),
        ]);

        // Halaman admin harus memuat gambar itu sebagai pratinjau yang
        // dibungkus tautan, bukan sekadar nama berkas yang tidak terbaca foto.
        $this->get(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertSee('src="'.$url.'"', false)
            ->assertSee('<a href="'.$url.'" target="_blank" rel="noopener">', false);
    }

    public function test_foto_lampiran_aduan_tampil_sebagai_pratinjau_di_halaman_admin(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->create('bukti-parkir.jpg', 50, 'image/jpeg')->store('lampiran', 'public');

        $pengaduan = $this->tiketDiproses('ADUAN-FOTO-07');
        $pengaduan->forceFill(['lampiran' => [$path]])->save();

        $url = route('lampiran.pengaduan', [
            'kode' => $pengaduan->kode_tiket,
            'file' => basename($path),
        ]);

        $this->get(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertSee('src="'.$url.'"', false);
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
        $this->post(route('admin.pengaduan.disposisi', $kode), ['unit' => 'IFP-01'])->assertNotFound();
        $this->post(route('admin.pengaduan.tangani-langsung', $kode))->assertNotFound();
        $this->post(route('admin.pengaduan.kasus-berat', $kode), ['aktif' => 1])->assertNotFound();
        $this->post(route('admin.pengaduan.keparahan', $kode), ['tingkat' => 'berat'])->assertNotFound();
    }

    /**
     * Ringkasan penugasan satu unit, diambil dari baris tabel unit.
     *
     * Hitungannya menempel pada hasil query DaftarUnit, jadi unitnya harus
     * diambil dari paginator, bukan dari fresh().
     *
     * @return array{terputus: bool, investigasi: int, racikan: int}
     */
    private function penugasanUnit(MasterUnit $unit): array
    {
        $daftar = DaftarUnit::dariRequest(request());
        $baris = $daftar->unit->firstWhere('id', $unit->id);

        $this->assertNotNull($baris);

        return $daftar->penugasan($baris);
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
