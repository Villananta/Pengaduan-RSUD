<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mempercepat penyaringan status yang dipakai hampir di seluruh halaman.
 *
 * Kolom status selalu jadi saringan atau pengelompokan, tapi sebelumnya tidak
 * punya index sama sekali. Index tunggal melayani agregat dashboard, sedangkan
 * index gabungan melayani daftar per unit yang menyaring unit lalu status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaduan', function (Blueprint $table) {
            $table->index('status');
            $table->index(['master_unit_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('pengaduan', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['master_unit_id', 'status']);
        });
    }
};
