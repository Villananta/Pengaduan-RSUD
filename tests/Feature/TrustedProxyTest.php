<?php

namespace Tests\Feature;

use App\Http\Middleware\ProxyTepercaya;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Batas kepercayaan header proxy.
 *
 * Seluruh throttle berbasis IP bergantung pada request()->ip(). Kalau
 * X-Forwarded-For dipercaya dari mana saja, satu header buatan cukup untuk
 * mendapat jatah baru di setiap permintaan. Tes ini mengunci dua sifat yang
 * membuat header itu aman dipakai: hanya hop proxy yang dipercaya, dan IP
 * klien diambil dari ujung kanan rantai, bukan nilai kiriman klien.
 */
class TrustedProxyTest extends TestCase
{
    public function test_header_proxy_dari_luar_tidak_dipakai_sebagai_ip_klien(): void
    {
        $request = $this->lewatMiddleware('203.0.113.10', '1.2.3.4');

        $this->assertSame('203.0.113.10', $request->ip());
    }

    public function test_ip_klien_diambil_dari_hop_terakhir_di_belakang_edge(): void
    {
        $request = $this->lewatMiddleware('100.64.0.5', '1.2.3.4, 203.0.113.10');

        $this->assertSame('203.0.113.10', $request->ip());
    }

    public function test_daftar_proxy_tidak_memakai_wildcard(): void
    {
        $this->assertNotContains('*', config('proxy.tepercaya'));
    }

    private function lewatMiddleware(string $alamatAsal, string $forwardedFor): Request
    {
        $request = Request::create('/buat-aduan');
        $request->server->set('REMOTE_ADDR', $alamatAsal);
        $request->headers->set('X-Forwarded-For', $forwardedFor);

        (new ProxyTepercaya)->handle($request, fn (Request $request) => $request);

        return $request;
    }
}
