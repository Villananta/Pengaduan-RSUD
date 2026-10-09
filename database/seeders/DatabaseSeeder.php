<?php

namespace Database\Seeders;

use App\Enums\PeranAksesUnit;
use App\Enums\PeranPengguna;
use App\Models\MasterUnit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(MasterUnitSeeder::class);
        $this->call(PengaduanDemoSeeder::class);
        $this->call(PengaduanDummySeeder::class);

        // Akun contoh dibuat satu kali saja supaya `db:seed` berulang
        // tidak bentrok dengan batasan unik email.
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'peran' => PeranPengguna::Admin,
                'password' => bcrypt('password'),
            ],
        );

        // Contoh akun PIC yang menempel ke unit pertama, supaya relasi
        // peran–unit bisa dicoba tanpa membuat akunnya manual lebih dulu.
        $unit = MasterUnit::query()->orderBy('id')->first();

        if ($unit === null) {
            return;
        }

        $pic = User::firstOrCreate(
            ['email' => 'pic@example.com'],
            [
                'name' => 'PIC '.$unit->nama,
                'peran' => PeranPengguna::Unit,
                'password' => bcrypt('password'),
            ],
        );

        // syncWithoutDetaching() dipilih agar `db:seed` berulang tidak
        // menggandakan baris pivot atau mengubah peran yang sudah disunting.
        $pic->unit()->syncWithoutDetaching([
            $unit->getKey() => ['peran_akses' => PeranAksesUnit::KepalaUnit->value],
        ]);
    }
}
