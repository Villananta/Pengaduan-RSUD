<?php

namespace Database\Factories;

use App\Enums\KategoriPengaduan;
use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pengaduan>
 */
class PengaduanFactory extends Factory
{
    protected $model = Pengaduan::class;

    public function definition(): array
    {
        // Nama unit pada kolom teks dan master_unit_id harus berasal dari
        // baris yang sama supaya namaUnit() tidak pernah berbeda dengan
        // unit yang benar-benar ditugaskan.
        $unit = MasterUnit::inRandomOrder()->first();

        return [
            'kode_tiket' => 'ADUAN-'.now()->format('Ymd').'-'.strtoupper(fake()->bothify('?????')),
            'kategori' => fake()->randomElement(KategoriPengaduan::cases()),
            'nama_lengkap' => fake()->name(),
            'nrm' => fake()->numerify('##-##-##-##'),
            'no_wa' => '08'.fake()->numerify('##########'),
            'email' => fake()->safeEmail(),
            'alamat' => fake()->address(),
            'waktu_kejadian' => fake()->dateTimeBetween('-3 months', '-1 day'),
            'unit' => $unit?->nama ?? fake()->randomElement(config('pengaduan.units')),
            'master_unit_id' => $unit?->id,
            'subjek' => fake()->sentence(4),
            'deskripsi' => fake()->paragraph(),
            'lampiran' => [],
            'status' => StatusPengaduan::Diterima,
        ];
    }

    public function selesai(?CarbonInterface $pada = null): static
    {
        $pada ??= now();

        return $this->state(fn (): array => [
            'status' => StatusPengaduan::Selesai,
            'selesai_at' => $pada,
        ]);
    }

    public function status(StatusPengaduan $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
