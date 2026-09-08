<?php

namespace Tests\Feature;

use App\Models\{Branch, Complaint, ComplaintCategory, ComplaintSource, ComplaintStatus, ComplaintType, Customer, Priority, Service, User};
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::factory()->create();
        $this->user->assignRole('Customer Support');
    }

    public function test_primary_phone_search_finds_customer(): void
    {
        $customer = Customer::factory()->create(['phone_primary' => '01000000001']);
        $this->actingAs($this->user)->get(route('customers.index', ['search' => $customer->phone_primary]))
            ->assertOk()->assertSee($customer->name);
    }

    public function test_each_optional_phone_search_finds_customer(): void
    {
        $customer = Customer::factory()->create(['phone_primary' => '01000000001', 'phone_2' => '01000000002', 'phone_3' => '01000000003', 'phone_4' => '01000000004']);
        foreach (['phone_2', 'phone_3', 'phone_4'] as $phone) {
            $this->actingAs($this->user)->get(route('customers.index', ['search' => $customer->{$phone}]))
                ->assertOk()->assertSee($customer->name);
        }
    }

    public function test_customer_picker_search_returns_at_most_ten_name_or_phone_matches(): void
    {
        Customer::factory()->count(12)->create();
        $target = Customer::factory()->create(['name' => 'Search Target', 'phone_2' => '01155555555']);
        $response = $this->actingAs($this->user)->getJson(route('customers.search', ['q' => '01155555555']));
        $response->assertOk()->assertJsonFragment(['id' => $target->id, 'name' => 'Search Target']);
        $this->assertLessThanOrEqual(10, count($response->json()));

        $response = $this->actingAs($this->user)->getJson(route('customers.search', ['q' => 'Search Target']));
        $response->assertOk()->assertJsonFragment(['id' => $target->id, 'name' => 'Search Target']);
    }

    public function test_complaint_form_requires_and_renders_customer_selector(): void
    {
        $response = $this->actingAs($this->user)->get(route('complaints.create'));
        $response->assertOk()->assertSee('name="customer_id"', false)->assertSee('Ahmed Mohamed')->assertSee('nav-link-active', false);
    }

    public function test_complaint_stores_authenticated_creator_and_relationships(): void
    {
        $customer = Customer::factory()->create();
        $data = $this->complaintData($customer);
        $this->actingAs($this->user)->post(route('complaints.store'), $data)->assertRedirect();
        $this->assertDatabaseHas('complaints', ['customer_id' => $customer->id, 'created_by' => $this->user->id, 'short_description' => $data['short_description']]);
    }

    public function test_status_change_stores_history_and_resolution_metadata(): void
    {
        $customer = Customer::factory()->create();
        $data = $this->complaintData($customer);
        $type = ComplaintType::find($data['type_id']);
        $complaint = Complaint::create($data + ['category_id' => $type->category_id, 'priority_id' => $type->priority_id, 'created_by' => $this->user->id]);
        $solved = ComplaintStatus::where('name_en', 'Solved')->first();
        $this->actingAs($this->user)->put(route('complaints.update', $complaint), array_merge($data, ['status_id' => $solved->id, 'resolution' => 'Replacement provided.', 'status_reason' => 'Customer contacted.']))->assertRedirect();
        $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'status_id' => $solved->id, 'resolved_by' => $this->user->id, 'resolution' => 'Replacement provided.']);
        $this->assertDatabaseHas('complaint_status_histories', ['complaint_id' => $complaint->id, 'to_status_id' => $solved->id, 'changed_by' => $this->user->id]);
    }

    public function test_complaint_store_derives_category_and_priority_from_type(): void
    {
        $customer = Customer::factory()->create();
        $data = $this->complaintData($customer);
        $this->actingAs($this->user)->post(route('complaints.store'), $data)->assertRedirect();
        $type = ComplaintType::find($data['type_id']);
        $this->assertDatabaseHas('complaints', ['customer_id' => $customer->id, 'type_id' => $type->id, 'category_id' => $type->category_id, 'priority_id' => $type->priority_id]);
    }

    public function test_complaint_store_ignores_manipulated_category_and_priority(): void
    {
        $customer = Customer::factory()->create();
        $type = ComplaintType::first();
        $data = array_merge($this->complaintData($customer), [
            'category_id' => ComplaintCategory::where('id', '!=', $type->category_id)->latest('id')->value('id'),
            'priority_id' => Priority::where('id', '!=', $type->priority_id)->latest('id')->value('id'),
        ]);
        $this->actingAs($this->user)->post(route('complaints.store'), $data)->assertRedirect();
        $this->assertDatabaseHas('complaints', ['customer_id' => $customer->id, 'type_id' => $type->id, 'category_id' => $type->category_id, 'priority_id' => $type->priority_id]);
        $this->assertDatabaseMissing('complaints', ['customer_id' => $customer->id, 'category_id' => $data['category_id'], 'priority_id' => $data['priority_id']]);
    }

    public function test_complaint_store_rejects_type_without_category_priority_config(): void
    {
        $unconfigured = ComplaintType::create(['name_en' => 'Unconfigured Type', 'name_ar' => 'نوع غير مضبوط', 'color' => '#111111', 'is_active' => true, 'sort_order' => 99]);
        $customer = Customer::factory()->create();
        $data = array_merge($this->complaintData($customer), ['type_id' => $unconfigured->id]);
        $this->actingAs($this->user)->post(route('complaints.store'), $data)->assertSessionHasErrors('type_id');
    }

    public function test_complaint_update_changes_category_and_priority_when_type_changes(): void
    {
        $customer = Customer::factory()->create();
        $data = $this->complaintData($customer);
        $complaint = Complaint::create($data + ['category_id' => ComplaintType::find($data['type_id'])->category_id, 'priority_id' => ComplaintType::find($data['type_id'])->priority_id, 'created_by' => $this->user->id]);
        $secondType = ComplaintType::where('id', '!=', $data['type_id'])->first();
        $payload = array_merge($data, ['type_id' => $secondType->id, 'short_description' => 'Switched type']);
        $this->actingAs($this->user)->put(route('complaints.update', $complaint), $payload)->assertRedirect();
        $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'type_id' => $secondType->id, 'category_id' => $secondType->category_id, 'priority_id' => $secondType->priority_id, 'short_description' => 'Switched type']);
    }

    public function test_complaint_update_preserves_category_and_priority_when_type_unchanged(): void
    {
        $customer = Customer::factory()->create();
        $data = $this->complaintData($customer);
        $type = ComplaintType::find($data['type_id']);
        $complaint = Complaint::create($data + ['category_id' => $type->category_id, 'priority_id' => $type->priority_id, 'created_by' => $this->user->id]);
        $payload = array_merge($data, ['short_description' => 'Touched description']);
        $this->actingAs($this->user)->put(route('complaints.update', $complaint), $payload)->assertRedirect();
        $complaint->refresh();
        $this->assertSame((int) $type->category_id, (int) $complaint->category_id);
        $this->assertSame((int) $type->priority_id, (int) $complaint->priority_id);
        $this->assertSame('Touched description', $complaint->short_description);
    }

    public function test_complaint_form_hides_category_priority_selects_and_shows_derived_badges(): void
    {
        $response = $this->actingAs($this->user)->get(route('complaints.create'));
        $response->assertOk()
            ->assertDontSee('name="category_id"', false)
            ->assertDontSee('name="priority_id"', false)
            ->assertSee('data-category-id', false)
            ->assertSee('data-derived-category-name', false)
            ->assertSee(__('common.auto_determined'));
        $response = $this->actingAs($this->user)->get(route('complaints.edit', Complaint::create(['customer_id' => Customer::factory()->create()->id, 'type_id' => ComplaintType::first()->id, 'category_id' => ComplaintType::first()->category_id, 'priority_id' => ComplaintType::first()->priority_id, 'branch_id' => Branch::first()->id, 'service_id' => Service::first()->id, 'source_id' => ComplaintSource::first()->id, 'status_id' => ComplaintStatus::where('name_en', 'Pending')->first()->id, 'short_description' => 'Edit layout', 'description' => 'Layout check.', 'complaint_date' => now()->toDateString(), 'created_by' => $this->user->id])));
        $response->assertOk()->assertDontSee('name="category_id"', false)->assertDontSee('name="priority_id"', false)->assertSee('data-derived-category-name', false);
    }

    public function test_complaint_type_master_data_requires_and_stores_category_priority(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::where('name', 'Admin')->first());
        $this->actingAs($admin)->get(route('master.create', 'types'))
            ->assertOk()->assertSee('name="category_id"', false)->assertSee('name="priority_id"', false);
        $category = ComplaintCategory::first();
        $priority = Priority::first();
        $this->actingAs($admin)->post(route('master.store', 'types'), ['name_en' => 'Managed Type', 'name_ar' => 'نوع مُدار', 'color' => '#0f766e', 'category_id' => $category->id, 'priority_id' => $priority->id, 'is_active' => 1, 'sort_order' => 90])->assertRedirect();
        $this->assertDatabaseHas('complaint_types', ['name_en' => 'Managed Type', 'category_id' => $category->id, 'priority_id' => $priority->id]);
        $this->actingAs($admin)->post(route('master.store', 'types'), ['name_en' => 'Broken Type', 'name_ar' => 'نوع خاطئ', 'color' => '#111111'])->assertSessionHasErrors(['category_id', 'priority_id']);
    }

    private function complaintData(Customer $customer): array
    {
        return ['customer_id' => $customer->id, 'branch_id' => Branch::first()->id, 'service_id' => Service::first()->id, 'source_id' => ComplaintSource::first()->id, 'type_id' => ComplaintType::first()->id, 'status_id' => ComplaintStatus::where('name_en', 'Pending')->first()->id, 'short_description' => 'Wrong item received', 'description' => 'The customer received an incorrect item.', 'complaint_date' => now()->toDateString()];
    }
}
