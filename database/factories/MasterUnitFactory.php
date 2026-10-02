<?php

namespace Database\Factories;

use App\Enums\DisposisiUnit;
use App\Enums\KategoriUnit;
use App\Enums\PeranAksesUnit;
use App\Enums\StatusAksesUnit;
use App\Models\MasterUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterUnit>
 */
class MasterUnitFactory extends Factory
{
    protected $model = MasterUnit::class;

    public function definition(): array
    {
        return [
            'kode' => strtoupper(fake()->unique()->bothify('[A-Z][A-Z][A-Z]-##')),
            'nama' => fake()->words(2, true),
            'kategori' => fake()->randomElement(KategoriUnit::cases()),
            'pic' => fake()->name(),
            'jabatan_pic' => fake()->jobTitle(),
            'nip' => (string) fake()->numerify('1985######## 1993## 1 ###'),
            'kontak_wa' => fake()->numerify('081#-####-##'),
            'ekstensi' => (string) fake()->numberBetween(4100, 4799),
            'jam_layanan' => '24 Jam',
            'akun_simrs' => fake()->unique()->safeEmail(),
            'peran_akses' => fake()->randomElement(PeranAksesUnit::cases()),
            'status_akses' => fake()->randomElement(StatusAksesUnit::cases()),
            'aktif' => true,
            'koneksi_simrs' => true,
            'koneksi_simrs_terakhir' => now(),
            'disposisi' => DisposisiUnit::Terhubung,
        ];
    }

    public function terputus(): static
    {
        return $this->state(fn (): array => [
            'koneksi_simrs' => false,
            'koneksi_simrs_terakhir' => null,
            'disposisi' => DisposisiUnit::Terputus,
        ]);
    }

    public function disposisi(DisposisiUnit $disposisi): static
    {
        return $this->state(fn (): array => ['disposisi' => $disposisi]);
    }

    /** Unit tanpa PIC, dipakai untuk menguji kondisi unit yang belum lengkap. */
    public function tanpaPic(): static
    {
        return $this->state(fn (): array => [
            'pic' => null,
            'jabatan_pic' => null,
            'kontak_wa' => null,
            'ekstensi' => null,
        ]);
    }

    /** Unit nonaktif, tidak boleh muncul sebagai pilihan tujuan disposisi. */
    public function nonaktif(): static
    {
        return $this->state(fn (): array => ['aktif' => false]);
    }

    public function kategori(KategoriUnit $kategori): static
    {
        return $this->state(fn (): array => ['kategori' => $kategori]);
    }

    public function akses(StatusAksesUnit $status): static
    {
        return $this->state(fn (): array => ['status_akses' => $status]);
    }
}
