<?php

use App\Enums\PeranAksesUnit;
use App\Enums\PeranPengguna;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Akun lama belum punya peran. Konsol humas adalah pintu masuk yang
            // lebih dulu dipakai, jadi bawaan admin paling masuk akal; PIC unit
            // ditandai eksplisit saat relasinya ke unit diisi.
            $table->string('peran', 20)->default(PeranPengguna::Admin->value)->after('email');
        });

        Schema::create('master_unit_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('master_unit_id')->constrained('master_units')->cascadeOnDelete();

            // Peran PIC di dalam unit memakai enum yang sudah dipakai master
            // unit, supaya seorang petugas tidak disebut berbeda antara form
            // unit dan akunnya sendiri.
            $table->string('peran_akses', 30)->default(PeranAksesUnit::KepalaUnit->value);

            $table->timestamps();

            // Satu akun cukup tercatat sekali per unit; perubahan peran diatur
            // dengan menyunting baris yang ada, bukan menambah baris baru.
            $table->unique(['user_id', 'master_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_unit_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('peran');
        });
    }
};
