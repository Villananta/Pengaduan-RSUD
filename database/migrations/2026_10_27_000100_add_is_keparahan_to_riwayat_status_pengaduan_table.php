<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riwayat_status_pengaduan', function (Blueprint $table) {
            // Perubahan keparahan menulis tahap asal dan tujuan yang sama,
            // jadi butuh penanda sendiri agar tidak terbaca sebagai disposisi.
            $table->boolean('is_keparahan')->default(false)->after('is_nudge');
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_status_pengaduan', function (Blueprint $table) {
            $table->dropColumn('is_keparahan');
        });
    }
};
