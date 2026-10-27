<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaduan', function (Blueprint $table) {
            // Kategori sekarang jadi dimensi filter di daftar humas, padahal
            // dulu hanya ditampilkan. Index ini menjaga penyaringan tetap
            // murah saat jumlah tiket bertambah.
            $table->index('kategori');
        });
    }

    public function down(): void
    {
        Schema::table('pengaduan', function (Blueprint $table) {
            $table->dropIndex(['kategori']);
        });
    }
};
