<?php

namespace Tests\Feature;

use App\Enums\KategoriPengaduan;
use App\Enums\StatusPengaduan;
use App\Models\Pengaduan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PengaduanTest extends TestCase
{
    use RefreshDatabase;

    /** Data formulir yang valid untuk pengajuan aduan. */
    private function form(array $ubah = []): array
    {
        return array_merge([
            'kategori' => 'fasilitas',
            'nama_lengkap' => 'Budi Santoso',
            'nrm' => '12-34-56-78',
            'no_wa' => '081234567890',
            'email' => 'budi@example.com',
            'alamat' => 'Jl. Dharmawangsa No. 12, Surabaya',
            'waktu_kejadian' => '2026-09-24T10:30',
            'unit' => 'Instalasi Farmasi',
            'subjek' => 'Keterlambatan pengambilan resep obat kronis',
            'deskripsi' => 'Pasien menunggu lebih dari 4 jam untuk pengambilan obat kronis.',
            'persetujuan' => '1',
        ], $ubah);
    }

    /** Verifikasi kepemilikan tiket lewat POST, sama seperti langkah pelapor. */
    private function verifikasi(Pengaduan $pengaduan): void
    {
        $this->post(route('pengaduan.verifikasi'), [
            'kode' => $pengaduan->kode_tiket,
            'nrm' => $pengaduan->nrm,
        ])->assertRedirect(route('pengaduan.lacak'));
    }

    public function test_halaman_buat_aduan_dapat_diakses(): void
    {
        $this->get(route('pengaduan.create'))
            ->assertOk()
            ->assertSee('Buat Aduan Baru')
            ->assertSee('Pilihan Kategori Masalah');
    }

    public function test_pengaduan_dapat_disimpan(): void
    {
        $this->post(route('pengaduan.store'), $this->form())
            ->assertRedirect();

        $this->assertDatabaseCount('pengaduan', 1);
        $pengaduan = Pengaduan::first();
        $this->assertSame(KategoriPengaduan::Fasilitas, $pengaduan->kategori);
        $this->assertStringStartsWith('ADUAN-', $pengaduan->kode_tiket);
        $this->assertSame(StatusPengaduan::Diterima, $pengaduan->status);
        $this->assertNull($pengaduan->selesai_at);

        $this->get(route('pengaduan.sukses', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertSee($pengaduan->kode_tiket);
    }

    public function test_tahap_awal_tercatat_di_riwayat_status(): void
    {
        $this->post(route('pengaduan.store'), $this->form());

        $riwayat = Pengaduan::first()->riwayatStatus;

        $this->assertCount(1, $riwayat);
        $this->assertTrue($riwayat->first()->dariAwal());
        $this->assertSame(StatusPengaduan::Diterima, $riwayat->first()->ke);
    }

    public function test_kode_tiket_tidak_bisa_dimasukkan_melalui_mass_assignment(): void
    {
        $pengaduan = Pengaduan::buat(
            collect($this->form())->except(['persetujuan'])->all(),
            'ADUAN-20260925-SISSTEM',
        );

        $pengaduan->fill(['kode_tiket' => 'ADUAN-20260925-PALING']);

        $this->assertSame('ADUAN-20260925-SISSTEM', $pengaduan->kode_tiket);
    }

    public function test_validasi_wajib_berfungsi(): void
    {
        $this->post(route('pengaduan.store'), [])
            ->assertSessionHasErrors([
                'kategori', 'nama_lengkap', 'nrm', 'no_wa', 'email', 'alamat',
                'waktu_kejadian', 'unit', 'subjek', 'deskripsi', 'persetujuan',
            ]);
    }

    public function test_kategori_harus_sesuai_enum(): void
    {
        $this->post(route('pengaduan.store'), $this->form(['kategori' => 'lainnya']))
            ->assertSessionHasErrors('kategori');

        $this->assertDatabaseCount('pengaduan', 0);
    }

    public function test_nomor_whatsapp_harus_format_telepon(): void
    {
        $this->post(route('pengaduan.store'), $this->form(['no_wa' => 'bukan-nomor']))
            ->assertSessionHasErrors('no_wa');

        $this->post(route('pengaduan.store'), $this->form(['no_wa' => '0812 3456 7890']))
            ->assertSessionHasErrors('no_wa');

        $this->assertDatabaseCount('pengaduan', 0);
    }

    public function test_waktu_kejadian_tidak_boleh_masa_depan(): void
    {
        $this->post(route('pengaduan.store'), $this->form([
            'waktu_kejadian' => now()->addYear()->format('Y-m-d\TH:i'),
        ]))->assertSessionHasErrors('waktu_kejadian');

        $this->assertDatabaseCount('pengaduan', 0);
    }

    public function test_unit_harus_terdaftar(): void
    {
        $this->post(route('pengaduan.store'), $this->form(['unit' => 'Unit Asing']))
            ->assertSessionHasErrors('unit');

        $this->assertDatabaseCount('pengaduan', 0);
    }

    public function test_lampiran_dibatasi_jumlah_dan_jenis(): void
    {
        Storage::fake('s3');

        $this->post(route('pengaduan.store'), $this->form([
            'lampiran' => collect(range(1, 6))
                ->map(fn () => UploadedFile::fake()->create('bukti.jpg', 10, 'image/jpeg'))
                ->all(),
        ]))->assertSessionHasErrors('lampiran');

        $this->post(route('pengaduan.store'), $this->form([
            'lampiran' => [UploadedFile::fake()->create('bukti.exe', 10, 'application/x-msdownload')],
        ]))->assertSessionHasErrors('lampiran.0');

        $this->assertDatabaseCount('pengaduan', 0);
    }

    public function test_halaman_lacak_tiket_dapat_diakses(): void
    {
        $this->get(route('pengaduan.lacak'))
            ->assertOk()
            ->assertSee('Lacak Status Pengaduan')
            ->assertSee('Lengkapi Kode Tiket dan NRM')
            ->assertDontSee('Alur Prosedur 12 Hari Kerja');
    }

    public function test_rincian_tiket_hanya_terbuka_bila_nrm_cocok(): void
    {
        $pengaduan = Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-20260925-NRMM1',
            'nrm' => '99-88-77-66',
            'nama_lengkap' => 'Siti Aminah',
            'subjek' => 'Keterlambatan Penyerahan Obat Resep',
            'status' => StatusPengaduan::Revisi,
        ]);

        // Kode saja tidak cukup.
        $this->get(route('pengaduan.lacak', ['kode' => $pengaduan->kode_tiket]))
            ->assertOk()
            ->assertDontSee('Siti Aminah')
            ->assertDontSee('99-88-77-66');

        // NRM salah tetap ditolak.
        $this->post(route('pengaduan.verifikasi'), [
            'kode' => $pengaduan->kode_tiket,
            'nrm' => '11-11-11-11',
        ])->assertRedirect(route('pengaduan.lacak'));

        $this->get(route('pengaduan.lacak'))
            ->assertOk()
            ->assertSee('Tiket Tidak Ditemukan')
            ->assertSee('tidak sesuai dengan data kami')
            ->assertDontSee('Siti Aminah');

        // Kode + NRM benar membuka rincian.
        $this->post(route('pengaduan.verifikasi'), [
            'kode' => $pengaduan->kode_tiket,
            'nrm' => '99887766',
        ])->assertRedirect(route('pengaduan.lacak'))->assertSessionHasNoErrors();

        $this->get(route('pengaduan.lacak'))->assertOk()
            ->assertSee('Siti Aminah')
            ->assertSee('Keterlambatan Penyerahan Obat Resep')
            ->assertSee('Perlu Revisi')
            ->assertSee('Alur Prosedur 12 Hari Kerja')
            ->assertSee('Tindakan Diperlukan: Unggah Bukti Tambahan');
    }

    public function test_nrm_tidak_pernah_muncul_di_url(): void
    {
        $pengaduan = Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-20260925-URLAMAN',
            'nrm' => '99-88-77-66',
        ]);

        $respons = $this->post(route('pengaduan.verifikasi'), [
            'kode' => $pengaduan->kode_tiket,
            'nrm' => '99-88-77-66',
        ]);

        $respons->assertRedirect(route('pengaduan.lacak'));
        $this->assertStringNotContainsString('nrm', $respons->headers->get('Location'));
        $this->assertStringNotContainsString('99-88-77-66', $respons->headers->get('Location'));

        $halaman = $this->get(route('pengaduan.lacak'));
        $this->assertStringNotContainsString('nrm=', $halaman->headers->get('Location') ?? '');
    }

    public function test_lacak_tiket_menampilkan_detail(): void
    {
        $pengaduan = Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-20260925-TESTX',
            'nrm' => '99-88-77-66',
            'nama_lengkap' => 'Siti Aminah',
            'subjek' => 'Keterlambatan Penyerahan Obat Resep',
            'status' => StatusPengaduan::Revisi,
        ]);

        $this->verifikasi($pengaduan);

        $this->get(route('pengaduan.lacak'))
            ->assertOk()
            ->assertSee($pengaduan->kode_tiket)
            ->assertSee('Keterlambatan Penyerahan Obat Resep')
            ->assertSee('Perlu Revisi')
            ->assertSee('Status Pengaduan')
            ->assertSee('Alur Prosedur 12 Hari Kerja')
            ->assertSee('Tindakan Diperlukan: Unggah Bukti Tambahan')
            ->assertSee('Siti Aminah');
    }

    public function test_lampiran_aduan_bisa_dilihat_pelapor(): void
    {
        Storage::fake('s3');

        $path = UploadedFile::fake()->create('bukti-resep.jpg', 40, 'image/jpeg')->store('lampiran', 'public');
        $pengaduan = Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-20260925-BUKTI1',
            'nrm' => '77-66-55-44',
            'lampiran' => [$path],
        ]);

        $this->verifikasi($pengaduan);

        $this->get(route('pengaduan.lacak'))
            ->assertOk()
            ->assertSee('Lampiran Bukti (1 berkas)')
            ->assertSee(basename($path));
    }

    public function test_pelapor_tidak_bisa_mengubah_status_sendiri(): void
    {
        $pengaduan = Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-20260925-UBAH1']);

        $this->verifikasi($pengaduan);

        $this->get(route('pengaduan.lacak'))
            ->assertOk()
            ->assertDontSee('Ubah Tahap Pengaduan')
            ->assertSee('Tulis Balasan atau Unggah Lampiran');

        $this->post('/lacak-tiket/'.$pengaduan->kode_tiket.'/tahap', ['status' => 'selesai'])
            ->assertNotFound();

        $this->assertSame(StatusPengaduan::Diterima, $pengaduan->fresh()->status);
    }

    public function test_pesan_hanya_bisa_dikirim_setelah_nrm_diverifikasi(): void
    {
        $pengaduan = Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-20260925-GATE1']);

        $this->post(route('pengaduan.pesan', $pengaduan->kode_tiket), ['isi' => 'Percobaan tanpa verifikasi'])
            ->assertRedirect(route('pengaduan.lacak'))
            ->assertSessionHasErrors('kode');

        $this->assertDatabaseCount('pesan_pengaduan', 0);

        $this->verifikasi($pengaduan);

        $this->post(route('pengaduan.pesan', $pengaduan->kode_tiket), ['isi' => 'Pesan setelah verifikasi'])
            ->assertRedirect(route('pengaduan.lacak'))
            ->with('sukses');

        $this->assertDatabaseHas('pesan_pengaduan', [
            'pengaduan_id' => $pengaduan->id,
            'peran' => 'pelapor',
            'isi' => 'Pesan setelah verifikasi',
        ]);
    }

    public function test_sesi_verifikasi_tidak_berpindah_ke_tiket_lain(): void
    {
        $pertama = Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-20260925-SESI01']);
        $kedua = Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-20260925-SESI02']);

        $this->verifikasi($pertama);

        $this->post(route('pengaduan.pesan', $kedua->kode_tiket), ['isi' => 'Mencoba ke tiket lain'])
            ->assertRedirect(route('pengaduan.lacak'))
            ->assertSessionHasErrors('kode');

        $this->assertDatabaseMissing('pesan_pengaduan', ['pengaduan_id' => $kedua->id]);
    }

    public function test_pesan_pelapor_dan_balasan_admin_tersimpan(): void
    {
        $pengaduan = Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-20260925-CHAT1',
            'status' => StatusPengaduan::Diproses,
        ]);

        $this->verifikasi($pengaduan);

        $this->post(route('pengaduan.pesan', $pengaduan->kode_tiket), ['isi' => 'Apakah bisa diproses lebih cepat?'])
            ->assertRedirect(route('pengaduan.lacak'));

        $this->assertDatabaseHas('pesan_pengaduan', [
            'pengaduan_id' => $pengaduan->id,
            'peran' => 'pelapor',
            'isi' => 'Apakah bisa diproses lebih cepat?',
        ]);

        // Balasan humas belum punya layar kirim sendiri, jadi pesannya
        // dibuat lewat model dan tetap harus tampil di halaman lacak tiket.
        $pengaduan->pesan()->create(['peran' => 'admin', 'isi' => 'Tim sedang menindaklanjuti.']);

        $this->assertDatabaseHas('pesan_pengaduan', [
            'pengaduan_id' => $pengaduan->id,
            'peran' => 'admin',
            'isi' => 'Tim sedang menindaklanjuti.',
        ]);

        $this->get(route('pengaduan.lacak'))
            ->assertOk()
            ->assertSee('Riwayat Tanggapan Dua Arah')
            ->assertSee('Apakah bisa diproses lebih cepat?')
            ->assertSee('Tim sedang menindaklanjuti.')
            ->assertSee('Tim Humas')
            ->assertSee('Terkirim');

        $this->post(route('pengaduan.pesan', $pengaduan->kode_tiket), ['isi' => ''])
            ->assertSessionHasErrors('isi');
    }

    public function test_pesan_bisa_dilampiri_berkas(): void
    {
        Storage::fake('s3');

        $pengaduan = Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-20260925-BERKAS',
            'status' => StatusPengaduan::Revisi,
        ]);

        $this->verifikasi($pengaduan);

        $this->post(route('pengaduan.pesan', $pengaduan->kode_tiket), [
            'isi' => 'Berikut bukti foto resume medis.',
            'lampiran' => UploadedFile::fake()->create('bukti.jpg', 120, 'image/jpeg'),
        ])->assertRedirect(route('pengaduan.lacak'));

        $pesan = $pengaduan->pesan()->firstWhere('peran', 'pelapor');

        $this->assertNotNull($pesan->lampiran);
        Storage::disk('s3')->assertExists($pesan->lampiran);

        $this->get(route('pengaduan.lacak'))
            ->assertOk()
            ->assertSee('Unggah Lampiran');
    }

    public function test_lacak_tiket_tidak_menampilkan_aduan_lain(): void
    {
        $pengaduan = Pengaduan::factory()->create(['kode_tiket' => 'ADUAN-20260925-LIST01']);

        $this->get(route('pengaduan.lacak'))
            ->assertOk()
            ->assertSee('Lengkapi Kode Tiket dan NRM')
            ->assertDontSee($pengaduan->kode_tiket)
            ->assertDontSee($pengaduan->subjek)
            ->assertDontSee($pengaduan->nama_lengkap);

        $this->post(route('pengaduan.verifikasi'), [
            'kode' => 'ADUAN-20260925-SALAH',
            'nrm' => '0000',
        ])->assertRedirect(route('pengaduan.lacak'));

        $this->get(route('pengaduan.lacak'))
            ->assertOk()
            ->assertSee('Tiket Tidak Ditemukan')
            ->assertDontSee($pengaduan->kode_tiket);
    }

    public function test_pengajuan_dibatasi_rate_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('pengaduan.store'), $this->form(['email' => "budi{$i}@example.com"]));
        }

        $response = $this->post(route('pengaduan.store'), $this->form(['email' => 'budi5@example.com']));

        $response->assertStatus(429);
        $response->assertSee('Terlalu Banyak Permintaan');
        $this->assertDatabaseCount('pengaduan', 5);
    }

    public function test_verifikasi_tiket_dibatasi_rate_limit(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('pengaduan.verifikasi'), [
                'kode' => 'ADUAN-20260925-XXXXX',
                'nrm' => '0000',
            ]);
        }

        $this->post(route('pengaduan.verifikasi'), [
            'kode' => 'ADUAN-20260925-XXXXX',
            'nrm' => '0000',
        ])->assertStatus(429);
    }
}
