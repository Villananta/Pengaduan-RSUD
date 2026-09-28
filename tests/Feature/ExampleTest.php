<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Halaman akar langsung mengarahkan pelapor ke formulir pengaduan.
     */
    public function test_halaman_akar_mengarah_ke_formulir_pengaduan(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/buat-aduan');
    }
}
