<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\LayoutConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CertificatePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Halaman pratinjau ikut shell aplikasi dan khusus admin (Uji Layout).
        $this->actingAs(User::factory()->admin()->create());

        // Font inti "times" membuat isi PDF terbaca sebagai teks di tes tanpa
        // bergantung pada berkas Tinos yang tidak ikut di repo.
        $layouts = [];

        foreach (['seminar', 'bootcamp'] as $jenis) {
            /** @var array<string, mixed> $layout */
            $layout = require config_path("layout/{$jenis}.php");
            $layout['font'] = ['serif' => 'times', 'serif_bold' => 'times', 'fallback' => 'times'];
            $layouts[$jenis] = $layout;
        }

        $this->app->instance(LayoutConfig::class, new LayoutConfig($layouts));
    }

    public function test_operator_is_forbidden(): void
    {
        $this->actingAs(User::factory()->operator()->create());

        $this->get('/pratinjau')->assertForbidden();
        $this->get('/pratinjau/pdf')->assertForbidden();
    }

    public function test_requires_login_like_the_rest_of_the_app(): void
    {
        auth()->logout();

        $this->get('/pratinjau')->assertRedirect('/masuk');
        $this->get('/pratinjau/pdf')->assertRedirect('/masuk');
    }

    public function test_page_shows_preview_iframe_for_default_layout(): void
    {
        $response = $this->get('/pratinjau');

        $response->assertOk();
        $response->assertSee('Pratinjau Sertifikat');
        $response->assertSee('Arief Soekardi');
        $response->assertSee('/pratinjau/pdf?jenis=seminar', false);
    }

    public function test_page_follows_selected_layout_and_guides(): void
    {
        $response = $this->get('/pratinjau?jenis=bootcamp&guides=1');

        $response->assertOk();
        $response->assertSee('Akrom Masani Iksanul Amri');
        $response->assertSee('Garis bantu 10 mm dan koordinat elemen ikut tercetak.');
        $response->assertDontSee('Arief Soekardi');
    }

    public function test_pdf_endpoint_streams_pdf_inline(): void
    {
        $response = $this->get('/pratinjau/pdf?jenis=seminar&sampel=Sertifikat_Seminar_Mourice.pdf');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->getContent());
        $this->assertStringContainsString('Mourice', $this->textOf((string) $response->getContent()));
    }

    public function test_pdf_endpoint_supports_guides(): void
    {
        $response = $this->get('/pratinjau/pdf?jenis=seminar&guides=1');

        $response->assertOk();
        $this->assertStringContainsString('GARIS BANTU', $this->textOf((string) $response->getContent()));
    }

    public function test_rejects_unknown_layout(): void
    {
        $this->get('/pratinjau?jenis=workshop')->assertNotFound();
        $this->get('/pratinjau/pdf?jenis=workshop')->assertNotFound();
    }

    private function textOf(string $pdf): string
    {
        preg_match_all('/stream\r?\n(.*?)endstream/s', $pdf, $streams);

        $teks = '';

        foreach ($streams[1] as $stream) {
            $isi = @gzuncompress($stream);

            if ($isi === false) {
                continue;
            }

            preg_match_all('/\[\((.*?)\)\] TJ/', $isi, $cocok);
            $teks .= implode(' ', $cocok[1]).' ';
        }

        return str_replace(['\\(', '\\)'], ['(', ')'], $teks);
    }
}
