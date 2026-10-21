<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('master_units', function (Blueprint $table) {
            // Unit baru default 'menunggu_investigasi' (bukan 'terhubung')
            // Agar unit baru tidak otomatis dianggap siap menerima tiket
            // Admin harus eksplisit set ke 'terhubung' saat unit siap (PIC, WA, dll)
            $table->string('disposisi', 30)
                ->default('menunggu_investigasi')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_units', function (Blueprint $table) {
            $table->string('disposisi', 30)
                ->default('terhubung')
                .change();
        });
    }
};
