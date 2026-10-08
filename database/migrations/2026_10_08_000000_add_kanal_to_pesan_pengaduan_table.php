<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pisahkan percakapan pelapor dari kanal koordinasi humas dan unit.
     *
     * Sebelumnya semua pesan duduk di satu percakapan per tiket, sehingga
     * jawaban unit dan obrolan internal humas ikut terlihat oleh pelapor.
     * Kolom kanal ini memberi dua jalur: 'pelapor' untuk chat dengan rakyat
     * dan 'unit' untuk koordinasi humas&harr;unit yang berdiri sendiri.
     *
     * Baris yang sudah ada semuanya milik jalur pelapor, jadi cukup diisi
     * nilai bawaan tanpa memindahkan data satu pun.
     */
    public function up(): void
    {
        Schema::table('pesan_pengaduan', function (Blueprint $table) {
            $table->string('kanal', 20)->default('pelapor')->after('peran');
        });
    }

    public function down(): void
    {
        Schema::table('pesan_pengaduan', function (Blueprint $table) {
            $table->dropColumn('kanal');
        });
    }
};
