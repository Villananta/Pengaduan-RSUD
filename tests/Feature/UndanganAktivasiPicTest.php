<?php

namespace Tests\Feature;

use App\Enums\PeranAksesUnit;
use App\Enums\PeranPengguna;
use App\Enums\StatusAksesUnit;
use App\Models\MasterUnit;
use App\Models\User;
use App\Notifications\UndanganAktivasiPic;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Alur undangan aktivasi PIC: tombol massal di master unit, tautan
 * berumur pendek di email, lalu halaman publik yang membuat akun PIC.
 *
 * Sebaran data dipakai sebagai acuan perhitungan di bawah:
 * IRS-03, IRJ-05, dan ADM-08 punya alamat email serta belum berakun,
 * KSA-07 belum diundang tapi alamat emailnya kosong, dan lima unit
 * lainnya sudah berstatus Akun Aktif.
 */
class UndanganAktivasiPicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);

        $this->masukSebagaiAdmin();
    }

    public function test_kirim_undangan_massal_mengirim_email_dan_mengubah_status(): void
    {
        Notification::fake();

        $this->post(route('admin.unit.undangan'))
            ->assertRedirect()
            ->assertSessionHas('sukses');

        Notification::assertSentOnDemand(UndanganAktivasiPic::class, 3);

        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();

        $this->assertSame(StatusAksesUnit::UndanganTerkirim, $unit->status_akses);
        $this->assertNotNull($unit->undangan_token);
        $this->assertTrue($unit->undangan_berlaku->isFuture());
    }

    public function test_unit_tanpa_alamat_email_dilewati_dan_statusnya_tidak_berubah(): void
    {
        Notification::fake();

        $this->post(route('admin.unit.undangan'));

        $unit = MasterUnit::where('kode', 'KSA-07')->firstOrFail();

        $this->assertSame(StatusAksesUnit::BelumDiundang, $unit->status_akses);
        $this->assertNull($unit->undangan_token);
    }

    public function test_unit_yang_akunnya_sudah_aktif_tidak_menerima_undangan_ulang(): void
    {
        Notification::fake();

        $this->post(route('admin.unit.undangan'));

        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $this->assertSame(StatusAksesUnit::AkunAktif, $unit->status_akses);
        $this->assertNull($unit->undangan_token);
    }

    public function test_unit_nonaktif_tidak_diundang_dan_statusnya_tetap_dinonaktifkan(): void
    {
        // Undangan ke unit nonaktif hanya akan membuat akun PIC yang tidak
        // bisa membuka konsolnya, sekaligus menimpa status Dinonaktifkan
        // menjadi UndanganTerkirim.
        Notification::fake();

        MasterUnit::where('kode', 'IRJ-05')->firstOrFail()->update([
            'aktif' => false,
            'status_akses' => StatusAksesUnit::Dinonaktifkan,
        ]);

        $this->post(route('admin.unit.undangan'));

        // Dua unit ber-email lainnya tetap diundang; yang nonaktif dilewati.
        Notification::assertSentOnDemand(UndanganAktivasiPic::class, 2);

        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();

        $this->assertSame(StatusAksesUnit::Dinonaktifkan, $unit->status_akses);
        $this->assertNull($unit->undangan_token);
    }

    public function test_tombol_kirim_undangan_di_master_unit_kini_berupa_formulir(): void
    {
        $tampilan = $this->get(route('admin.unit.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Kirim Undangan', $tampilan);
        $this->assertStringContainsString(route('admin.unit.undangan', [], false), $tampilan);
        $this->assertStringNotContainsString('href="#undangan', $tampilan);
    }

    public function test_halaman_aktivasi_menampilkan_formulir_untuk_token_sehat(): void
    {
        Notification::fake();

        $this->post(route('admin.unit.undangan'));
        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();

        $this->get(route('aktivasi.undangan', ['token' => $unit->undangan_token]))
            ->assertOk()
            ->assertSee('Buat kata sandi akun PIC')
            ->assertSee($unit->nama)
            ->assertSee('name="kata_sandi"', false);
    }

    public function test_token_yang_tidak_dikenal_ditolak_dengan_pesan_jujur(): void
    {
        $this->get(route('aktivasi.undangan', ['token' => 'token-yang-tidak-pernah-dibuat']))
            ->assertOk()
            ->assertSee('Tautan undangan tidak berlaku');
    }

    public function test_tautan_yang_sudah_lewat_waktu_ditolak(): void
    {
        Notification::fake();

        $this->post(route('admin.unit.undangan'));
        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();

        $this->travel(73)->hours();

        $this->get(route('aktivasi.undangan', ['token' => $unit->undangan_token]))
            ->assertOk()
            ->assertSee('Tautan undangan sudah kedaluwarsa');

        $this->post(route('aktivasi.undangan.simpan', ['token' => $unit->undangan_token]), [
            'nama' => 'PIC Baru',
            'kata_sandi' => 'sandipic123',
            'kata_sandi_confirmation' => 'sandipic123',
        ])->assertSessionHasErrors('undangan');

        $this->assertNull(User::where('email', 'poliklinik@rsudsoetomo.go.id')->first());
        $this->assertSame(StatusAksesUnit::UndanganTerkirim, $unit->refresh()->status_akses);
    }

    public function test_aktivasi_membuat_akun_pic_dan_mengaktifkan_unit(): void
    {
        Notification::fake();

        $this->post(route('admin.unit.undangan'));
        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();

        $this->post(route('aktivasi.undangan.simpan', ['token' => $unit->undangan_token]), [
            'nama' => 'dr. Surya N., Sp.PD',
            'kata_sandi' => 'sandipic123',
            'kata_sandi_confirmation' => 'sandipic123',
        ])
            ->assertRedirect(route('aktivasi.undangan', ['token' => $unit->undangan_token]))
            ->assertSessionHas('aktivasi_sukses');

        $unit->refresh();

        $this->assertSame(StatusAksesUnit::AkunAktif, $unit->status_akses);
        $this->assertNull($unit->undangan_token);
        $this->assertNull($unit->undangan_berlaku);

        $pengguna = User::where('email', 'poliklinik@rsudsoetomo.go.id')->firstOrFail();

        $this->assertSame(PeranPengguna::Unit, $pengguna->peran);
        $this->assertTrue(Hash::check('sandipic123', $pengguna->password));
        $this->assertTrue($pengguna->terdaftarDiUnit($unit));

        // Peran di pivot mengikuti catatan master unit, bukan nilai bawaan.
        $this->assertSame(
            PeranAksesUnit::KepalaUnit->value,
            $pengguna->unit()->whereKey($unit->getKey())->first()->pivot->peran_akses,
        );
    }

    public function test_aktivasi_menghubungkan_akun_lama_tanpa_menimpa_nama_dan_sandinya(): void
    {
        Notification::fake();

        $penggunaLama = User::factory()->create([
            'email' => 'poliklinik@rsudsoetomo.go.id',
            'name' => 'Petugas Lama',
            'password' => 'sandilama99',
        ]);

        $this->post(route('admin.unit.undangan'));
        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();

        $this->post(route('aktivasi.undangan.simpan', ['token' => $unit->undangan_token]), [
            'nama' => 'Nama Pengganti',
            'kata_sandi' => 'sandibaru123',
            'kata_sandi_confirmation' => 'sandibaru123',
        ])->assertSessionHas('aktivasi_sukses');

        $penggunaLama->refresh();

        $this->assertSame('Petugas Lama', $penggunaLama->name);
        $this->assertTrue(Hash::check('sandilama99', $penggunaLama->password));
        $this->assertSame(1, User::where('email', 'poliklinik@rsudsoetomo.go.id')->count());
        $this->assertTrue($penggunaLama->terdaftarDiUnit($unit->refresh()));
        $this->assertSame(StatusAksesUnit::AkunAktif, $unit->status_akses);
    }

    public function test_formulir_aktivasi_menolak_sandi_yang_tidak_dikonfirmasi(): void
    {
        Notification::fake();

        $this->post(route('admin.unit.undangan'));
        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();

        $this->post(route('aktivasi.undangan.simpan', ['token' => $unit->undangan_token]), [
            'nama' => 'dr. Surya N., Sp.PD',
            'kata_sandi' => 'sandipic123',
            'kata_sandi_confirmation' => 'berbeda-beda',
        ])->assertSessionHasErrors([
            'kata_sandi' => 'Konfirmasi kata sandi tidak sama dengan kata sandi.',
        ]);

        $this->assertSame(StatusAksesUnit::UndanganTerkirim, $unit->refresh()->status_akses);
        $this->assertNull(User::where('email', 'poliklinik@rsudsoetomo.go.id')->first());
    }
}
