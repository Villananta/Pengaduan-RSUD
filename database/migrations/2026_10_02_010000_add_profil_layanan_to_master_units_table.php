<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lengkapi master unit dengan data rujukan tempat melapor.
     *
     * Kolom yang ditambahkan semuanya menyimpan data kontak dan akses PIC
     * unit. Yang ada sebelumnya baru kode, nama, dan status koneksi SIMRS,
     * sehingga halaman master unit tidak bisa menampilkan siapa yang harus
     * dihubungi ketika sebuah tiket melewati batas investigasi.
     */
    public function up(): void
    {
        Schema::table('master_units', function (Blueprint $table) {
            $table->string('kategori', 30)->default('penunjang_klinis')->after('kode');
            $table->string('pic')->nullable()->after('nama');
            $table->string('jabatan_pic')->nullable()->after('pic');
            $table->string('nip', 30)->nullable()->after('jabatan_pic');
            $table->string('kontak_wa', 25)->nullable()->after('nip');
            $table->string('ekstensi', 15)->nullable()->after('kontak_wa');
            $table->string('jam_layanan', 30)->default('24 Jam')->after('ekstensi');
            $table->string('akun_simrs')->nullable()->after('jam_layanan');
            $table->string('peran_akses', 30)->default('kepala_unit')->after('akun_simrs');
            $table->string('status_akses', 20)->default('belum_diundang')->after('peran_akses');
            $table->boolean('aktif')->default(true)->after('status_akses');

            $table->index(['kategori', 'aktif']);
        });
    }

    public function down(): void
    {
        Schema::table('master_units', function (Blueprint $table) {
            $table->dropIndex(['kategori', 'aktif']);

            $table->dropColumn([
                'kategori',
                'pic',
                'jabatan_pic',
                'nip',
                'kontak_wa',
                'ekstensi',
                'jam_layanan',
                'akun_simrs',
                'peran_akses',
                'status_akses',
                'aktif',
            ]);
        });
    }
};
