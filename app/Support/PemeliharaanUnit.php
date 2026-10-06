<?php

namespace App\Support;

use App\Enums\KategoriUnit;
use App\Enums\PeranAksesUnit;
use App\Enums\StatusAksesUnit;
use App\Models\MasterUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Penyimpanan data master unit: tambah, ubah, dan alih status aktif.
 *
 * Semua perubahan unit ikut membuang cache statistik dashboard. Tanpa itu,
 * kartu ringkasan pada beranda, monitor disposisi, dan halaman master unit
 * masih menampilkan angka unit yang sudah lama.
 *
 * Kode unit tidak pernah dibuat di dalam sistem menjadi angka urut. Admin
 * menulis kode manual dengan awalan unit, dan keunikannya dijaga oleh
 * indexes unik pada tabel.
 */
final class PemeliharaanUnit
{
    /**
     * Samakan kode unit dengan bentuk yang akan ditulis ke database.
     *
     * Kode harus dinormalkan sebelum aturan keunikan dijalankan, bukan
     * sesudahnya. Kalau urutannya dibalik, kode huruf kecil seperti "ifp-01"
     * lolos pemeriksaan keunikan karena belum ada baris huruf kecil, lalu
     * diubah menjadi "IFP-01" dan menabrak index unik. Adminnya mendapat
     * galat 500 dari database, bukan pesan validasi yang bisa diperbaiki.
     *
     * Metode ini sengaja ada terpisah dari simpan() supaya controller bisa
     * memanggilnya pada request sebelum validate(), dan supaya pemanggil lain
     * seperti seeder bisa memakai bentuk kode yang sama.
     */
    public static function normalisasiKode(?string $kode): ?string
    {
        return $kode === null ? null : Str::upper(trim($kode));
    }

    /**
     * Aturan validasi untuk tambah dan ubah unit.
     *
     * Kolom kontak sengaja boleh kosong. Unit yang baru terdaftar sering
     * belum punya PIC, dan memblokir pendaftaran unit hanya akan membuat
     * admin mencatatnya di luar sistem.
     *
     * Kode unit menerima huruf kecil juga karena admin mengetik dengan
     * keyboard Caps Lock yang tidak sengaja menyala. Huruf besarnya
     * dinormalkan saat penyimpanan, bukan ditolak di sini.
     *
     * @return array<string, mixed>
     */
    public static function aturan(?MasterUnit $unit = null): array
    {
        $id = $unit?->id;

        return [
            'kode' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+(-[A-Za-z0-9]+)*$/', $unit === null ? 'unique:master_units,kode' : 'unique:master_units,kode,'.$id],
            'nama' => ['required', 'string', 'max:120'],
            'kategori' => ['required', Rule::in(array_column(KategoriUnit::cases(), 'value'))],
            'pic' => ['nullable', 'string', 'max:120'],
            'jabatan_pic' => ['nullable', 'string', 'max:120'],
            'nip' => ['nullable', 'string', 'max:30'],
            'kontak_wa' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\-\s()]+$/'],
            'ekstensi' => ['nullable', 'string', 'max:15'],
            'jam_layanan' => ['nullable', 'string', 'max:30'],
            'akun_simrs' => ['nullable', 'email', 'max:120'],
            'peran_akses' => ['required', Rule::in(array_column(PeranAksesUnit::cases(), 'value'))],
            'status_akses' => ['required', Rule::in(array_column(StatusAksesUnit::cases(), 'value'))],
            'aktif' => ['required', 'boolean'],
        ];
    }

    /**
     * Simpan unit baru atau perbarui unit lama.
     *
     * @param  array<string, mixed>  $data
     */
    public static function simpan(array $data, ?MasterUnit $unit = null): MasterUnit
    {
        $data['kode'] = self::normalisasiKode($data['kode']);
        $data['aktif'] = (bool) $data['aktif'];

        // Kolom kontak dikosongkan jadi null, bukan string kosong, supaya
        // pencarian "belum punya PIC" tidak menemukan unit yang isinya spasi.
        foreach (['pic', 'jabatan_pic', 'nip', 'kontak_wa', 'ekstensi', 'jam_layanan', 'akun_simrs'] as $kosong) {
            if (! isset($data[$kosong]) || trim((string) $data[$kosong]) === '') {
                $data[$kosong] = null;
            }
        }

        return DB::transaction(function () use ($data, $unit): MasterUnit {
            if ($unit === null) {
                // Unit baru belum pernah ditugaskan tiket, jadi catatan
                // heartbeat SIMRS-nya dikosongkan sampai ada penugasan pertama.
                $data['koneksi_simrs'] = false;
                $data['koneksi_simrs_terakhir'] = null;

                $unit = MasterUnit::create($data);
            } else {
                $unit->fill($data)->save();
            }

            StatistikDashboard::lupaCache();

            return $unit;
        });
    }

    /**
     * Alih status aktif sebuah unit.
     *
     * Unit yang dinonaktifkan tidak dihapus supaya riwayat penugasan tiket
     * lama tetap punya nama unit yang bisa dibaca.
     */
    public static function alihStatus(MasterUnit $unit): MasterUnit
    {
        $unit->aktif = ! $unit->aktif;
        $unit->save();

        StatistikDashboard::lupaCache();

        return $unit;
    }
}
