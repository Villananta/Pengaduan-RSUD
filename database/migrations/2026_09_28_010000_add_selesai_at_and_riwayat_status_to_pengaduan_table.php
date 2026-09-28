<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaduan', function (Blueprint $table) {
            $table->timestamp('selesai_at')->nullable()->after('status');
        });

        Schema::create('riwayat_status_pengaduan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengaduan_id')->constrained('pengaduan')->cascadeOnDelete();
            $table->string('dari', 20)->nullable();
            $table->string('ke', 20);
            $table->string('catatan', 500)->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['pengaduan_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_status_pengaduan');

        Schema::table('pengaduan', function (Blueprint $table) {
            $table->dropColumn('selesai_at');
        });
    }
};
