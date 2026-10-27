<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaduan', function (Blueprint $table) {
            // Null berarti admin belum menetapkan tingkat keparahan. Tiket
            // lama sengaja dibiarkan kosong supaya tidak otomatis dicap
            // ringan tanpa ada yang memutuskan.
            $table->string('tingkat_keparahan', 20)->nullable()->after('kasus_berat_at');
            $table->index('tingkat_keparahan');
        });
    }

    public function down(): void
    {
        Schema::table('pengaduan', function (Blueprint $table) {
            $table->dropIndex(['tingkat_keparahan']);
            $table->dropColumn('tingkat_keparahan');
        });
    }
};
