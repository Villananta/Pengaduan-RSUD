<?php

namespace Tests\Feature;

use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Penyajian lampiran lewat rute terlindung.
 *
 * Yang dijaga di sini bukan isi berkasnya, melainkan cara berkas dikirim:
 * header yang mencegah browser menebak tipe, dan hak akses yang tetap utuh
 * setelah pemeriksaan unit yang berulang dihapus.
 */
class LampiranTest extends TestCase
{
    use RefreshDatabase;

    public function test_lampiran_menyertakan_header_nosniff(): void
    {
        Storage::fake('public');
        $this->masukSebagaiAdmin();

        $path = UploadedFile::fake()->create('bukti.jpg', 20, 'image/jpeg')->store('lampiran', 'public');

        $pengaduan = Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-20261028-HEADER',
            'lampiran' => [$path],
        ]);

        $pesan = $pengaduan->pesan()->create([
            'peran' => 'admin',
            'isi' => 'Bukti pendukung kami lampirkan bersama balasan ini.',
            'lampiran' => [$path],
        ]);

        $this->get(route('lampiran.pengaduan', [
            'kode' => $pengaduan->kode_tiket,
            'file' => basename($path),
        ]))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->get(route('lampiran.pesan', [
            'pesan' => $pesan->id,
            'file' => basename($path),
        ]))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_berkas_di_luar_daftar_jpg_png_pdf_dipaksa_unduh(): void
    {
        Storage::fake('public');
        $this->masukSebagaiAdmin();

        // Berkas seperti ini tidak mungkin lolos validasi unggahan, tapi
        // bisa masuk lewat seeder atau salinan manual ke disk lampiran.
        Storage::disk('public')->put('lampiran/catatan.html', '<script>alert("x")</script>');

        $pengaduan = Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-20261028-HTML',
            'lampiran' => ['lampiran/catatan.html'],
        ]);

        $response = $this->get(route('lampiran.pengaduan', [
            'kode' => $pengaduan->kode_tiket,
            'file' => 'catatan.html',
        ]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/octet-stream')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; sandbox");

        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_hak_akses_unit_tetap_berlaku_setelah_duplikat_dihapus(): void
    {
        Storage::fake('public');
        $this->seed(MasterUnitSeeder::class);

        $unitAsal = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $unitLain = MasterUnit::where('kode', 'DFR-02')->firstOrFail();

        $path = UploadedFile::fake()->create('bukti.pdf', 30, 'application/pdf')->store('lampiran', 'public');

        $pengaduan = Pengaduan::factory()->create([
            'kode_tiket' => 'ADUAN-20261028-UNIT',
            'lampiran' => [$path],
            'master_unit_id' => $unitAsal->id,
            'status' => StatusPengaduan::Diproses,
        ]);

        $pesan = $pengaduan->pesan()->create([
            'peran' => 'admin',
            'isi' => 'Kami teruskan ke unit terkait untuk ditelaah.',
            'lampiran' => [$path],
        ]);

        $urlPengaduan = route('lampiran.pengaduan', [
            'kode' => $pengaduan->kode_tiket,
            'file' => basename($path),
        ]);
        $urlPesan = route('lampiran.pesan', [
            'pesan' => $pesan->id,
            'file' => basename($path),
        ]);

        $this->masukSebagaiPicUntuk($unitLain);
        $this->get($urlPengaduan)->assertForbidden();
        $this->get($urlPesan)->assertForbidden();

        // Selama tiket belum diteruskan humas, unit pemilik pun tidak boleh
        // membukanya lewat rute lampiran.
        $this->masukSebagaiPicUntuk($unitAsal);
        $pengaduan->forceFill(['status' => StatusPengaduan::Diterima])->save();
        $this->get($urlPengaduan)->assertNotFound();
        $this->get($urlPesan)->assertNotFound();

        $pengaduan->forceFill(['status' => StatusPengaduan::Diproses])->save();
        $this->get($urlPengaduan)->assertOk();
        $this->get($urlPesan)->assertOk();
    }
}
