<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaduan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_tiket')->unique();
            $table->string('kategori');
            $table->string('nama_lengkap');
            $table->string('nrm');
            $table->string('no_wa');
            $table->string('email');
            $table->text('alamat');
            $table->dateTime('waktu_kejadian');
            $table->string('unit');
            $table->string('subjek');
            $table->text('deskripsi');
            $table->json('lampiran')->nullable();
            $table->string('status')->default('diterima');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaduan');
    }
};
