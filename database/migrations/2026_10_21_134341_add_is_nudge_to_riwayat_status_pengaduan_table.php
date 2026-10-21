<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('riwayat_status_pengaduan', function (Blueprint $table) {
            $table->boolean('is_nudge')->default(false)->after('catatan');
            $table->index(['pengaduan_id', 'is_nudge', 'created_at'], 'riwayat_nudge_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riwayat_status_pengaduan', function (Blueprint $table) {
            $table->dropIndex('riwayat_nudge_lookup');
            $table->dropColumn('is_nudge');
        });
    }
};
