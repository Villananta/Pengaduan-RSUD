<?php

namespace Tests\Feature;

use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\User;
use App\Notifications\PengaduanNudgeUnit;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NotifikasiKonsolUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterUnitSeeder::class);
    }

    public function test_pic_hanya_melihat_notifikasi_milik_akun_sendiri(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $picA = $this->masukSebagaiPicUntuk($farmasi);
        $picB = User::factory()->picUnit($farmasi)->create();

        $pengaduan = Pengaduan::factory()->create(['master_unit_id' => $farmasi->id]);
        $picB->notify(new PengaduanNudgeUnit($pengaduan, $farmasi, 'Cek segera'));

        $response = $this->get(route('unit.notifikasi.daftar', $farmasi));
        $response->assertOk()
            ->assertJson(['sukses' => true])
            ->assertJsonCount(0, 'notifikasi');
    }

    public function test_realtime_hanya_mengembalikan_notifikasi_akun_yang_masuk(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $picA = $this->masukSebagaiPicUntuk($farmasi);
        $picB = User::factory()->picUnit($farmasi)->create();

        $pengaduan = Pengaduan::factory()->create(['master_unit_id' => $farmasi->id]);
        $picB->notify(new PengaduanNudgeUnit($pengaduan, $farmasi, 'Cek segera'));

        $response = $this->get(route('unit.notifikasi.realtime', $farmasi));
        $response->assertOk()
            ->assertJson(['sukses' => true, 'total_belum_dibaca' => 0]);
    }

    public function test_tandai_semua_dibaca_bukan_ajax_mengembalikan_redirect(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $pic = $this->masukSebagaiPicUntuk($farmasi);
        $pengaduan = Pengaduan::factory()->create(['master_unit_id' => $farmasi->id]);
        $pic->notify(new PengaduanNudgeUnit($pengaduan, $farmasi, 'Cek segera'));

        $response = $this->post(route('unit.notifikasi.tandai-semua-dibaca', $farmasi));
        $response->assertRedirect();
    }

    public function test_lonceng_muncul_di_halaman_disposisi(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $this->masukSebagaiPicUntuk($farmasi);

        $response = $this->get(route('unit.disposisi.index', $farmasi));
        $response->assertOk()->assertSee('id="notifikasi-wrapper"', false);
    }

    public function test_polling_membaca_patokan_utc_dalam_zona_aplikasi(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $pic = $this->masukSebagaiPicUntuk($farmasi);
        $pengaduan = Pengaduan::factory()->create(['master_unit_id' => $farmasi->id]);

        $pic->notify(new PengaduanNudgeUnit($pengaduan, $farmasi, 'Cek segera'));
        $notifikasi = $pic->notifications()->firstOrFail();
        $notifikasi->forceFill(['created_at' => now()->subMinute()->startOfSecond()])->save();

        // Klien mengirim ISO8601 UTC, sedangkan tabel notifications menyimpan
        // waktu lokal. Tanpa penerjemahan, batas ini bergeser beberapa jam.
        $response = $this->get(route('unit.notifikasi.realtime', [
            'unit' => $farmasi,
            'since' => now()->subMinutes(5)->toISOString(),
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.id', $notifikasi->id);
    }

    public function test_polling_mengirim_sisa_notifikasi_di_poll_berikutnya(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $pic = $this->masukSebagaiPicUntuk($farmasi);
        $pengaduan = Pengaduan::factory()->create(['master_unit_id' => $farmasi->id]);

        foreach ([1, 2, 3] as $urutan) {
            $pic->notify(new PengaduanNudgeUnit($pengaduan, $farmasi, 'Nudge '.$urutan));
        }

        $semua = $pic->notifications()->orderBy('created_at')->get();
        foreach ($semua as $index => $notifikasi) {
            $notifikasi->forceFill([
                'created_at' => now()->subMinutes(10 - $index)->startOfSecond(),
            ])->save();
        }

        $pertama = $this->get(route('unit.notifikasi.realtime', [
            'unit' => $farmasi,
            'since' => now()->subHour()->toISOString(),
            'limit' => 2,
        ]));

        $pertama->assertOk()
            ->assertJsonCount(2, 'notifications')
            ->assertJsonPath('has_more', true)
            ->assertJsonPath('next_since', $semua[1]->fresh()->created_at->toISOString());

        // Poll lanjutan memakai next_since; sisa yang tadi terpotong batas
        // harus ikut terkirim, bukan dilewati.
        $kedua = $this->get(route('unit.notifikasi.realtime', [
            'unit' => $farmasi,
            'since' => $pertama->json('next_since'),
            'limit' => 2,
        ]));

        $kedua->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('has_more', false)
            ->assertJsonPath('notifications.0.id', $semua[2]->id);
    }

    public function test_patokan_poll_dimundurkan_agar_detik_yang_sama_tidak_terlewat(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $this->masukSebagaiPicUntuk($farmasi);

        $response = $this->get(route('unit.notifikasi.realtime', $farmasi));

        $patokan = Carbon::parse($response->json('next_since'));
        $waktuServer = Carbon::parse($response->json('server_time'));

        // created_at hanya menyimpan detik, jadi patokan harus berada sebelum
        // waktu server. Kalau sama, notifikasi yang lahir di detik itu lewat.
        $this->assertTrue(
            $patokan->lessThan($waktuServer),
            'Patokan poll harus mundur dari waktu server agar detik yang sama ikut terbaca.'
        );
    }

    public function test_tandai_dibaca_hanya_menyentuh_milik_akun_dan_mengirim_sisa_lencana(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $picA = $this->masukSebagaiPicUntuk($farmasi);
        $picB = User::factory()->picUnit($farmasi)->create();
        $pengaduan = Pengaduan::factory()->create(['master_unit_id' => $farmasi->id]);

        $picB->notify(new PengaduanNudgeUnit($pengaduan, $farmasi, 'Milik PIC lain'));
        $milikB = $picB->notifications()->firstOrFail();

        $picA->notify(new PengaduanNudgeUnit($pengaduan, $farmasi, 'Pertama'));
        $picA->notify(new PengaduanNudgeUnit($pengaduan, $farmasi, 'Kedua'));
        $milikA = $picA->notifications()->latest()->get();

        $response = $this->postJson(route('unit.notifikasi.tandai-dibaca', $farmasi), [
            'notification_ids' => [$milikA->first()->id, $milikB->id],
        ]);

        // ID milik petugas lain tidak boleh ikut tertandai hanya karena
        // ditebak, dan sisa belum dibaca harus akurat untuk lencana.
        $response->assertOk()
            ->assertJsonPath('dibaca', 1)
            ->assertJsonPath('total_belum_dibaca', 1);

        $this->assertNull($milikB->fresh()->read_at);
    }
}
