<?php

namespace Tests\Feature;

use App\Models\{Branch, Complaint, ComplaintCategory, ComplaintSource, ComplaintStatus, ComplaintType, Customer, Priority, Service, User};
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
        $complaint = Complaint::create($data + ['created_by' => $this->user->id]);
        $solved = ComplaintStatus::where('name', 'Solved')->first();
        $this->actingAs($this->user)->put(route('complaints.update', $complaint), array_merge($data, ['status_id' => $solved->id, 'resolution' => 'Replacement provided.', 'status_reason' => 'Customer contacted.']))->assertRedirect();
        $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'status_id' => $solved->id, 'resolved_by' => $this->user->id, 'resolution' => 'Replacement provided.']);
        $this->assertDatabaseHas('complaint_status_histories', ['complaint_id' => $complaint->id, 'to_status_id' => $solved->id, 'changed_by' => $this->user->id]);
    }

    private function complaintData(Customer $customer): array
    {
        return ['customer_id' => $customer->id, 'branch_id' => Branch::first()->id, 'service_id' => Service::first()->id, 'source_id' => ComplaintSource::first()->id, 'category_id' => ComplaintCategory::first()->id, 'type_id' => ComplaintType::first()->id, 'priority_id' => Priority::first()->id, 'status_id' => ComplaintStatus::where('name', 'Pending')->first()->id, 'short_description' => 'Wrong item received', 'description' => 'The customer received an incorrect item.', 'complaint_date' => now()->toDateString()];
    }
}
