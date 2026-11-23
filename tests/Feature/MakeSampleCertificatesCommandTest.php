<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class MakeSampleCertificatesCommandTest extends TestCase
{
    private string $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->folder = storage_path('framework/testing/samples_' . uniqid());
        File::deleteDirectory($this->folder);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->folder);

        parent::tearDown();
    }

    public function test_creates_four_sample_pdfs(): void
    {
        $this->artisan('certificates:make-samples', ['--output' => $this->folder])
            ->assertExitCode(0);

        $this->assertDirectoryExists($this->folder);

        $berkas = File::files($this->folder);

        $this->assertCount(4, $berkas);

        foreach ($berkas as $file) {
            $this->assertStringStartsWith('%PDF-', (string) file_get_contents($file->getPathname()));
        }

        foreach ([
            'Sertifikat_Seminar_Arief_Soekardi.pdf',
            'Sertifikat_Seminar_Mourice.pdf',
            'Sertifikat_Seminar_Muammal_Hamidy.pdf',
            'Sertifikat_Bootcamp_Akrom_Masani_Iksanul_Amri.pdf',
        ] as $nama) {
            $this->assertFileExists($this->folder.'/'.$nama);
        }
    }

    public function test_creates_only_requested_layout(): void
    {
        $this->artisan('certificates:make-samples', [
            '--output' => $this->folder,
            '--jenis' => 'bootcamp',
        ])->assertExitCode(0);

        $berkas = File::files($this->folder);

        $this->assertCount(1, $berkas);
        $this->assertSame(
            'Sertifikat_Bootcamp_Akrom_Masani_Iksanul_Amri.pdf',
            $berkas[0]->getFilename(),
        );
    }

    public function test_rejects_unknown_jenis(): void
    {
        $this->artisan('certificates:make-samples', [
            '--output' => $this->folder,
            '--jenis' => 'workshop',
        ])
            ->expectsOutputToContain('tidak dikenal')
            ->assertExitCode(1);

        $this->assertDirectoryDoesNotExist($this->folder);
    }

    public function test_rejects_unknown_mode(): void
    {
        $this->artisan('certificates:make-samples', [
            '--output' => $this->folder,
            '--mode' => 'arsip',
        ])->assertExitCode(1);

        $this->assertDirectoryDoesNotExist($this->folder);
    }
}
