<?php

namespace Database\Seeders;

use App\Enums\DisposisiUnit;
use App\Models\MasterUnit;
use Illuminate\Database\Seeder;

/**
 * Daftar unit yang menjadi acuan konsol admin: nama, kode MASTER_UNITS,
 * status koneksi SIMRS, dan disposisi terakhir.
 */
class MasterUnitSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->data() as $baris) {
            MasterUnit::updateOrCreate(
                ['kode' => $baris['kode']],
                [
                    'nama' => $baris['nama'],
                    'koneksi_simrs' => $baris['koneksi_simrs'],
                    'koneksi_simrs_terakhir' => $baris['koneksi_simrs'] ? now() : null,
                    'disposisi' => $baris['disposisi'],
                ],
            );
        }
    }

    /**
     * @return array<int, array{kode: string, nama: string, koneksi_simrs: bool, disposisi: DisposisiUnit}>
     */
    private function data(): array
    {
        return [
            [
                'kode' => 'IFP-01',
                'nama' => 'Instalasi Farmasi Pusat',
                'koneksi_simrs' => true,
                'disposisi' => DisposisiUnit::SedangInvestigasi,
            ],
            [
                'kode' => 'IRS-03',
                'nama' => 'Instalasi Radiologi Sentral & Diagnostik',
                'koneksi_simrs' => true,
                'disposisi' => DisposisiUnit::MenungguInvestigasi,
            ],
            [
                'kode' => 'DFR-02',
                'nama' => 'Depo Farmasi Rawat Jalan GBPT',
                'koneksi_simrs' => true,
                'disposisi' => DisposisiUnit::SedangInvestigasi,
            ],
            [
                'kode' => 'IBT-04',
                'nama' => 'Instalasi Bedah Terpadu (GBPT)',
                'koneksi_simrs' => true,
                'disposisi' => DisposisiUnit::MenungguInfoTambahan,
            ],
            [
                'kode' => 'KSA-07',
                'nama' => 'KSM Kesehatan Anak',
                'koneksi_simrs' => true,
                'disposisi' => DisposisiUnit::MenungguInvestigasi,
            ],
            [
                'kode' => 'IGD-02',
                'nama' => 'Instalasi Gawat Darurat',
                'koneksi_simrs' => true,
                'disposisi' => DisposisiUnit::Terhubung,
            ],
            [
                'kode' => 'ILP-06',
                'nama' => 'Instalasi Rawat Inap',
                'koneksi_simrs' => true,
                'disposisi' => DisposisiUnit::Terhubung,
            ],
            [
                'kode' => 'IRJ-05',
                'nama' => 'Instalasi Rawat Jalan (Poliklinik)',
                'koneksi_simrs' => false,
                'disposisi' => DisposisiUnit::Terputus,
            ],
        ];
    }
}
