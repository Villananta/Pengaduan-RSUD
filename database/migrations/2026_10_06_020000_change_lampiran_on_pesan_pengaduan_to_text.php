<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Longgarkan kolom lampiran pesan dari satu berkas menjadi banyak.
     *
     * Kolomnya dibuat string karena waktu itu satu pesan hanya boleh membawa
     * satu berkas. Sekarang pelapor dan admin humas sama-sama boleh melampirkan
     * beberapa berkas sekaligus, sehingga isinya harus berupa daftar path.
     * String tidak bisa menyimpan daftar, jadi tipenya diganti text dan nilainya
     * ditulis sebagai JSON lewat cast array di model.
     *
     * Baris lama berisi satu path polos, bukan JSON. Kalau tidak dikonversi,
     * cast array membaca nilai itu sebagai null dan lampiran pada pesan lama
     * hilang dari layar walaupun berkasnya masih ada di disk.
     */
    public function up(): void
    {
        Schema::table('pesan_pengaduan', function (Blueprint $table) {
            $table->text('lampiran')->nullable()->change();
        });

        DB::table('pesan_pengaduan')
            ->whereNotNull('lampiran')
            ->orderBy('id')
            ->chunkById(100, function ($baris): void {
                foreach ($baris as $pesan) {
                    // Path lama tidak pernah terbaca sebagai JSON array, jadi
                    // cukup dibungkus satu elemen tanpa memeriksa isinya lagi.
                    if (is_array(json_decode($pesan->lampiran, true))) {
                        continue;
                    }

                    DB::table('pesan_pengaduan')
                        ->where('id', $pesan->id)
                        ->update(['lampiran' => json_encode([$pesan->lampiran])]);
                }
            });
    }

    public function down(): void
    {
        // Pesan yang berisi lebih dari satu berkas harus dipangkas ke berkas
        // pertamanya, sebab string tidak punya tempat menyimpan sisanya.
        DB::table('pesan_pengaduan')
            ->whereNotNull('lampiran')
            ->orderBy('id')
            ->chunkById(100, function ($baris): void {
                foreach ($baris as $pesan) {
                    $nilai = json_decode($pesan->lampiran, true);

                    DB::table('pesan_pengaduan')
                        ->where('id', $pesan->id)
                        ->update([
                            'lampiran' => is_array($nilai) ? ($nilai[0] ?? null) : $pesan->lampiran,
                        ]);
                }
            });

        Schema::table('pesan_pengaduan', function (Blueprint $table) {
            $table->string('lampiran')->nullable()->change();
        });
    }
};
