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
            ->assertSee('rel="apple-touch-icon"', false);
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
