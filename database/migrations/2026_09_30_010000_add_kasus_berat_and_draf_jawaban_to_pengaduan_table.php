<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaduan', function (Blueprint $table) {
            // Kasus berat menambah batas penyelesaian, jadi penandanya harus
            // tersimpan dan bukan hanya tampilan sesaat di halaman detail.
            $table->boolean('kasus_berat')->default(false)->after('selesai_at');
            $table->timestamp('kasus_berat_at')->nullable()->after('kasus_berat');

            // Draf jawaban admin humas disimpan terpisah dari pesan yang sudah
            // terkirim supaya isinya tidak pernah ikut terkirim ke pelapor.
            $table->text('draf_jawaban')->nullable()->after('kasus_berat_at');
        });
    }

    public function down(): void
    {
        Schema::table('pengaduan', function (Blueprint $table) {
            $table->dropColumn(['draf_jawaban', 'kasus_berat_at', 'kasus_berat']);
        });
    }
};
