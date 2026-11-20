<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class ImportFontsCommandTest extends TestCase
{
    private string $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->folder = storage_path('framework/testing/fonts');
        File::deleteDirectory($this->folder);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->folder);

        parent::tearDown();
    }

    public function test_rejects_missing_source_folder(): void
    {
        $this->artisan('certificates:import-fonts', [
            '--source' => $this->folder.'/tidak-ada',
            '--output' => $this->folder.'/out',
        ])
            ->expectsOutputToContain('tidak ditemukan')
            ->assertExitCode(1);
    }

    public function test_rejects_source_without_ttf_files(): void
    {
        File::ensureDirectoryExists($this->folder.'/kosong');
        File::put($this->folder.'/kosong/catatan.txt', 'bukan font');

        $this->artisan('certificates:import-fonts', [
            '--source' => $this->folder.'/kosong',
            '--output' => $this->folder.'/out',
        ])
            ->expectsOutputToContain('Tidak ada berkas .ttf')
            ->assertExitCode(1);
    }
}
