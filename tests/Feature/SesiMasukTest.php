<?php

namespace Tests\Feature;

use App\Enums\PeranPengguna;
use App\Models\MasterUnit;
use App\Models\User;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sesi masuk dan keluar untuk dua konsol.
 *
 * Sebelum fase ini seluruh rute konsol terbuka; sekarang tamu diarahkan ke
 * halaman masuk dan peran menentukan konsol tujuan. Yang diuji bukan hanya
 * formulirnya, tetapi juga penguncian rute dan pembatasan percobaan masuk.
 */
class SesiMasukTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);
    }

    public function test_halaman_masuk_terbuka_tanpa_login(): void
    {
        $this->get(route('masuk'))
            ->assertOk()
            ->assertSee('Masuk ke Konsol Layanan')
            ->assertSee('name="email"', false)
            ->assertSee('name="kata_sandi"', false)
            ->assertSee('Ingat saya di peramban ini');
    }

    public function test_alamat_masuk_lama_sudah_tidak_melayani(): void
    {
        // Alamat lama dulu dipakai publik, jadi jangan dibiarkan hidup lagi
        // supaya orang awam yang mengingatnya tidak menemukan pintu konsol.
        $this->get('/masuk')->assertNotFound();
        $this->post('/masuk')->assertNotFound();
    }

    public function test_situs_pelapor_tidak_menautkan_alamat_masuk(): void
    {
        $this->get(route('pengaduan.create'))
            ->assertOk()
            ->assertDontSee(route('masuk'), false);
    }

    public function test_halaman_masuk_meminta_mesin_pencari_tidak_mengindeks(): void
    {
        $this->get(route('masuk'))
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_masuk_sebagai_admin_mengarahkan_ke_konsol_admin(): void
    {
        $pengguna = User::factory()->admin()->create(['email' => 'humas@rsud.test']);

        $this->post(route('masuk.coba'), [
            'email' => 'humas@rsud.test',
            'kata_sandi' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($pengguna);
    }

    public function test_masuk_sebagai_pic_mengarahkan_ke_konsol_unitnya(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $pengguna = $this->picUntuk($unit, ['email' => 'pic@ifp.test']);

        $this->post(route('masuk.coba'), [
            'email' => 'pic@ifp.test',
            'kata_sandi' => 'password',
        ])->assertRedirect(route('unit.dashboard', $unit));

        $this->assertAuthenticatedAs($pengguna);
    }

    public function test_pic_belum_terikat_unit_mendarat_di_halaman_penjelasan(): void
    {
        User::factory()->state(['peran' => PeranPengguna::Unit])->create(['email' => 'pic@tanpa-unit.test']);

        // Tidak ada lagi halaman pemilih unit: akun yang belum ditautkan
        // cukup diberi tahu bahwa aksesnya belum lengkap.
        $this->post(route('masuk.coba'), [
            'email' => 'pic@tanpa-unit.test',
            'kata_sandi' => 'password',
        ])->assertRedirect(route('unit.beranda'));

        $this->get(route('unit.beranda'))
            ->assertOk()
            ->assertSee('Akun Belum Ditautkan');
    }

    public function test_sandi_salah_mengembalikan_pesan_dan_tetap_tamu(): void
    {
        User::factory()->admin()->create(['email' => 'humas@rsud.test']);

        $this->from(route('masuk'))
            ->post(route('masuk.coba'), [
                'email' => 'humas@rsud.test',
                'kata_sandi' => 'salah-sekali',
            ])
            ->assertRedirect(route('masuk'))
            ->assertSessionHasErrors('email', 'Email atau kata sandi tidak sesuai.');

        $this->assertGuest();
    }

    public function test_email_tidak_terdaftar_memakai_pesan_kegagalan_yang_sama(): void
    {
        // Pesan disatukan supaya halaman masuk tidak bisa dipakai menebak
        // alamat mana yang sudah punya akun.
        $this->post(route('masuk.coba'), [
            'email' => 'belum-ada@rsud.test',
            'kata_sandi' => 'rahasia-juga',
        ])->assertSessionHasErrors('email', 'Email atau kata sandi tidak sesuai.');

        $this->assertGuest();
    }

    public function test_percobaan_masuk_dibatasi_mencegah_penebakan_sandi(): void
    {
        User::factory()->admin()->create(['email' => 'humas@rsud.test']);

        foreach (range(1, 10) as $percobaan) {
            $this->post(route('masuk.coba'), [
                'email' => 'humas@rsud.test',
                'kata_sandi' => 'salah-'.$percobaan,
            ]);
        }

        $this->post(route('masuk.coba'), [
            'email' => 'humas@rsud.test',
            'kata_sandi' => 'satu-lagi-salah',
        ])->assertTooManyRequests();
    }

    public function test_membuka_halaman_masuk_saat_sudah_masuk_mengarahkan_ke_konsol(): void
    {
        $this->masukSebagaiAdmin();

        $this->get(route('masuk'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_pic_yang_sudah_masuk_dibawa_langsung_ke_unitnya(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $this->masukSebagaiPicUntuk($unit);

        // Baik membuka ulang halaman masuk maupun menembak pintu /unit,
        // petugas tidak lagi disodori daftar unit untuk dipilih.
        $this->get(route('masuk'))->assertRedirect(route('unit.dashboard', $unit));
        $this->get(route('unit.beranda'))->assertRedirect(route('unit.dashboard', $unit));
    }

    public function test_mengingat_sesi_mengeluarkan_cookie_pengingat(): void
    {
        User::factory()->admin()->create(['email' => 'humas@rsud.test']);

        $this->post(route('masuk.coba'), [
            'email' => 'humas@rsud.test',
            'kata_sandi' => 'password',
            'ingat' => '1',
        ])->assertCookie('remember_web_'.sha1(SessionGuard::class));
    }

    public function test_keluar_mengakhiri_sesi_dan_kembali_ke_halaman_masuk(): void
    {
        $this->masukSebagaiAdmin();

        // Petugas yang keluar dikembalikan ke pintu masuk petugas, bukan ke
        // formulir pelapor, supaya tidak tertukar kanal saat hendak masuk lagi.
        $this->post(route('keluar'))
            ->assertRedirect(route('masuk'));

        $this->assertGuest();
    }

    public function test_rute_konsol_admin_meminta_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('masuk'));
        $this->get(route('admin.unit.index'))->assertRedirect(route('masuk'));
        $this->post(route('admin.unit.undangan'))->assertRedirect(route('masuk'));
    }

    public function test_rute_konsol_unit_meminta_login(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $this->get(route('unit.beranda'))->assertRedirect(route('masuk'));
        $this->get(route('unit.dashboard', $unit))->assertRedirect(route('masuk'));
        $this->get(route('unit.disposisi.index', $unit))->assertRedirect(route('masuk'));
    }

    public function test_pic_tidak_bisa_membuka_konsol_admin(): void
    {
        $this->masukSebagaiPic();

        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('admin.unit.index'))->assertForbidden();
    }

    public function test_admin_tidak_bisa_membuka_konsol_unit(): void
    {
        $unit = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $this->masukSebagaiAdmin();

        // Konsol unit tertutup bagi konsol humas, termasuk pintu berandanya.
        $this->get(route('unit.beranda'))->assertForbidden();
        $this->get(route('unit.dashboard', $unit))->assertForbidden();
    }
}
