<?php

namespace Tests\Feature;

use App\Models\{Branch, Complaint, ComplaintCategory, ComplaintSource, ComplaintStatus, ComplaintType, Customer, Priority, Service, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintFiltersAndAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_multiple_branch_filter_returns_only_selected_branches(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Customer Support');
        $first = Branch::first();
        $second = Branch::skip(1)->first();
        $third = Branch::skip(2)->first();
        $this->makeComplaint($user, $first);
        $this->makeComplaint($user, $second);
        $this->makeComplaint($user, $third);
        $response = $this->actingAs($user)->get(route('complaints.index', ['branch_ids' => [$first->id, $second->id]]));
        $expected = Complaint::whereIn('branch_id', [$first->id, $second->id])->count();
        $response->assertOk()->assertSee('<strong>'.$expected.'</strong>', false);
    }

    public function test_viewer_cannot_create_customer(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Viewer');
        $this->actingAs($user)->get(route('customers.create'))->assertForbidden();
    }

    private function makeComplaint(User $user, Branch $branch): Complaint
    {
        return Complaint::create(['customer_id'=>Customer::factory()->create()->id,'branch_id'=>$branch->id,'service_id'=>Service::first()->id,'source_id'=>ComplaintSource::first()->id,'category_id'=>ComplaintCategory::first()->id,'type_id'=>ComplaintType::first()->id,'priority_id'=>Priority::first()->id,'status_id'=>ComplaintStatus::first()->id,'short_description'=>'Example complaint','description'=>'Example details','complaint_date'=>now()->toDateString(),'created_by'=>$user->id]);
    }
}
