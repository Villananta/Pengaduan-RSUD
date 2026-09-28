<?php

namespace Database\Factories;

use App\Enums\KategoriPengaduan;
use App\Enums\StatusPengaduan;
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
        return [
            'kode_tiket' => 'ADUAN-'.now()->format('Ymd').'-'.strtoupper(fake()->bothify('?????')),
            'kategori' => fake()->randomElement(KategoriPengaduan::cases()),
            'nama_lengkap' => fake()->name(),
            'nrm' => fake()->numerify('##-##-##-##'),
            'no_wa' => '08'.fake()->numerify('##########'),
            'email' => fake()->safeEmail(),
            'alamat' => fake()->address(),
            'waktu_kejadian' => fake()->dateTimeBetween('-3 months', '-1 day'),
            'unit' => fake()->randomElement(config('pengaduan.units')),
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
