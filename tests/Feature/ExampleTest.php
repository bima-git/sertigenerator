<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        // PRD AU-05: tanpa sesi, semua halaman mengarah ke Masuk.
        $response->assertRedirect('/masuk');
    }
}
