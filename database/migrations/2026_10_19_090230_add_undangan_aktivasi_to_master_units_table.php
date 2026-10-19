<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_units', function (Blueprint $table) {
            // Tautan undangan memakai token acak, bukan kode unit yang tampil
            // di layar, supaya mengaktifkan akun tidak bisa ditebak lewat URL.
            // Masa berlakunya disimpan terpisah supaya tautan yang lewat waktu
            // bisa langsung ditolak tanpa perlu menghapus token lebih dulu.
            $table->string('undangan_token', 64)->nullable()->unique()->after('email');
            $table->dateTime('undangan_berlaku')->nullable()->after('undangan_token');
        });
    }

    public function down(): void
    {
        Schema::table('master_units', function (Blueprint $table) {
            $table->dropUnique(['undangan_token']);
            $table->dropColumn(['undangan_token', 'undangan_berlaku']);
        });
    }
};
