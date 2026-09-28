<?php

namespace Database\Factories;

use App\Enums\DisposisiUnit;
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
}
