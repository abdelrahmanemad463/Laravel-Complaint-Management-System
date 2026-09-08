<?php

namespace Tests\Feature;

use App\Exports\ComplaintsExport;
use App\Models\{Branch, Complaint, ComplaintCategory, ComplaintSource, ComplaintStatus, ComplaintType, Customer, Priority, Service, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MasterDataLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_master_data_store_requires_both_localized_names(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::where('name', 'Admin')->first());

        $this->actingAs($admin)->post(route('master.store', 'services'), ['name_en' => 'Kiosk', 'color' => '#111111', 'is_active' => 1])
            ->assertSessionHasErrors('name_ar');

        $this->actingAs($admin)->post(route('master.store', 'services'), ['name_ar' => 'كشك', 'color' => '#111111', 'is_active' => 1])
            ->assertSessionHasErrors('name_en');

        $this->actingAs($admin)->post(route('master.store', 'services'), ['name_en' => 'Kiosk', 'name_ar' => 'كشك', 'color' => '#111111', 'is_active' => 1])
            ->assertRedirect();
        $this->assertDatabaseHas('services', ['name_en' => 'Kiosk', 'name_ar' => 'كشك']);
    }

    public function test_master_data_update_requires_both_localized_names(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::where('name', 'Admin')->first());
        $branch = Branch::first();

        $this->actingAs($admin)->put(route('master.update', ['branches', $branch->id]), ['name_en' => 'HQ', 'code' => $branch->code, 'is_active' => 1])
            ->assertSessionHasErrors('name_ar');

        $this->actingAs($admin)->put(route('master.update', ['branches', $branch->id]), ['name_en' => 'HQ', 'name_ar' => 'المقر الرئيسي', 'code' => $branch->code, 'is_active' => 1])
            ->assertRedirect();
        $this->assertSame('HQ', $branch->fresh()->name_en);
        $this->assertSame('المقر الرئيسي', $branch->fresh()->name_ar);
    }

    public function test_localized_name_returns_locale_appropriate_value_with_fallback(): void
    {
        $branch = Branch::first();
        $branch->update(['name_en' => 'Downtown', 'name_ar' => 'وسط المدينة']);

        app()->setLocale('en');
        $this->assertSame('Downtown', $branch->localized_name);

        app()->setLocale('ar');
        $this->assertSame('وسط المدينة', $branch->localized_name);

        $branch->update(['name_ar' => '']);
        $this->assertSame('Downtown', $branch->localized_name);
    }

    public function test_arabic_locale_master_data_pages_render_arabic_names(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $status = ComplaintStatus::first();

        $this->get('/locale/ar');
        $this->actingAs($admin)->get(route('master.index', 'statuses'))
            ->assertOk()
            ->assertSee($status->localized_name);
    }

    public function test_complaint_export_uses_localized_master_data_names(): void
    {
        $user = User::where('email', 'admin@example.com')->first();
        $branch = Branch::first();
        $branch->update(['name_en' => 'Downtown', 'name_ar' => 'وسط المدينة']);
        $complaint = Complaint::create([
            'customer_id' => Customer::factory()->create()->id,
            'branch_id' => $branch->id,
            'service_id' => Service::first()->id,
            'source_id' => ComplaintSource::first()->id,
            'category_id' => ComplaintCategory::first()->id,
            'type_id' => ComplaintType::first()->id,
            'priority_id' => Priority::first()->id,
            'status_id' => ComplaintStatus::first()->id,
            'short_description' => 'Export localization',
            'description' => 'Export details',
            'complaint_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        app()->setLocale('en');
        $this->assertSame('Downtown', (new ComplaintsExport([]))->map($complaint)[3]);

        app()->setLocale('ar');
        $this->assertSame('وسط المدينة', (new ComplaintsExport([]))->map($complaint)[3]);
    }
}