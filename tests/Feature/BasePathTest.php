<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class BasePathTest extends TestCase
{
    use RefreshDatabase;

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
        $this->assertStringContainsString('window.route = function (...args)', $frontendHelper);
        $this->assertStringContainsString('RewriteBase /crystal/', $htaccess);
        $this->assertStringContainsString('RewriteRule ^crystal/(.*)$ $1 [L]', $htaccess);
    }

    public function test_auth_forms_normalize_ziggy_urls_through_frontend_base_path_helper(): void
    {
        $login = file_get_contents(resource_path('js/Pages/Auth/Login.vue'));
        $register = file_get_contents(resource_path('js/Pages/Auth/Register.vue'));
        $userLayout = file_get_contents(resource_path('js/Layouts/UserLayout.vue'));
        $folderItem = file_get_contents(resource_path('js/Pages/User/Resources/FolderItem.vue'));

        $this->assertStringContainsString("form.post(toApplicationUrl(route('login'))", $login);
        $this->assertStringContainsString(":href=\"toApplicationUrl(route('password.request'))\"", $login);
        $this->assertStringContainsString("form.post(toApplicationUrl(route('register'))", $register);
        $this->assertStringContainsString(":href=\"toApplicationUrl(route('login'))\"", $register);
        $this->assertStringContainsString("const loginPath = withBasePath('/login')", $userLayout);
        $this->assertStringNotContainsString("const loginPath = '/login'", $userLayout);
        $this->assertStringContainsString("const loginPath = withBasePath('/login')", $folderItem);
        $this->assertStringNotContainsString("const loginPath = '/login'", $folderItem);
    }

    public function test_laravel_routes_remain_unprefixed(): void
    {
        $this->assertSame('/login', route('login', absolute: false));
        $this->assertSame('/dashboard', route('dashboard', absolute: false));
        $this->assertSame('/resources', route('resources.index', absolute: false));
        $this->assertSame('', config('fortify.prefix'));
    }

    public function test_redirect_locations_include_the_apache_base_url_when_present(): void
    {
        $user = User::factory()->create();

        config()->set('app.frontend_base_path', '/crystal');

        $this->withServerVariables([
            'HTTP_HOST' => '58.69.118.16:85',
            'SCRIPT_NAME' => '/crystal/index.php',
            'REQUEST_URI' => '/crystal/login',
        ])->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/crystal/resources');
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
