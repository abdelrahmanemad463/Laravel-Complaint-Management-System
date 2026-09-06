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
        $this->assertStringContainsString('data-install-button class="hidden shrink-0 items-center gap-1.5', $layout);
        $this->assertStringContainsString('data-install-button-mobile class="mt-3 hidden w-full', $layout);
        $this->assertStringNotContainsString('sm:inline-flex sm:text-sm"><svg data-install-icon', $layout);
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

        $this->assertStringContainsString('@media (display-mode: standalone)', $styles);
        $this->assertStringContainsString('display: none !important', $styles);
        $this->assertStringContainsString('[data-install-button]', $styles);
    }

    public function test_shared_footer_exposes_localized_contact_details_without_location(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Abdelrahman Emad')
            ->assertDontSee('Alexandria')
            ->assertDontSee('Location')
            ->assertSee('tel:01110174868', false)
            ->assertSee('https://www.linkedin.com/in/abdelrahman-emad1', false)
            ->assertSee('https://www.facebook.com/abdelrahman.emad.660867/', false)
            ->assertSee('Complaint Desk. All rights reserved.');

        $this->actingAs($user)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('عبدالرحمن عماد')
            ->assertDontSee('الإسكندرية')
            ->assertDontSee('الموقع')
            ->assertSee('© '.now()->year.' نظام الشكاوى. جميع الحقوق محفوظة.');

        $layout = (string) file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString('flex max-w-7xl flex-wrap items-center', $layout);
        $this->assertStringContainsString('px-4 py-2 text-xs', $layout);
        $this->assertStringNotContainsString("__('common.footer_location')", $layout);
        $this->assertStringNotContainsString("__('common.footer_city')", $layout);
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
