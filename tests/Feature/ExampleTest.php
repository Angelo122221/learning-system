<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guests should be sent to the public resources page from the root URL.
     */
    public function test_root_url_redirects_to_resources_page(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('resources.index'));
    }

    public function test_guests_can_view_the_public_resources_page(): void
    {
        $response = $this->get(route('resources.index'));

        $response->assertOk();
    }
}
