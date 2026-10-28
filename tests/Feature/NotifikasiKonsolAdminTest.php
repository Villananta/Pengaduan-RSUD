<?php

namespace Tests\Feature;

use App\Models\MasterUnit;
use App\Models\Pengaduan;
use App\Models\User;
use App\Notifications\PengaduanBalasanAdmin;
use App\Notifications\PengaduanEscalasiNudge;
use App\Notifications\PengaduanEscalasiSla;
use App\Notifications\PengaduanNudgeUnit;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotifikasiKonsolAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterUnitSeeder::class);
    }

    public function test_daftar_hanya_memuat_notifikasi_milik_admin_yang_masuk(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $admin = $this->masukSebagaiAdmin();
        $adminLain = User::factory()->admin()->create();
        $pengaduan = Pengaduan::factory()->create(['master_unit_id' => $farmasi->id]);

        $adminLain->notify(new PengaduanNudgeUnit($pengaduan, $farmasi, 'Milik admin lain'));
        $admin->notify(new PengaduanNudgeUnit($pengaduan, $farmasi, 'Milik sendiri'));

        $this->getJson(route('admin.notifikasi.daftar'))
            ->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.type', 'pengaduan_nudge_unit');
    }

    public function test_dropdown_admin_memakai_ikon_khusus_untuk_eskalasi(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $admin = $this->masukSebagaiAdmin();
        $pengaduan = Pengaduan::factory()->create(['master_unit_id' => $farmasi->id]);

        $admin->notify(new PengaduanEscalasiSla($pengaduan, $farmasi, 2));
        $admin->notify(new PengaduanEscalasiNudge($pengaduan, $farmasi, 30, 'Belum direspons'));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('notification_important', false)
            ->assertSee('campaign', false);
    }

    public function test_lencana_lonceng_menampilkan_jumlah_notifikasi_belum_dibaca(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $admin = $this->masukSebagaiAdmin();
        $pengaduan = Pengaduan::factory()->create(['master_unit_id' => $farmasi->id]);

        $admin->notify(new PengaduanNudgeUnit($pengaduan, $farmasi, 'Pesan pertama'));
        $admin->notify(new PengaduanNudgeUnit($pengaduan, $farmasi, 'Pesan kedua'));

        // Angka lencana disiapkan App\Support\NotifikasiAdmin lewat view
        // composer pada layout; tanpa composer, layout tidak punya angka
        // untuk ditampilkan.
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="admin-badge-notifikasi"', false)
            ->assertSee('2 notifikasi belum dibaca');
    }

    public function test_tandai_dibaca_menolak_daftar_kosong(): void
    {
        $this->masukSebagaiAdmin();

        $this->postJson(route('admin.notifikasi.baca'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('notification_ids');
    }

    public function test_balasan_admin_dikirim_ke_email_pelapor(): void
    {
        Notification::fake();

        $this->masukSebagaiAdmin();
        $pengaduan = Pengaduan::factory()->create(['email' => 'pelapor@contoh.test']);

        $this->post(route('admin.pengaduan.balas', $pengaduan->kode_tiket), [
            'isi' => 'Terima kasih, laporan Anda sudah kami tindak lanjuti.',
        ])->assertRedirect();

        Notification::assertSentOnDemand(
            PengaduanBalasanAdmin::class,
            function (PengaduanBalasanAdmin $notification, array $channels, $notifiable): bool {
                return $channels === ['mail']
                    && $notifiable->routes['mail'] === 'pelapor@contoh.test';
            }
        );
    }
}
