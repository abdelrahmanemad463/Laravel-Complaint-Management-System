<?php

namespace Tests\Feature;

use App\Models\{Branch, Complaint, ComplaintCategory, ComplaintSource, ComplaintStatus, ComplaintType, Customer, Priority, Service, User};
use Spatie\Permission\Models\Role;
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

    public function test_demo_seeder_creates_one_hundred_customers_and_complaints(): void
    {
        $this->assertSame(100, Customer::count());
        $this->assertSame(100, Complaint::count());
    }

    public function test_complaints_index_paginates_thirty_records(): void
    {
        $user = User::where('email', 'admin@example.com')->first();
        foreach (range(1, 35) as $number) {
            $this->makeComplaint($user, Branch::first());
        }

        $firstPage = $this->actingAs($user)->get(route('complaints.index'));
        $secondPage = $this->actingAs($user)->get(route('complaints.index', ['page' => 2]));

        $firstPage->assertOk();
        $secondPage->assertOk();
        $this->assertSame(30, substr_count($firstPage->getContent(), '<td>#'));
        $this->assertSame(30, substr_count($secondPage->getContent(), '<td>#'));
    }

    public function test_customers_index_paginates_thirty_records(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        Customer::factory()->count(35)->create();

        $firstPage = $this->actingAs($admin)->get(route('customers.index'));
        $secondPage = $this->actingAs($admin)->get(route('customers.index', ['page' => 2]));

        $firstPage->assertOk();
        $secondPage->assertOk();
        $this->assertSame(30, substr_count($firstPage->getContent(), 'font-semibold text-indigo-700 hover:underline'));
        $this->assertSame(30, substr_count($secondPage->getContent(), 'font-semibold text-indigo-700 hover:underline'));
    }

    public function test_admin_can_create_and_delete_unused_role(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $this->actingAs($admin)->post(route('roles.store'), ['name' => 'Temporary Role'])->assertRedirect(route('roles.index'));
        $role = Role::where('name', 'Temporary Role')->firstOrFail();
        $this->actingAs($admin)->delete(route('roles.destroy', $role))->assertRedirect(route('roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_reserved_role_name_cannot_be_created(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $this->actingAs($admin)->post(route('roles.store'), ['name' => 'Viewer'])->assertSessionHasErrors('name');
    }

    public function test_non_authorized_user_cannot_manage_roles_or_users(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');
        $this->actingAs($viewer)->get(route('users.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('roles.create'))->assertForbidden();
    }

    public function test_role_assigned_to_user_cannot_be_deleted(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $role = Role::create(['name' => 'In Use Role', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($admin)->from(route('roles.index'))->delete(route('roles.destroy', $role))->assertRedirect(route('roles.index'))->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_users_page_filters_by_name_email_and_role(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $target = User::factory()->create(['name' => 'Filtered Support', 'email' => 'filtered-support@example.com']);
        $target->assignRole('Customer Support');
        $other = User::factory()->create(['name' => 'Other Viewer', 'email' => 'other-viewer@example.com']);
        $other->assignRole('Viewer');

        $this->actingAs($admin)->get(route('users.index', ['search' => 'filtered-support']))->assertOk()->assertSee($target->email)->assertDontSee($other->email);
        $this->actingAs($admin)->get(route('users.index', ['role' => 'Customer Support']))->assertOk()->assertSee($target->email)->assertDontSee($other->email);
    }

    public function test_complaint_id_filter_returns_only_the_requested_complaint(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Customer Support');
        $complaint = $this->makeComplaint($user, Branch::first());
        $otherComplaint = $this->makeComplaint($user, Branch::first());

        $response = $this->actingAs($user)->get(route('complaints.index', ['complaint_id' => $complaint->id]));
        $response->assertOk()->assertSee('#'.$complaint->id)->assertDontSee('#'.$otherComplaint->id);
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

    public function test_branch_reports_uses_the_top_five_searchable_branch_picker(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        foreach (range(1, 7) as $number) {
            Branch::create(['name' => 'Report Branch '.$number, 'code' => 'RB'.$number, 'is_active' => true, 'sort_order' => $number]);
        }
        $response = $this->actingAs($admin)->get(route('reports.branches'));
        $response->assertOk();
        $this->assertSame(5, substr_count($response->getContent(), 'class="branch-option'));
        $this->actingAs($admin)->getJson(route('complaints.branches.search', ['q' => 'Report Branch']))->assertOk()->assertJsonCount(5);
    }

    public function test_branch_picker_loads_only_five_initial_rows_and_searches_by_name(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        foreach (range(1, 7) as $number) {
            Branch::create(['name' => 'Search Branch '.$number, 'code' => 'SB'.$number, 'is_active' => true, 'sort_order' => $number]);
        }
        $response = $this->actingAs($admin)->get(route('complaints.index'));
        $response->assertOk();
        $this->assertSame(5, substr_count($response->getContent(), 'class="branch-option'));

        $response = $this->actingAs($admin)->getJson(route('complaints.branches.search', ['q' => 'Search Branch']));
        $response->assertOk()->assertJsonCount(5);
    }

    public function test_complaint_filter_loads_only_ten_initial_customers(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        Customer::factory()->count(12)->create();
        $response = $this->actingAs($admin)->get(route('complaints.index'));
        $response->assertOk();
        $this->assertSame(10, substr_count($response->getContent(), 'class="customer-option'));
    }

    public function test_admin_can_open_master_data_create_form(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $this->actingAs($admin)->get(route('master.create', 'branches'))->assertOk()->assertSee('name="name"', false);
    }

    public function test_admin_can_open_user_create_and_edit_forms(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $this->actingAs($admin)->get(route('users.create'))->assertOk()->assertSee('name="name"', false);
        $user = User::factory()->create();
        $this->actingAs($admin)->get(route('users.edit', $user))->assertOk()->assertSee($user->email);
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
