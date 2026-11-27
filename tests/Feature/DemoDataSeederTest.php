<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\EventSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_mengisi_sertifikat_contoh_sekali_saja(): void
    {
        // Render PDF sungguhan sudah diuji di tempat lain; di sini cukup PDF tiruan supaya cepat.
        $this->mock(\App\Services\PdfRenderer::class, fn ($m) => $m->shouldReceive('render')->andReturn('%PDF-1.4'));
        User::factory()->admin()->create();
        $this->seed(EventSeeder::class);

        $this->seed(DemoDataSeeder::class);

        $this->assertSame(24, Certificate::count());
        $this->assertSame(2, Certificate::where('status', 'dibatalkan')->count());
        $this->assertSame(2, User::where('peran', 'operator')->count());
        $this->assertTrue(Certificate::where('jenis', 'bootcamp')->exists());
        $this->assertSame(24, Certificate::distinct()->count('nama_peserta'));

        // Dijalankan ulang: tidak menambah data.
        $this->seed(DemoDataSeeder::class);
        $this->assertSame(24, Certificate::count());
    }
}
