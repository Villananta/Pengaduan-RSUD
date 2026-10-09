<?php

namespace Database\Factories;

use App\Enums\PeranAksesUnit;
use App\Enums\PeranPengguna;
use App\Models\MasterUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * Bawaannya admin karena konsol humas yang lebih dulu dipakai; akun unit
     * dibuat lewat state picUnit() supaya sekaligus menempel ke unitnya.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'peran' => PeranPengguna::Admin,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /** Akun pemegang konsol humas. */
    public function admin(): static
    {
        return $this->state(fn (): array => ['peran' => PeranPengguna::Admin]);
    }

    /**
     * Akun PIC yang langsung dikaitkan ke satu unit.
     *
     * Peran aksesnya ditulis ke pivot begitu akun tersimpan, supaya test
     * tidak perlu meng-attach relasinya secara manual.
     */
    public function picUnit(MasterUnit $unit, PeranAksesUnit $akses = PeranAksesUnit::KepalaUnit): static
    {
        return $this
            ->state(fn (): array => ['peran' => PeranPengguna::Unit])
            ->afterCreating(function (User $user) use ($unit, $akses): void {
                $user->unit()->attach($unit->getKey(), ['peran_akses' => $akses->value]);
            });
    }
}
