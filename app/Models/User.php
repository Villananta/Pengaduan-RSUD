<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\PeranPengguna;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Akun pengguna aplikasi, baik pemegang konsol humas maupun PIC unit.
 */
#[Fillable(['name', 'email', 'peran', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'peran' => PeranPengguna::class,
            'password' => 'hashed',
        ];
    }

    /**
     * Unit-unit yang diurus akun ini.
     *
     * Peran PIC disimpan di pivot karena seorang petugas bisa memegang lebih
     * dari satu unit dengan peran yang berbeda di masing-masing unit.
     */
    public function unit(): BelongsToMany
    {
        return $this->belongsToMany(MasterUnit::class, 'master_unit_user')
            ->withPivot('peran_akses')
            ->withTimestamps();
    }

    /** True bila akun ini memegang konsol humas. */
    public function berperanAdmin(): bool
    {
        return $this->peran === PeranPengguna::Admin;
    }

    /** True bila akun ini hanya mengurus unitnya sendiri. */
    public function berperanUnit(): bool
    {
        return $this->peran === PeranPengguna::Unit;
    }

    /** True bila akun ini tercatat sebagai pengurus unit tersebut. */
    public function terdaftarDiUnit(MasterUnit $unit): bool
    {
        return $this->unit()->whereKey($unit->getKey())->exists();
    }
}
