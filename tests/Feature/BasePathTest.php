<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class BasePathTest extends TestCase
{
    public function test_subdirectory_base_path_is_configured_for_frontend_and_apache(): void
    {
        $envExample = file_get_contents(base_path('.env.example'));
        $frontendHelper = file_get_contents(resource_path('js/lib/basePath.js'));
        $appEntry = file_get_contents(resource_path('js/app.js'));
        $htaccess = file_get_contents(public_path('.htaccess'));

        $this->assertStringContainsString('VITE_APP_BASE_PATH=/crystal', $envExample);
        $this->assertStringContainsString('import.meta.env.VITE_APP_BASE_PATH', $frontendHelper);
        $this->assertStringContainsString('configureInertiaRouterBasePath(router)', $appEntry);
        $this->assertStringContainsString('configureFetchBasePath()', $appEntry);
        $this->assertStringContainsString('configureWayfinderBasePath', $appEntry);
        $this->assertStringContainsString('configureDomBasePath()', $appEntry);
        $this->assertStringContainsString('RewriteBase /crystal/', $htaccess);
        $this->assertStringContainsString('RewriteRule ^crystal/(.*)$ $1 [L]', $htaccess);
    }

    public function test_laravel_routes_remain_unprefixed(): void
    {
        $this->assertSame('/login', route('login', absolute: false));
        $this->assertSame('/dashboard', route('dashboard', absolute: false));
        $this->assertSame('/resources', route('resources.index', absolute: false));
        $this->assertSame('', config('fortify.prefix'));
    }

    public function test_inertia_shares_request_base_url_without_prefixing_routes(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('appBasePath', '')
            );
    }
}
