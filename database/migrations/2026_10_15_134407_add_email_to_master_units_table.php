<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_units', function (Blueprint $table) {
            $table->string('email')->nullable()->after('kontak_wa')
                ->comment('Email PIC unit untuk notifikasi email');
        });
    }

    public function down(): void
    {
        Schema::table('master_units', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
