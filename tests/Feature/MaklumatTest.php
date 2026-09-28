<?php

namespace Tests\Feature;

use Tests\TestCase;

class MaklumatTest extends TestCase
{
    public function test_halaman_maklumat_dapat_diakses(): void
    {
        $this->get(route('maklumat.index'))
            ->assertOk()
            ->assertSee('Maklumat Pelayanan &amp; Standar SOP', false)
            ->assertSee('Piagam Maklumat Resmi')
            ->assertSee('UU No. 25 Tahun 2009')
            ->assertSee('Kepmenkes No. 129/2008')
            ->assertSee('Hak Pasien &amp; Pelapor', false)
            ->assertSee('Kewajiban Pengadu')
            ->assertSee('Standar Pelayanan Minimal (SPM) Penanganan')
            ->assertSee('Pedoman &amp; Formulir Pengaduan', false);
    }

    public function test_seluruh_poin_hak_kewajiban_dan_tahap_spm_tampil(): void
    {
        $html = view('maklumat.index')->render();

        $judul = [
            'Pelayanan Bermutu',
            'Keterbukaan Informasi Medis',
            'Persetujuan Tindakan Medis',
            'Privasi & Kerahasiaan Data',
            'Hak Mengajukan Keberatan',
            'Waktu Tanggap Sesuai SPM',
            'Akses Pengaduan Tanpa Biaya',
            'Pendampingian Mediasi',
            'Menyertakan Bukti & Kronologi',
            'Memberikan Keterangan yang Benar',
            'Bersedia Dihubungi & Dimediasi',
            'Tidak Menyalahgunakan Kanal',
            'Menjaga Kerahasiaan Medis',
        ];

        foreach ($judul as $item) {
            $this->assertStringContainsString(e($item), $html, "Point \"{$item}\" tidak tampil.");
        }

        foreach (config('pengaduan.spm') as $tahap) {
            $this->assertStringContainsString(e($tahap['nilai']), $html);
            $this->assertStringContainsString(e($tahap['judul']), $html);
            $this->assertStringContainsString(e($tahap['sla']), $html);
        }

        $this->assertStringContainsString(route('maklumat.index'), $html);
    }
}
