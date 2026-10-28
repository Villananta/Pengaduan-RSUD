<?php

namespace Tests\Feature;

use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\User;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aksi massal pada daftar pengaduan.
 *
 * Yang dijaga di sini: jumlah tiket sekali proses dibatasi, hasilnya
 * benar-benar terbaca admin, dan pelakunya tercatat di riwayat.
 */
class AksiMassalPengaduanTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);

        $this->admin = $this->masukSebagaiAdmin();
    }

    public function test_pilihan_lebih_dari_seratus_tiket_ditolak(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $kodeTiket = Pengaduan::factory()
            ->count(101)
            ->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => null])
            ->pluck('kode_tiket')
            ->all();

        $this->from(route('admin.pengaduan.index'))
            ->post(route('admin.pengaduan.bulk-action'), [
                'aksi' => 'disposisi',
                'kode_tiket' => $kodeTiket,
                'unit' => $unit->kode,
            ])
            ->assertSessionHasErrors('kode_tiket');

        // Kiriman yang ditolak tidak boleh menyentuh satu tiket pun.
        $this->assertSame(0, Pengaduan::query()->whereNotNull('master_unit_id')->count());
    }

    public function test_aksi_massal_yang_gagal_semua_mengirim_pesan_gagal_bukan_sukses(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $kodeTiket = Pengaduan::factory()
            ->count(2)
            ->selesai()
            ->create()
            ->pluck('kode_tiket')
            ->all();

        $this->from(route('admin.pengaduan.index'))
            ->post(route('admin.pengaduan.bulk-action'), [
                'aksi' => 'disposisi',
                'kode_tiket' => $kodeTiket,
                'unit' => $unit->kode,
            ])
            ->assertRedirect(route('admin.pengaduan.index'))
            ->assertSessionHas('gagal')
            ->assertSessionMissing('sukses');

        // Pesannya harus benar-benar tampil: sebelumnya kunci "sukses"
        // dipakai walau tidak ada tiket yang berhasil, dan halaman daftar
        // tidak merender pesan apa pun.
        $this->get(route('admin.pengaduan.index'))
            ->assertOk()
            ->assertSee('0 tiket berhasil diproses.');
    }

    public function test_aksi_massal_yang_sebagian_berhasil_tetap_memakai_pesan_sukses(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        Pengaduan::factory()->status(StatusPengaduan::Diterima)->create([
            'kode_tiket' => 'ADUAN-MASSAL-01',
        ]);
        Pengaduan::factory()->selesai()->create([
            'kode_tiket' => 'ADUAN-MASSAL-02',
        ]);

        $this->from(route('admin.pengaduan.index'))
            ->post(route('admin.pengaduan.bulk-action'), [
                'aksi' => 'disposisi',
                'kode_tiket' => ['ADUAN-MASSAL-01', 'ADUAN-MASSAL-02'],
                'unit' => $unit->kode,
            ])
            ->assertRedirect(route('admin.pengaduan.index'))
            ->assertSessionHas('sukses')
            ->assertSessionMissing('gagal');

        $this->get(route('admin.pengaduan.index'))
            ->assertOk()
            ->assertSee('1 tiket berhasil diproses.')
            ->assertSee('ADUAN-MASSAL-02: Tiket sudah selesai.');
    }

    public function test_aksi_massal_disposisi_mencatat_admin_pelaku(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $pengaduan = Pengaduan::factory()->status(StatusPengaduan::Diterima)->create([
            'kode_tiket' => 'ADUAN-MASSAL-03',
        ]);

        $this->from(route('admin.pengaduan.index'))
            ->post(route('admin.pengaduan.bulk-action'), [
                'aksi' => 'disposisi',
                'kode_tiket' => [$pengaduan->kode_tiket],
                'unit' => $unit->kode,
            ])
            ->assertRedirect(route('admin.pengaduan.index'));

        $riwayat = $pengaduan->riwayatStatus()->reorder('id', 'desc')->first();

        $this->assertSame($this->admin->id, $riwayat->admin_id);
        $this->assertSame($unit->id, $pengaduan->fresh()->master_unit_id);
    }
}
