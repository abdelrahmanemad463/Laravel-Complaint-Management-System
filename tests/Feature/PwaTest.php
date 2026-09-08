<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_layout_exposes_installation_metadata(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@example.com')->first();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('name="theme-color" content="#4f46e5"', false)
            ->assertSee('rel="apple-touch-icon"', false)
            ->assertSee('id="wco-titlebar"', false);

        $layout = (string) file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringNotContainsString('data-install-button', $layout);
        $this->assertStringNotContainsString('data-install-modal', $layout);
        $this->assertStringNotContainsString('install_instructions_title', $layout);
    }

    public function test_authenticated_layout_exposes_theme_switch_and_bootstrap(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee("localStorage.getItem('complaint-theme')", false)
            ->assertSee("document.documentElement.dataset.theme", false)
            ->assertSee(__('common.dark_mode'), false)
            ->assertSee(__('common.theme_switcher'), false);

        $styles = (string) file_get_contents(resource_path('css/app.css'));
        $this->assertStringContainsString("html[data-theme='dark'] .card", $styles);
        $this->assertStringContainsString("html[data-theme='dark'] .form-input", $styles);
        $this->assertStringContainsString("html[data-theme='dark'] #branch-results", $styles);
        $this->assertStringContainsString("html[data-theme='dark'] .form-label", $styles);
        $this->assertStringContainsString("html[data-theme='dark'] .btn-secondary", $styles);
        $this->assertStringContainsString("html[data-theme='dark'] .nav-link", $styles);
        $this->assertStringContainsString('#wco-titlebar', $styles);
        $this->assertStringContainsString('env(titlebar-area-width', $styles);
        $this->assertStringContainsString('-webkit-app-region: drag', $styles);
        $this->assertStringNotContainsString('[data-install-button]', $styles);
        $this->assertStringNotContainsString('[data-install-modal]', $styles);
    }

    public function test_shared_footer_exposes_localized_attribution_without_contact_or_social_links(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sultan Ayub')
            ->assertSee('Managed by IT Department')
            ->assertDontSee('tel:', false)
            ->assertDontSee('facebook.com', false)
            ->assertDontSee('linkedin.com', false);

        $this->actingAs($user)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('سلطان أيوب')
            ->assertSee('قسم تقنية المعلومات')
            ->assertSee('© '.now()->year.' سلطان أيوب. جميع الحقوق محفوظة. بإدارة قسم تقنية المعلومات.');

        $layout = (string) file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString('items-center justify-center px-4 py-2', $layout);
        $this->assertStringNotContainsString("__('common.footer_location')", $layout);
        $this->assertStringNotContainsString("__('common.footer_city')", $layout);
        $this->assertStringNotContainsString("__('common.footer_contact')", $layout);
        $this->assertStringNotContainsString("__('common.footer_social')", $layout);
    }

    public function test_pwa_manifest_and_assets_are_valid(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Complaint Desk', $manifest['name']);
        $this->assertSame('Complaints', $manifest['short_name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('#4f46e5', $manifest['theme_color']);
        $this->assertSame('./', $manifest['scope']);

        foreach (['icons/icon-180x180.png', 'icons/icon-192x192.png', 'icons/icon-512x512.png', 'icons/icon-512x512-maskable.png', 'service-worker.js'] as $asset) {
            $this->assertFileExists(public_path($asset));
        }
    }

    public function test_service_worker_does_not_cache_private_pages_or_api_responses(): void
    {
        $serviceWorker = (string) file_get_contents(public_path('service-worker.js'));

        $this->assertStringContainsString("if (relativePath === 'build/manifest.json')", $serviceWorker);
        $this->assertStringContainsString("fetch(request, { cache: 'no-store' })", $serviceWorker);
        $this->assertStringContainsString("const isStaticAsset = relativePath.startsWith('build/') || relativePath.startsWith('icons/');", $serviceWorker);
        $this->assertStringContainsString('if (!isStaticAsset) return;', $serviceWorker);
    }
}
