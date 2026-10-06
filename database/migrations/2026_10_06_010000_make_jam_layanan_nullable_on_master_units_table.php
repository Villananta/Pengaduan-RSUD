<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Longgarkan aturan NOT NULL pada jam layanan unit.
     *
     * Kolom ini dibuat wajib dengan bawaan '24 Jam', padahal form
     * memperlakukannya opsional: validasinya nullable, PemeliharaanUnit
     * sengaja menulis null saat isinya dikosongkan, dan daftar unit sudah
     * punya teks "Jam layanan belum diisi" untuk nilai kosong. Ketiga hal
     * itu tidak pernah bisa terjadi karena kolomnya melarang null, jadi
     * admin yang mengosongkan kolom berakhir dengan galat 500 dari
     * pelanggaran batasan.
     */
    public function up(): void
    {
        Schema::table('master_units', function (Blueprint $table) {
            // Bawaan '24 Jam' sengaja dipertahankan supaya jalur yang tidak
            // menyebut kolom sama sekali, misalnya seeder, tetap sama
            // seperti sebelumnya.
            $table->string('jam_layanan', 30)->nullable()->default('24 Jam')->change();
        });
    }

    public function down(): void
    {
        // Unit yang jam layanannya kosong harus diisi lebih dulu, kalau tidak
        // menambahkan NOT NULL akan gagal di tengah proses rollback.
        DB::table('master_units')
            ->whereNull('jam_layanan')
            ->update(['jam_layanan' => '24 Jam']);

        Schema::table('master_units', function (Blueprint $table) {
            $table->string('jam_layanan', 30)->default('24 Jam')->change();
        });
    }
};
