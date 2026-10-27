<?php

use App\Enums\KategoriPengaduan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaduan', function (Blueprint $table) {
            // Pelapor tidak lagi memilih kategori; humas yang menentukannya
            // saat telaah. Tiket yang baru masuk harus boleh kosong dulu,
            // sedangkan tiket lama tetap menyimpan kategori yang sudah ada.
            $table->string('kategori')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Kolom non-null tidak bisa dipulihkan selama masih ada tiket yang
        // belum dikategorikan. Rollback sengaja berhenti dengan pesan yang
        // jelas daripada gagal di tengah jalan dengan galat SQL mentah.
        if (DB::table('pengaduan')->whereNull('kategori')->exists()) {
            throw new RuntimeException(
                'Tiket tanpa kategori masih ada. Isi kolom kategori dulu sebelum rollback: '
                .'UPDATE pengaduan SET kategori = \''.KategoriPengaduan::Fasilitas->value.'\' WHERE kategori IS NULL;'
            );
        }

        Schema::table('pengaduan', function (Blueprint $table) {
            $table->string('kategori')->nullable(false)->change();
        });
    }
};
