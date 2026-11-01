<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hanya MySQL yang membandingkan token tanpa peduli huruf besar/kecil
        // lewat collation utf8mb4_unicode_ci. SQLite sudah case-sensitive,
        // jadi tidak ada yang perlu diubah di sana.
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('master_units', function (Blueprint $table) {
            // Token acak 40 karakter; membandingkannya secara biner menjaga
            // seluruh entropi itu tetap terpakai saat tautan dicari.
            $table->string('undangan_token', 64)
                ->nullable()
                ->collation('utf8mb4_bin')
                ->change();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('master_units', function (Blueprint $table) {
            $table->string('undangan_token', 64)
                ->nullable()
                ->collation('utf8mb4_unicode_ci')
                ->change();
        });
    }
};
