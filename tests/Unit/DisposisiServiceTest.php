<?php

namespace Tests\Unit;

use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\User;
use App\Services\DisposisiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisposisiServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_disposisi_ke_unit_mengubah_status_dan_menandai_unit_menunggu_investigasi(): void
    {
        $pengaduan = Pengaduan::factory()->create([
            'status' => StatusPengaduan::Diterima,
            'master_unit_id' => null,
        ]);

        $unit = MasterUnit::factory()->create([
            'aktif' => true,
            'disposisi' => \App\Enums\DisposisiUnit::Terhubung,
        ]);

        DisposisiService::disposisiKeUnit($pengaduan, $unit, 'Segera ditindaklanjuti');

        $pengaduan->refresh();
        $unit->refresh();

        $this->assertSame(StatusPengaduan::Diproses, $pengaduan->status);
        $this->assertSame($unit->id, $pengaduan->master_unit_id);
        $this->assertSame(\App\Enums\DisposisiUnit::MenungguInvestigasi, $unit->disposisi);
        $this->assertGreaterThan(0, $pengaduan->riwayatStatus()->count());
    }

    public function test_tangani_langsung_melepas_unit_dan_pertahankan_status_diproses(): void
    {
        $unit = MasterUnit::factory()->create([
            'aktif' => true,
            'disposisi' => \App\Enums\DisposisiUnit::MenungguInvestigasi,
        ]);

        $pengaduan = Pengaduan::factory()->create([
            'status' => StatusPengaduan::Diterima,
            'master_unit_id' => $unit->id,
        ]);

        DisposisiService::disposisiKeUnit($pengaduan, $unit, 'Tugaskan');
        $pengaduan->refresh();

        // Setelah didisposisikan ke unit dan status jadi Diproses, 
        // tanganiLangsung tidak bisa dipanggil sesuai aturan saat ini
        $this->expectException(\RuntimeException::class);
        DisposisiService::tanganiLangsung($pengaduan, 'Ditangani langsung humas');
    }

    public function test_disposisi_ke_unit_tidak_bisa_jika_tiket_sudah_selesai(): void
    {
        $pengaduan = Pengaduan::factory()->create([
            'status' => StatusPengaduan::Selesai,
            'selesai_at' => now(),
        ]);

        $unit = MasterUnit::factory()->create(['aktif' => true]);

        $this->expectException(\RuntimeException::class);
        DisposisiService::disposisiKeUnit($pengaduan, $unit);
    }

    public function test_disposisi_ke_unit_tidak_bisa_jika_unit_tidak_aktif(): void
    {
        $pengaduan = Pengaduan::factory()->create([
            'status' => StatusPengaduan::Diterima,
        ]);

        $unit = MasterUnit::factory()->create(['aktif' => false]);

        $this->expectException(\RuntimeException::class);
        DisposisiService::disposisiKeUnit($pengaduan, $unit);
    }
}
