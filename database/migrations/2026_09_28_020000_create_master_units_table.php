<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_units', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama');
            $table->boolean('koneksi_simrs')->default(true);
            $table->timestamp('koneksi_simrs_terakhir')->nullable();
            $table->string('disposisi', 30)->default('terhubung');
            $table->timestamps();

            $table->index(['kode', 'nama']);
        });

        Schema::table('pengaduan', function (Blueprint $table) {
            $table->foreignId('master_unit_id')
                ->nullable()
                ->after('unit')
                ->constrained('master_units')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pengaduan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('master_unit_id');
        });

        Schema::dropIfExists('master_units');
    }
};
