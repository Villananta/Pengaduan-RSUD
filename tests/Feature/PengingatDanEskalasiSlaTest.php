<?php

namespace Tests\Feature;

use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\User;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pengingat dan eskalasi SLA yang berjalan lewat jadwal harian.
 *
 * Tiga hal yang dijaga di sini: pengingat "SLA lewat" tidak boleh membanjiri
 * lonceng PIC setiap pagi, eskalasi harus sampai ke admin humas karena tidak
 * ada akun atasan unit, dan opsi --eskalasi harus benar-benar terjadwal.
 */
class PengingatDanEskalasiSlaTest extends TestCase
{
    use RefreshDatabase;

    /** Senin, 26 Oktober 2026, pukul 09:00 waktu aplikasi. */
    private const SEKARANG = '2026-10-26 09:00:00';

    /** Tiket masuk lima hari kerja sebelumnya, sudah lewat batas investigasi. */
    private const MASUK_LEWAT = '2026-10-19 09:00:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterUnitSeeder::class);
        Carbon::setTestNow(Carbon::parse(self::SEKARANG));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_pengingat_sla_lewat_hanya_dikirim_sekali_tiap_tiga_hari(): void
    {
        $unit = $this->unit();
        $pic = $this->picUntuk($unit);
        $pengaduan = $this->tiketLewat($unit);

        $this->artisan('pengaduan:cek-sla --kirim-notifikasi --force')->assertSuccessful();
        $this->assertSame(1, $this->jumlahNotifikasi($pic, 'pengaduan_sla_lewat'));

        // Menjalankan ulang di hari yang sama tidak boleh menambah baris baru.
        $this->artisan('pengaduan:cek-sla --kirim-notifikasi --force')->assertSuccessful();
        $this->assertSame(1, $this->jumlahNotifikasi($pic, 'pengaduan_sla_lewat'));

        // Tiga hari kemudian jeda sudah habis, jadi pengingat boleh dikirim lagi.
        Carbon::setTestNow(now()->addDays(3));
        $this->artisan('pengaduan:cek-sla --kirim-notifikasi --force')->assertSuccessful();

        $this->assertSame(2, $this->jumlahNotifikasi($pic, 'pengaduan_sla_lewat'));
        $this->assertSame(0, $this->jumlahNotifikasi($pic, 'pengaduan_eskalasi_sla'));
    }

    public function test_eskalasi_nudge_dikirim_ke_admin_humas_bukan_pic(): void
    {
        $unit = $this->unit();
        $pic = $this->picUntuk($unit);
        $admin = User::factory()->admin()->create();
        $pengaduan = $this->tiketLewat($unit);

        $nudge = $pengaduan->riwayatStatus()->create([
            'dari' => $pengaduan->status,
            'ke' => $pengaduan->status,
            'catatan' => 'Nudge manual ke unit: lengkapi berkas investigasi',
        ]);
        $nudge->forceFill(['created_at' => now()->subHours(30)])->save();

        $this->artisan('pengaduan:cek-sla --kirim-notifikasi --eskalasi --force')->assertSuccessful();

        $this->assertSame(1, $this->jumlahNotifikasi($admin, 'pengaduan_eskalasi_nudge'));
        $this->assertSame(0, $this->jumlahNotifikasi($pic, 'pengaduan_eskalasi_nudge'));

        $notifikasi = $admin->notifications()
            ->get()
            ->first(fn ($baris): bool => ($baris->data['type'] ?? null) === 'pengaduan_eskalasi_nudge');

        $this->assertSame(
            route('admin.pengaduan.show', $pengaduan->kode_tiket),
            $notifikasi->data['url'],
        );

        $catatan = $pengaduan->riwayatStatus()
            ->where('catatan', 'like', 'Eskalasi nudge%')
            ->value('catatan');

        $this->assertStringContainsString('Diteruskan ke admin humas.', $catatan);
    }

    public function test_eskalasi_sla_dikirim_ke_admin_humas_bukan_pic(): void
    {
        $unit = $this->unit();
        $pic = $this->picUntuk($unit);
        $admin = User::factory()->admin()->create();

        // Lima hari kerja lewat adalah kelipatan lima pertama yang memicu eskalasi.
        $pengaduan = $this->tiketLewat($unit);

        $this->artisan('pengaduan:cek-sla --kirim-notifikasi --eskalasi --force')->assertSuccessful();

        $this->assertSame(1, $this->jumlahNotifikasi($admin, 'pengaduan_eskalasi_sla'));
        $this->assertSame(0, $this->jumlahNotifikasi($pic, 'pengaduan_eskalasi_sla'));

        // Pengingat biasa tetap dikirim ke PIC; yang berpindah penerima hanya eskalasinya.
        $this->assertSame(1, $this->jumlahNotifikasi($pic, 'pengaduan_sla_lewat'));

        $notifikasi = $admin->notifications()
            ->get()
            ->first(fn ($baris): bool => ($baris->data['type'] ?? null) === 'pengaduan_eskalasi_sla');

        $this->assertSame(
            route('admin.pengaduan.show', $pengaduan->kode_tiket),
            $notifikasi->data['url'],
        );
    }

    public function test_eskalasi_dilewati_bila_tidak_ada_admin_humas(): void
    {
        $unit = $this->unit();
        $pic = $this->picUntuk($unit);
        $pengaduan = $this->tiketLewat($unit);

        $nudge = $pengaduan->riwayatStatus()->create([
            'dari' => $pengaduan->status,
            'ke' => $pengaduan->status,
            'catatan' => 'Nudge manual ke unit: cek tindak lanjut',
        ]);
        $nudge->forceFill(['created_at' => now()->subHours(30)])->save();

        $this->artisan('pengaduan:cek-sla --kirim-notifikasi --eskalasi --force')
            ->expectsOutputToContain('Tidak ada akun admin humas')
            ->assertSuccessful();

        $this->assertSame(0, DB::table('notifications')
            ->where('data->type', 'like', 'pengaduan_eskalasi%')
            ->count());

        $this->assertSame(0, $pengaduan->riwayatStatus()
            ->where('catatan', 'like', 'Eskalasi%')
            ->count());

        $this->assertSame(0, $this->jumlahNotifikasi($pic, 'pengaduan_eskalasi_nudge'));
    }

    public function test_jadwal_cek_sla_menyertakan_opsi_eskalasi(): void
    {
        $jadwal = collect($this->app->make(Schedule::class)->events())
            ->map(fn ($event): string => $event->command ?? '')
            ->first(fn (string $perintah): bool => str_contains($perintah, 'pengaduan:cek-sla'));

        $this->assertNotNull($jadwal, 'Pemeriksaan SLA tidak ada di jadwal aplikasi.');
        $this->assertStringContainsString('--eskalasi', $jadwal);
    }

    /** Unit dengan PIC yang menempel, supaya tiket tidak dilewati command. */
    private function unit(): MasterUnit
    {
        return MasterUnit::where('kode', 'IFP-01')->firstOrFail();
    }

    private function tiketLewat(MasterUnit $unit): Pengaduan
    {
        return Pengaduan::factory()->create([
            'master_unit_id' => $unit->id,
            'created_at' => Carbon::parse(self::MASUK_LEWAT),
        ]);
    }

    /** Notifikasi database dengan jenis tertentu milik satu penerima. */
    private function jumlahNotifikasi(User $penerima, string $jenis): int
    {
        return $penerima->notifications()
            ->get()
            ->filter(fn ($baris): bool => ($baris->data['type'] ?? null) === $jenis)
            ->count();
    }
}
