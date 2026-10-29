<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_guests_are_redirected_from_the_root_panel_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('filament.user.auth.login'));
    }
}
