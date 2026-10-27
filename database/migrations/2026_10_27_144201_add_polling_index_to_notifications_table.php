<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lonceng notifikasi menanyakan "belum dibaca milik user ini, urut waktu"
     * pada setiap polling. Index morph yang lama hanya menutup dua kolom
     * pertama, sehingga read_at dan created_at masih dibaca dari tabel.
     *
     * Index baru memuat keempat kolom sekaligus. Index morph lama dilepas
     * karena kolomnya sudah menjadi awalan index baru; menyimpan keduanya
     * hanya menambah biaya tulis tanpa mempercepat query apa pun.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_type_notifiable_id_index');
            $table->index(
                ['notifiable_type', 'notifiable_id', 'read_at', 'created_at'],
                'notifications_polling_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_polling_index');
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }
};
