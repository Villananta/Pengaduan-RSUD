<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesan_pengaduan', function (Blueprint $table) {
            $table->string('lampiran')->nullable()->after('isi');
        });
    }

    public function down(): void
    {
        Schema::table('pesan_pengaduan', function (Blueprint $table) {
            $table->dropColumn('lampiran');
        });
    }
};
