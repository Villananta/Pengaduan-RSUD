<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaduan', function (Blueprint $table) {
            // Pelapor tidak lagi memilih kategori; humas yang menentukannya
            // saat telaah. Tiket yang baru masuk harus boleh kosong dulu,
            // sedangkan tiket lama tetap menyimpan kategori yang sudah ada.
            $table->string('kategori')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pengaduan', function (Blueprint $table) {
            $table->string('kategori')->nullable(false)->change();
        });
    }
};
