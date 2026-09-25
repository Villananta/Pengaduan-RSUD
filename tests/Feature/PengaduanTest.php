<?php

namespace Tests\Feature;

use App\Enums\KategoriPengaduan;
use App\Enums\StatusPengaduan;
use App\Models\Pengaduan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengaduanTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_buat_aduan_dapat_diakses(): void
    {
        $this->get(route('pengaduan.create'))
            ->assertOk()
            ->assertSee('Buat Aduan Baru')
            ->assertSee('Pilihan Kategori Masalah');
    }

    public function test_pengaduan_dapat_disimpan(): void
    {
        $this->post(route('pengaduan.store'), [
            'kategori' => 'fasilitas',
            'nama_lengkap' => 'Budi Santoso',
            'nrm' => '12-34-56-78',
            'no_wa' => '081234567890',
            'email' => 'budi@example.com',
            'alamat' => 'Jl. Dharmawangsa No. 12, Surabaya',
            'waktu_kejadian' => '2026-09-24T10:30',
            'unit' => 'Instalasi Farmasi',
            'subjek' => 'Keterlambatan pengambilan resep obat',
            'deskripsi' => 'Pasien menunggu lebih dari 4 jam untuk pengambilan obat kronis.',
            'persetujuan' => '1',
        ])
            ->assertRedirect();

        $this->assertDatabaseCount('pengaduan', 1);
        $pengaduan = Pengaduan::first();
        $this->assertSame(KategoriPengaduan::Fasilitas, $pengaduan->kategori);
        $this->assertStringStartsWith('ADUAN-', $pengaduan->kode_tiket);

        $this->get(route('pengaduan.sukses', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertSee($pengaduan->kode_tiket);
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
        $this->post(route('pengaduan.store'), [
            'kategori' => 'lainnya',
            'nama_lengkap' => 'Budi Santoso',
            'nrm' => '12-34-56-78',
            'no_wa' => '081234567890',
            'email' => 'budi@example.com',
            'alamat' => 'Jl. Dharmawangsa No. 12, Surabaya',
            'waktu_kejadian' => '2026-09-24T10:30',
            'unit' => 'Instalasi Farmasi',
            'subjek' => 'Keterlambatan pengambilan resep obat',
            'deskripsi' => 'Pasien menunggu lebih dari 4 jam untuk pengambilan obat kronis.',
            'persetujuan' => '1',
        ])
            ->assertSessionHasErrors('kategori');

        $this->assertDatabaseCount('pengaduan', 0);
    }

    public function test_halaman_lacak_tiket_dapat_diakses(): void
    {
        $this->get(route('pengaduan.lacak'))
            ->assertOk()
            ->assertSee('Lacak Status Pengaduan')
            ->assertSee('Masukkan Kode Tiket Anda')
            ->assertDontSee('Penyelesaian Rata-rata 6.8 Hari Kerja')
            ->assertDontSee('Alur Prosedur 12 Hari Kerja');
    }

    public function test_lacak_tiket_menampilkan_detail(): void
    {
        $pengaduan = Pengaduan::create([
            'kode_tiket' => 'ADUAN-20260925-TESTX',
            'kategori' => 'medis',
            'nama_lengkap' => 'Siti Aminah',
            'nrm' => '99-88-77-66',
            'no_wa' => '081298765432',
            'email' => 'siti@example.com',
            'alamat' => 'Jl. Pahlawan No. 1, Surabaya',
            'waktu_kejadian' => '2026-09-20T09:00',
            'unit' => 'Instalasi Farmasi',
            'subjek' => 'Keterlambatan Penyerahan Obat Resep',
            'deskripsi' => 'Obat kronis belum tersedia selama tiga hari.',
            'lampiran' => [],
            'status' => 'revisi',
        ]);

        $this->get(route('pengaduan.lacak', ['kode' => $pengaduan->kode_tiket]))
            ->assertOk()
            ->assertSee($pengaduan->kode_tiket)
            ->assertSee('Keterlambatan Penyerahan Obat Resep')
            ->assertSee('Perlu Revisi')
            ->assertSee('Status Pengaduan')
            ->assertSee('Alur Prosedur 12 Hari Kerja')
            ->assertSee('Tindakan Diperlukan: Unggah Bukti Tambahan')
            ->assertSee('Siti Aminah');
    }

    public function test_pelapor_tidak_bisa_mengubah_status_sendiri(): void
    {
        $pengaduan = Pengaduan::create([
            'kode_tiket' => 'ADUAN-20260925-UBAH1',
            'kategori' => 'fasilitas',
            'nama_lengkap' => 'Rina',
            'nrm' => '55-44-33-44',
            'no_wa' => '081200000001',
            'email' => 'rina@example.com',
            'alamat' => 'Surabaya',
            'waktu_kejadian' => '2026-09-20T09:00',
            'unit' => 'Instalasi Radiologi',
            'subjek' => 'Alat MRI tidak berfungsi',
            'deskripsi' => 'Pemeriksaan MRI tertunda selama dua hari.',
            'lampiran' => [],
            'status' => 'diterima',
        ]);

        $this->get(route('pengaduan.lacak', ['kode' => $pengaduan->kode_tiket]))
            ->assertOk()
            ->assertDontSee('Ubah Tahap Pengaduan')
            ->assertSee('Status pengaduan hanya dapat diubah oleh admin humas.');

        $this->post('/lacak-tiket/'.$pengaduan->kode_tiket.'/tahap', ['status' => 'selesai'])
            ->assertNotFound();

        $this->assertSame(StatusPengaduan::Diterima, $pengaduan->fresh()->status);
    }

    public function test_admin_dapat_mengubah_status_pengaduan(): void
    {
        $pengaduan = Pengaduan::create([
            'kode_tiket' => 'ADUAN-20260925-ADMIN',
            'kategori' => 'medis',
            'nama_lengkap' => 'Dewi',
            'nrm' => '66-77-88-99',
            'no_wa' => '081200000003',
            'email' => 'dewi@example.com',
            'alamat' => 'Surabaya',
            'waktu_kejadian' => '2026-09-20T09:00',
            'unit' => 'Instalasi Bedah',
            'subjek' => 'Perawatan lambat di Ruang Melati',
            'deskripsi' => 'Periksa baru dilakukan lima jam setelah masuk.',
            'lampiran' => [],
            'status' => 'diterima',
        ]);

        $this->get(route('admin.pengaduan.index'))
            ->assertOk()
            ->assertSee('Daftar Pengaduan Masuk')
            ->assertSee($pengaduan->kode_tiket);

        $this->get(route('admin.pengaduan.show', $pengaduan->kode_tiket))
            ->assertOk()
            ->assertSee('Ubah Tahap Pengaduan');

        $this->post(route('admin.pengaduan.status', $pengaduan->kode_tiket), ['status' => 'diproses'])
            ->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $this->assertSame(StatusPengaduan::Diproses, $pengaduan->fresh()->status);

        $this->get(route('pengaduan.lacak', ['kode' => $pengaduan->kode_tiket]))
            ->assertOk()
            ->assertSee('Laporan Sedang Diinvestigasi');

        $this->post(route('admin.pengaduan.status', $pengaduan->kode_tiket), ['status' => 'dibatalkan'])
            ->assertSessionHasErrors('status');

        $this->assertSame(StatusPengaduan::Diproses, $pengaduan->fresh()->status);
    }

    public function test_pesan_pelapor_dan_balasan_admin_tersimpan(): void
    {
        $pengaduan = Pengaduan::create([
            'kode_tiket' => 'ADUAN-20260925-CHAT1',
            'kategori' => 'fasilitas',
            'nama_lengkap' => 'Bagus',
            'nrm' => '12-34-56-78',
            'no_wa' => '081200000004',
            'email' => 'bagus@example.com',
            'alamat' => 'Surabaya',
            'waktu_kejadian' => '2026-09-20T09:00',
            'unit' => 'Poli Syaraf',
            'subjek' => 'Ruang tunggu penuh',
            'deskripsi' => 'Tidak ada kursi kosong sejak pagi.',
            'lampiran' => [],
            'status' => 'diproses',
        ]);

        $this->post(route('pengaduan.pesan', $pengaduan->kode_tiket), ['isi' => 'Apakah bisa diproses lebih cepat?'])
            ->assertRedirect(route('pengaduan.lacak', ['kode' => $pengaduan->kode_tiket]));

        $this->assertDatabaseHas('pesan_pengaduan', [
            'pengaduan_id' => $pengaduan->id,
            'peran' => 'pelapor',
            'isi' => 'Apakah bisa diproses lebih cepat?',
        ]);

        $this->post(route('admin.pengaduan.balas', $pengaduan->kode_tiket), ['isi' => 'Tim sedang menindaklanjuti.'])
            ->assertRedirect(route('admin.pengaduan.show', $pengaduan->kode_tiket));

        $this->assertDatabaseHas('pesan_pengaduan', [
            'pengaduan_id' => $pengaduan->id,
            'peran' => 'admin',
            'isi' => 'Tim sedang menindaklanjuti.',
        ]);

        $this->get(route('pengaduan.lacak', ['kode' => $pengaduan->kode_tiket]))
            ->assertOk()
            ->assertSee('Kolom Chat Pengaduan')
            ->assertSee('Apakah bisa diproses lebih cepat?')
            ->assertSee('Tim sedang menindaklanjuti.')
            ->assertSee('Admin Humas');

        $this->post(route('pengaduan.pesan', $pengaduan->kode_tiket), ['isi' => ''])
            ->assertSessionHasErrors('isi');
    }

    public function test_lacak_tiket_tidak_menampilkan_aduan_lain(): void
    {
        $pengaduan = Pengaduan::create([
            'kode_tiket' => 'ADUAN-20260925-LIST01',
            'kategori' => 'fasilitas',
            'nama_lengkap' => 'Andi',
            'nrm' => '11-22-33-44',
            'no_wa' => '081200000000',
            'email' => 'andi@example.com',
            'alamat' => 'Surabaya',
            'waktu_kejadian' => '2026-09-20T09:00',
            'unit' => 'Poli Umum',
            'subjek' => 'Kursi ruang tunggu rusak',
            'deskripsi' => 'Kursi patah di ruang tunggu poli.',
            'lampiran' => [],
            'status' => 'diterima',
        ]);

        $this->get(route('pengaduan.lacak'))
            ->assertOk()
            ->assertSee('Masukkan Kode Tiket Anda')
            ->assertDontSee($pengaduan->kode_tiket)
            ->assertDontSee($pengaduan->subjek)
            ->assertDontSee($pengaduan->nama_lengkap);

        $this->get(route('pengaduan.lacak', ['kode' => 'ADUAN-20260925-SALAH']))
            ->assertOk()
            ->assertSee('Tiket Tidak Ditemukan')
            ->assertDontSee($pengaduan->kode_tiket);
    }
}
