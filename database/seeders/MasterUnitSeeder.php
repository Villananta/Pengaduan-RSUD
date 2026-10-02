<?php

namespace Database\Seeders;

use App\Enums\DisposisiUnit;
use App\Enums\KategoriUnit;
use App\Enums\PeranAksesUnit;
use App\Enums\StatusAksesUnit;
use App\Models\MasterUnit;
use Illuminate\Database\Seeder;

/**
 * Daftar unit yang menjadi acuan konsol admin: nama, kode, profil PIC,
 * kondisi koneksi SIMRS, dan disposisi terakhir.
 *
 * Data PIC di sini masih berupa contoh awal untuk membangun halaman master
 * unit. Angkanya belum pernah dikonfirmasi ECD, jadi admin tetap bisa
 * menyuntingnya dari form unit.
 */
class MasterUnitSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->data() as $baris) {
            MasterUnit::updateOrCreate(
                ['kode' => $baris['kode']],
                $baris,
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function data(): array
    {
        return [
            $this->baris(
                kode: 'IFP-01',
                nama: 'Instalasi Farmasi Pusat',
                kategori: KategoriUnit::PenunjangKlinis,
                pic: 'Dr. apt. Hendra S., S.Farm',
                jabatan: 'Ka. Instalasi Farmasi',
                wa: '0811-3344-01',
                ekstensi: '4102',
                jam: '24 Jam',
                akun: 'hendra.farmasi@rsudsoetomo.go.id',
                akses: StatusAksesUnit::AkunAktif,
                disposisi: DisposisiUnit::SedangInvestigasi,
            ),
            $this->baris(
                kode: 'DFR-02',
                nama: 'Depo Farmasi Rawat Jalan GBPT',
                kategori: KategoriUnit::PenunjangKlinis,
                pic: 'apt. Rina K., S.Farm',
                jabatan: 'Supervisor Depo GBPT',
                wa: '0812-4455-88',
                ekstensi: '4105',
                jam: '07:00-21:00',
                akun: 'rina.depo@rsudsoetomo.go.id',
                akses: StatusAksesUnit::AkunAktif,
                disposisi: DisposisiUnit::SedangInvestigasi,
            ),
            $this->baris(
                kode: 'IRS-03',
                nama: 'Instalasi Radiologi Sentral & Diagnostik',
                kategori: KategoriUnit::PenunjangKlinis,
                pic: 'dr. Danang, Sp.Rad',
                jabatan: 'Ka. Instalasi Radiologi',
                wa: '0813-8899-23',
                ekstensi: '4210',
                jam: '24 Jam',
                akun: 'danang.radiologi@rsudsoetomo.go.id',
                akses: StatusAksesUnit::UndanganTerkirim,
                disposisi: DisposisiUnit::MenungguInvestigasi,
            ),
            $this->baris(
                kode: 'IBT-04',
                nama: 'Instalasi Bedah Terpadu (GBPT)',
                kategori: KategoriUnit::InstalasiMedis,
                pic: 'Dr. dr. Bambang, Sp.B',
                jabatan: 'Kepala Instalasi OK',
                wa: '0811-9012-44',
                ekstensi: '4301',
                jam: '24 Jam',
                akun: 'bambang.ok@rsudsoetomo.go.id',
                akses: StatusAksesUnit::AkunAktif,
                disposisi: DisposisiUnit::MenungguInfoTambahan,
            ),
            $this->baris(
                kode: 'KSA-07',
                nama: 'KSM Kesehatan Anak',
                kategori: KategoriUnit::InstalasiMedis,
                pic: 'dr. Ahmad, Sp.A(K)',
                jabatan: 'Ketua KSM Pediatri',
                wa: '0812-7890-11',
                ekstensi: '4412',
                jam: '07:30-16:00',
                akun: null,
                akses: StatusAksesUnit::BelumDiundang,
                disposisi: DisposisiUnit::MenungguInvestigasi,
            ),
            $this->baris(
                kode: 'IGD-02',
                nama: 'Instalasi Gawat Darurat',
                kategori: KategoriUnit::InstalasiMedis,
                pic: 'dr. Laksmi P., Sp.EM',
                jabatan: 'Kepala Instalasi IGD',
                wa: '0812-3344-90',
                ekstensi: '4100',
                jam: '24 Jam',
                akun: 'igd.daftar@rsudsoetomo.go.id',
                akses: StatusAksesUnit::AkunAktif,
                disposisi: DisposisiUnit::Terhubung,
            ),
            $this->baris(
                kode: 'ILP-06',
                nama: 'Instalasi Rawat Inap',
                kategori: KategoriUnit::LayananRawatInap,
                pic: 'Ns. Tri, S.Kep',
                jabatan: 'Kepala Instalasi Rawat Inap',
                wa: '0815-6677-43',
                ekstensi: '4610',
                jam: '24 Jam',
                akun: 'tri.rawatinap@rsudsoetomo.go.id',
                akses: StatusAksesUnit::AkunAktif,
                disposisi: DisposisiUnit::Terhubung,
            ),
            $this->baris(
                kode: 'IRJ-05',
                nama: 'Instalasi Rawat Jalan (Poliklinik)',
                kategori: KategoriUnit::LayananRawatInap,
                pic: 'dr. Surya N., Sp.PD',
                jabatan: 'Kepala Poliklinik',
                wa: '0811-3456-99',
                ekstensi: '4308',
                jam: '07:00-17:00',
                akun: 'poliklinik@rsudsoetomo.go.id',
                akses: StatusAksesUnit::BelumDiundang,
                disposisi: DisposisiUnit::Terputus,
                koneksi: false,
            ),
            $this->baris(
                kode: 'ADM-08',
                nama: 'Loket Kasir & Administrasi Pasien',
                kategori: KategoriUnit::LayananAdministrasi,
                pic: 'Bpk. Haryadi, SE',
                jabatan: 'Koord. Keuangan & Loket',
                wa: '0811-7788-20',
                ekstensi: '4702',
                jam: '07:00-17:00',
                akun: 'administrasi@rsudsoetomo.go.id',
                akses: StatusAksesUnit::UndanganTerkirim,
                disposisi: DisposisiUnit::Terhubung,
            ),
        ];
    }

    /**
     * Bentuk satu baris unit beserta nilai bawaan kolom kontak.
     *
     * NIP dibiarkan kosong karena formatnya berbeda antara PNS dan tenaga
     * kontrak, jadi lebih aman diisi admin dari form. Unit yang koneksinya
     * terputus juga dikosongkan waktu pengecekan terakhirnya, supaya panel
     * status koneksi di monitor disposisi tidak menampilkan koneksi hidup
     * pada unit yang sudah dimatikan.
     *
     * @return array<string, mixed>
     */
    private function baris(
        string $kode,
        string $nama,
        KategoriUnit $kategori,
        string $pic,
        string $jabatan,
        string $wa,
        string $ekstensi,
        string $jam,
        ?string $akun,
        StatusAksesUnit $akses,
        DisposisiUnit $disposisi,
        bool $koneksi = true,
    ): array {
        return [
            'kode' => $kode,
            'nama' => $nama,
            'kategori' => $kategori,
            'pic' => $pic,
            'jabatan_pic' => $jabatan,
            'nip' => null,
            'kontak_wa' => $wa,
            'ekstensi' => $ekstensi,
            'jam_layanan' => $jam,
            'akun_simrs' => $akun,
            'peran_akses' => PeranAksesUnit::KepalaUnit,
            'status_akses' => $akses,
            'aktif' => true,
            'koneksi_simrs' => $koneksi,
            'koneksi_simrs_terakhir' => $koneksi ? now() : null,
            'disposisi' => $disposisi,
        ];
    }
}
