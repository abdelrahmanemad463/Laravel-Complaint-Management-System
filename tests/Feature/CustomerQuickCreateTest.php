<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerQuickCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $support;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->support = User::factory()->create();
        $this->support->assignRole('Customer Support');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Customer',
            'phone_primary' => '01099998888',
            'phone_2' => '01111111111',
            'phone_3' => null,
            'phone_4' => '',
            'address' => 'Alexandria',
        ], $overrides);
    }

    public function test_quick_store_creates_customer_and_returns_json_without_complaint(): void
    {
        $complaintsBefore = \App\Models\Complaint::count();
        $response = $this->actingAs($this->support)->postJson(route('customers.quick-store'), $this->payload());

        $response->assertCreated()
            ->assertJsonPath('message', __('customers.created'))
            ->assertJsonPath('customer.name', 'New Customer')
            ->assertJsonPath('customer.phone_primary', '01099998888')
            ->assertJsonPath('customer.address', 'Alexandria');

        $this->assertDatabaseHas('customers', ['name' => 'New Customer', 'phone_primary' => '01099998888', 'phone_2' => '01111111111', 'address' => 'Alexandria']);
        $this->assertSame($complaintsBefore, \App\Models\Complaint::count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'customer.created']);
    }

    public function test_quick_store_allows_optional_phones_to_be_empty(): void
    {
        $this->actingAs($this->support)->postJson(route('customers.quick-store'), [
            'name' => 'Minimal Customer',
            'phone_primary' => '01000001111',
        ])->assertCreated();

        $this->assertDatabaseHas('customers', ['name' => 'Minimal Customer', 'phone_primary' => '01000001111']);
    }

    public function test_quick_store_validation_errors_are_localized_and_create_nothing(): void
    {
        app()->setLocale('ar');
        $before = Customer::count();
        $response = $this->actingAs($this->support)->postJson(route('customers.quick-store'), ['name' => '', 'phone_primary' => '']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone_primary'])
            ->assertJsonPath('errors.phone_primary.0', 'حقل رقم الهاتف الأساسي مطلوب.');

        $this->assertSame($before, Customer::count());
    }

    public function test_quick_store_english_validation_errors(): void
    {
        $this->actingAs($this->support)->postJson(route('customers.quick-store'), ['name' => '', 'phone_primary' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone_primary'])
            ->assertJsonPath('errors.phone_primary.0', 'The primary phone field is required.');
    }

    public function test_quick_store_returns_existing_customer_when_primary_phone_exists(): void
    {
        $before = Customer::count();
        $existing = Customer::factory()->create(['name' => 'Existing Customer', 'phone_primary' => '01022223333']);

        $response = $this->actingAs($this->support)->postJson(route('customers.quick-store'), $this->payload(['phone_primary' => '01022223333']));

        $response->assertUnprocessable()
            ->assertJsonPath('message', __('customers.phone_exists'))
            ->assertJsonPath('customer.id', $existing->id)
            ->assertJsonPath('customer.name', 'Existing Customer');

        $this->assertSame($before + 1, Customer::count());
    }

    public function test_quick_store_detects_phone_registered_as_secondary_number(): void
    {
        $before = Customer::count();
        $existing = Customer::factory()->create(['phone_primary' => '01033334444', 'phone_2' => '01055556666']);

        $this->actingAs($this->support)->postJson(route('customers.quick-store'), $this->payload(['phone_primary' => '01055556666']))
            ->assertUnprocessable()
            ->assertJsonPath('customer.id', $existing->id);

        $this->assertSame($before + 1, Customer::count());
    }

    public function test_quick_store_requires_customer_create_permission(): void
    {
        $before = Customer::count();
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $this->actingAs($viewer)->postJson(route('customers.quick-store'), $this->payload())->assertForbidden();
        $this->assertSame($before, Customer::count());
    }

    public function test_customer_search_finds_by_any_of_the_four_phones(): void
    {
        $customer = Customer::factory()->create([
            'phone_primary' => '01010101010',
            'phone_2' => '01020202020',
            'phone_3' => '01030303030',
            'phone_4' => '01040404040',
        ]);

        foreach (['01010101010', '01020202020', '01030303030', '01040404040'] as $phone) {
            $this->actingAs($this->support)->getJson(route('customers.search', ['q' => $phone]))
                ->assertOk()
                ->assertJsonFragment(['id' => $customer->id]);
        }
    }

    public function test_create_page_renders_localized_customer_modal(): void
    {
        $content = $this->actingAs($this->support)->get(route('complaints.create'))->assertOk()->getContent();

        $this->assertStringContainsString('data-customer-create-modal', $content);
        $this->assertStringContainsString(__('customers.create_title'), $content);
        $this->assertStringContainsString(__('customers.create_new'), $content);
        $this->assertStringContainsString(__('customers.not_found'), $content);
        $this->assertStringContainsString(__('customers.save'), $content);
        $this->assertStringContainsString(__('common.cancel'), $content);
        $this->assertStringContainsString('name="phone_primary"', $content);
    }

    public function test_create_page_renders_arabic_customer_modal(): void
    {
        app()->setLocale('ar');
        $content = $this->actingAs($this->support)->get(route('complaints.create'))->assertOk()->getContent();

        $this->assertStringContainsString('إضافة عميل', $content);
        $this->assertStringContainsString('إضافة عميل جديد', $content);
        $this->assertStringContainsString('العميل غير موجود.', $content);
        $this->assertStringContainsString('حفظ العميل', $content);
    }
}
