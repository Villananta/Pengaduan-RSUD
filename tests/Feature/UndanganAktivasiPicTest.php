<?php

namespace Tests\Feature;

use App\Enums\KategoriUnit;
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
use stdClass;
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

    public function test_unit_yang_alamatnya_dipakai_akun_humas_dilewati_saat_kirim(): void
    {
        // Tautan ke alamat akun humas pasti ditolak di halaman aktivasi,
        // jadi lebih baik tidak dikirim sejak awal.
        Notification::fake();

        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();

        User::factory()->admin()->create(['email' => $unit->akun_simrs]);

        $this->post(route('admin.unit.undangan'));

        // Dua unit ber-email lainnya tetap diundang; alamat yang bentrok
        // dengan akun humas dilewati.
        Notification::assertSentOnDemand(UndanganAktivasiPic::class, 2);

        $unit->refresh();

        $this->assertSame(StatusAksesUnit::BelumDiundang, $unit->status_akses);
        $this->assertNull($unit->undangan_token);
    }

    public function test_aktivasi_menolak_alamat_akun_humas_tanpa_menurunkan_perannya(): void
    {
        Notification::fake();

        $this->post(route('admin.unit.undangan'));
        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();

        // Akun humas baru muncul setelah undangan terkirim, misalnya karena
        // admin memakai emailnya sendiri untuk mencoba alur ini. Tautan yang
        // sudah beredar tidak boleh mengubah perannya menjadi PIC unit.
        $adminLain = User::factory()->admin()->create(['email' => $unit->akun_simrs]);

        $this->post(route('aktivasi.undangan.simpan', ['token' => $unit->undangan_token]), [
            'nama' => 'PIC Uji',
            'kata_sandi' => 'sandipic123',
            'kata_sandi_confirmation' => 'sandipic123',
        ])->assertSessionHasErrors('undangan');

        $adminLain->refresh();

        $this->assertSame(PeranPengguna::Admin, $adminLain->peran);
        $this->assertFalse($adminLain->terdaftarDiUnit($unit));

        $unit->refresh();

        $this->assertSame(StatusAksesUnit::UndanganTerkirim, $unit->status_akses);
        $this->assertNotNull($unit->undangan_token);
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

    public function test_menonaktifkan_unit_membatalkan_tautan_undangan_yang_sudah_beredar(): void
    {
        Notification::fake();

        $this->post(route('admin.unit.undangan'));
        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();
        $token = $unit->undangan_token;

        $this->assertNotNull($token);

        // Admin mengubah status unit, bukan lewat formulir unit.
        $this->post(route('admin.unit.status', ['unit' => 'IRJ-05']));

        $unit->refresh();

        $this->assertFalse($unit->aktif);
        $this->assertNull($unit->undangan_token);
        $this->assertNull($unit->undangan_berlaku);

        // Tautan yang terlanjur dikirim tidak boleh lagi membuka formulir.
        $this->get(route('aktivasi.undangan', ['token' => $token]))
            ->assertOk()
            ->assertSee('Tautan undangan tidak berlaku');

        $this->post(route('aktivasi.undangan.simpan', ['token' => $token]), [
            'nama' => 'PIC Terlambat',
            'kata_sandi' => 'sandipic123',
            'kata_sandi_confirmation' => 'sandipic123',
        ])->assertSessionHasErrors('undangan');

        $this->assertNull(User::where('email', 'poliklinik@rsudsoetomo.go.id')->first());
    }

    public function test_aktivasi_ditolak_untuk_unit_nonaktif_yang_masih_menyimpan_token_lama(): void
    {
        Notification::fake();

        $this->post(route('admin.unit.undangan'));
        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();

        // Data lama bisa saja masih menyimpan token untuk unit yang sudah
        // dinonaktifkan, misalnya dari sebelum pembatalan otomatis ada.
        // Tautan seperti ini tetap harus ditolak, bukan hanya dikosongkan.
        $token = $unit->undangan_token;
        $unit->aktif = false;
        $unit->status_akses = StatusAksesUnit::Dinonaktifkan;
        $unit->save();

        $this->get(route('aktivasi.undangan', ['token' => $token]))
            ->assertOk()
            ->assertSee('Undangan unit ini sudah dibatalkan')
            ->assertDontSee('name="kata_sandi"', false);

        $this->post(route('aktivasi.undangan.simpan', ['token' => $token]), [
            'nama' => 'PIC Uji',
            'kata_sandi' => 'sandipic123',
            'kata_sandi_confirmation' => 'sandipic123',
        ])->assertSessionHasErrors('undangan');

        $unit->refresh();

        $this->assertSame(StatusAksesUnit::Dinonaktifkan, $unit->status_akses);
        $this->assertNull(User::where('email', 'poliklinik@rsudsoetomo.go.id')->first());
    }

    public function test_aktivasi_memakai_alamat_yang_menerima_undangan_bukan_alamat_baru(): void
    {
        Notification::fake();

        $this->post(route('admin.unit.undangan'));

        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();

        // Alamat disalin saat kirim, jadi tautan terikat pada pemilik email
        // yang benar-benar menerima undangan.
        $this->assertSame('poliklinik@rsudsoetomo.go.id', $unit->email);

        // Admin mengganti alamat PIC setelah email berangkat. Tautan yang
        // sudah dipegang PIC lama tetap harus membuat akun untuk alamat itu.
        $this->post(route('admin.unit.update', ['unit' => 'IRJ-05']), [
            'nama' => 'Instalasi Rawat Jalan (Poliklinik)',
            'kategori' => KategoriUnit::InstalasiMedis->value,
            'peran_akses' => PeranAksesUnit::KepalaUnit->value,
            'status_akses' => StatusAksesUnit::UndanganTerkirim->value,
            'aktif' => '1',
            'akun_simrs' => 'alamat.baru@rsudsoetomo.go.id',
        ])->assertSessionHasNoErrors();

        $this->post(route('aktivasi.undangan.simpan', ['token' => $unit->undangan_token]), [
            'nama' => 'PIC Uji',
            'kata_sandi' => 'sandipic123',
            'kata_sandi_confirmation' => 'sandipic123',
        ])->assertSessionHas('aktivasi_sukses');

        $this->assertNotNull(User::where('email', 'poliklinik@rsudsoetomo.go.id')->first());
        $this->assertNull(User::where('email', 'alamat.baru@rsudsoetomo.go.id')->first());
    }

    public function test_kirim_ulang_setelah_alamat_diubah_mengunci_alamat_baru(): void
    {
        Notification::fake();

        $this->post(route('admin.unit.undangan'));

        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();
        $unit->update(['akun_simrs' => 'alamat.baru@rsudsoetomo.go.id']);

        // Unit masih berstatus UndanganTerkirim, jadi admin bisa mengirim
        // ulang dan salinan alamatnya ikut diperbarui.
        $this->post(route('admin.unit.undangan'));

        $unit->refresh();

        $this->assertSame('alamat.baru@rsudsoetomo.go.id', $unit->email);

        $this->post(route('aktivasi.undangan.simpan', ['token' => $unit->undangan_token]), [
            'nama' => 'PIC Uji',
            'kata_sandi' => 'sandipic123',
            'kata_sandi_confirmation' => 'sandipic123',
        ])->assertSessionHas('aktivasi_sukses');

        $this->assertNotNull(User::where('email', 'alamat.baru@rsudsoetomo.go.id')->first());
        $this->assertNull(User::where('email', 'poliklinik@rsudsoetomo.go.id')->first());
    }

    public function test_alamat_lama_di_kolom_email_dipindahkan_ke_akun_simrs_saat_kirim(): void
    {
        // Baris lama bisa hanya punya kolom email, yang tidak tampil di form.
        // Alamatnya tetap dipakai, tapi dipindahkan ke akun_simrs supaya
        // admin bisa melihat dan menyuntingnya.
        Notification::fake();

        MasterUnit::where('kode', 'IRJ-05')->firstOrFail()->update([
            'akun_simrs' => null,
            'email' => 'pic.lama@rsudsoetomo.go.id',
        ]);

        $this->post(route('admin.unit.undangan'));

        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();

        $this->assertNotNull($unit->undangan_token);
        $this->assertSame('pic.lama@rsudsoetomo.go.id', $unit->akun_simrs);
        $this->assertSame('pic.lama@rsudsoetomo.go.id', $unit->email);

        $this->get(route('admin.unit.edit', ['unit' => 'IRJ-05']))
            ->assertOk()
            ->assertSee('pic.lama@rsudsoetomo.go.id');
    }

    public function test_menghapus_alamat_pic_membatalkan_undangan_yang_sedang_berjalan(): void
    {
        Notification::fake();

        $this->post(route('admin.unit.undangan'));
        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();
        $token = $unit->undangan_token;

        // Admin mengosongkan alamat PIC lewat form unit.
        $this->post(route('admin.unit.update', ['unit' => 'IRJ-05']), [
            'nama' => 'Instalasi Rawat Jalan (Poliklinik)',
            'kategori' => KategoriUnit::InstalasiMedis->value,
            'peran_akses' => PeranAksesUnit::KepalaUnit->value,
            'status_akses' => StatusAksesUnit::UndanganTerkirim->value,
            'aktif' => '1',
        ])->assertSessionHasNoErrors();

        $unit->refresh();

        $this->assertNull($unit->akun_simrs);
        $this->assertNull($unit->email);
        $this->assertNull($unit->undangan_token);
        $this->assertSame(StatusAksesUnit::BelumDiundang, $unit->status_akses);

        // Tautan lama tidak boleh lagi membuat akun.
        $this->post(route('aktivasi.undangan.simpan', ['token' => $token]), [
            'nama' => 'PIC Uji',
            'kata_sandi' => 'sandipic123',
            'kata_sandi_confirmation' => 'sandipic123',
        ])->assertSessionHasErrors('undangan');

        $this->assertNull(User::where('email', 'poliklinik@rsudsoetomo.go.id')->first());
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
            ->assertSee('name="kata_sandi"', false)
            // Halaman ini bisa dibuka siapa pun yang memegang tautannya,
            // jadi alamat email PIC tidak ikut ditampilkan.
            ->assertDontSee('poliklinik@rsudsoetomo.go.id');
    }

    public function test_email_undangan_tetap_tersusun_setelah_token_dikosongkan(): void
    {
        Notification::fake();

        $this->post(route('admin.unit.undangan'));

        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();
        $token = $unit->undangan_token;

        // Notifikasi dibangun saat pengiriman, lalu baru diproses worker
        // beberapa saat kemudian.
        $undangan = new UndanganAktivasiPic($unit, $token);

        // Token sudah dikosongkan sebelum worker sempat jalan, misalnya
        // karena PIC mengaktifkan dari email lain atau admin mengirim ulang.
        $unit->undangan_token = null;
        $unit->undangan_berlaku = null;
        $unit->save();

        $pesan = $undangan->toMail(new stdClass);

        $this->assertSame(
            route('aktivasi.undangan', ['token' => $token]),
            $pesan->actionUrl,
        );
    }

    public function test_token_yang_tidak_dikenal_ditolak_dengan_pesan_jujur(): void
    {
        $this->get(route('aktivasi.undangan', ['token' => 'token-yang-tidak-pernah-dibuat']))
            ->assertOk()
            ->assertSee('Tautan undangan tidak berlaku');
    }

    public function test_token_tidak_ditemukan_bila_huruf_besar_kecilnya_diubah(): void
    {
        Notification::fake();

        $this->post(route('admin.unit.undangan'));

        $unit = MasterUnit::where('kode', 'IRJ-05')->firstOrFail();

        // Token dibentuk manual supaya pasti punya huruf, bukan hanya angka.
        $token = 'AbCdEfGhIjKlMnOpQrStUvWxYz0123456789abcd';

        $unit->update([
            'undangan_token' => $token,
            'undangan_berlaku' => now()->addHour(),
        ]);

        $this->get(route('aktivasi.undangan', ['token' => $token]))
            ->assertOk()
            ->assertSee('Buat kata sandi akun PIC');

        // Collation MySQL yang lama menganggap kedua tautan ini sama.
        $this->get(route('aktivasi.undangan', ['token' => strtolower($token)]))
            ->assertOk()
            ->assertSee('Tautan undangan tidak berlaku');

        $this->post(route('aktivasi.undangan.simpan', ['token' => strtolower($token)]), [
            'nama' => 'PIC Uji',
            'kata_sandi' => 'sandipic123',
            'kata_sandi_confirmation' => 'sandipic123',
        ])->assertSessionHasErrors('undangan');

        $this->assertNull(User::where('email', 'poliklinik@rsudsoetomo.go.id')->first());
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

        // Akun lama di sini adalah PIC yang sudah tercatat sebagai akun unit,
        // bukan akun humas. Akun humas memang ditolak oleh alur undangan.
        $penggunaLama = User::factory()->create([
            'email' => 'poliklinik@rsudsoetomo.go.id',
            'name' => 'Petugas Lama',
            'peran' => PeranPengguna::Unit,
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
