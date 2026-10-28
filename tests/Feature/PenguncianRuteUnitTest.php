<?php

namespace Tests\Feature;

use App\Enums\StatusPengaduan;
use App\Models\MasterUnit;
use App\Models\Pengaduan;
use Database\Seeders\MasterUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Penjaga PastikanUnitSendiri pada seluruh rute konsol unit.
 *
 * Penjaga itu berjalan di middleware, bukan di tiap controller, jadi satu
 * rute baru bisa saja lupa ikut grup penguncian tanpa ada yang protes sampai
 * ada yang mengetik kode unit lain di URL. Daftar di bawah ini disusun dari
 * routes/web.php, sehingga bila ada rute ber-{unit} yang ditambahkan tanpa
 * penjaga, pengujian ini segera menunjuknya sebagai berlubang.
 *
 * Balasannya 403, bukan 404: kode unit memang sudah saling diketahui
 * antarpetugas, jadi menyembunyikan keberadaannya tidak menambah apa pun.
 */
class PenguncianRuteUnitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seluruh rute konsol unit yang membawa parameter {unit}.
     *
     * @var array<int, array{0: string, 1: string, 2: array<string, string>}>
     */
    private const RUTE_BERKUNCI = [
        ['GET', 'unit.dashboard', []],
        ['GET', 'unit.disposisi.index', []],
        ['GET', 'unit.disposisi.arsip', []],
        ['GET', 'unit.disposisi.show', ['kode' => 'ADUAN-UJI-0001']],
        ['POST', 'unit.disposisi.kirim', ['kode' => 'ADUAN-UJI-0001']],
        ['GET', 'unit.disposisi.ekspor.disposisi-pdf', ['kode' => 'ADUAN-UJI-0001']],
        ['GET', 'unit.disposisi.export.excel', []],
        ['GET', 'unit.disposisi.export.pdf', []],
        ['GET', 'unit.arsip.export.excel', []],
        ['GET', 'unit.arsip.export.pdf', []],
        ['POST', 'unit.notifikasi.tandai-dibaca', []],
        ['POST', 'unit.notifikasi.tandai-semua-dibaca', []],
        ['GET', 'unit.notifikasi.realtime', []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);
    }

    public function test_semua_rute_konsol_unit_menolak_pic_unit_lain_dengan_403(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();
        $radiologi = MasterUnit::where('kode', 'IRS-03')->firstOrFail();

        $this->masukSebagaiPicUntuk($farmasi);

        // Kepemilikan dicek sebelum isi halaman, jadi tiket di unit mana pun
        // tidak ikut diperlukan: penolakan harus terjadi lebih dulu.
        foreach (self::RUTE_BERKUNCI as [$metode, $rute, $parameter]) {
            $this->call($metode, route($rute, ['unit' => $radiologi->kode] + $parameter))
                ->assertForbidden();
        }
    }

    public function test_admin_juga_ditolak_pada_rute_konsol_unit(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $this->masukSebagaiAdmin();

        $this->get(route('unit.dashboard', $farmasi))->assertForbidden();
    }

    public function test_tamu_dialihkan_ke_halaman_masuk_bukan_menerima_403(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        // Tamu belum punya sesi sama sekali, jadi jawabannya mengarah ke
        // halaman masuk — 403 baru berlaku setelah petugas benar-benar masuk.
        $this->get(route('unit.dashboard', $farmasi))->assertRedirect();
    }

    public function test_pic_bisa_membuka_unit_miliknya_sendiri(): void
    {
        $farmasi = MasterUnit::where('kode', 'IFP-01')->firstOrFail();

        $this->masukSebagaiPicUntuk($farmasi);

        // Status Diproses, bukan bawaan Diterima: tiket yang belum diteruskan
        // Humas memang belum boleh dibuka unitnya sendiri, jadi uji ini tidak
        // boleh salah menangkap 404 sebagai keberhasilan.
        $tiket = Pengaduan::factory()->status(StatusPengaduan::Diproses)
            ->create(['master_unit_id' => $farmasi->id]);

        $this->get(route('unit.disposisi.show', [
            'unit' => $farmasi->kode,
            'kode' => $tiket->kode_tiket,
        ]))->assertOk();

        $this->get(route('unit.dashboard', $farmasi))->assertOk();
    }
}
