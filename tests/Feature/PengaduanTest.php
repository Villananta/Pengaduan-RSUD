<?php

namespace Tests\Feature;

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
        $this->assertSame('fasilitas', $pengaduan->kategori);
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
}
