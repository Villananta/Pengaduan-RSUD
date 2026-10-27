<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riwayat_status_pengaduan', function (Blueprint $table) {
            // Penetapan kategori juga menulis tahap asal dan tujuan yang sama,
            // jadi barisnya butuh penanda sendiri agar tidak terbaca sebagai
            // perpindahan status di halaman riwayat.
            $table->boolean('is_kategori')->default(false)->after('is_keparahan');
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_status_pengaduan', function (Blueprint $table) {
            $table->dropColumn('is_kategori');
        });
    }
};
