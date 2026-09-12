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

    public function test_description_filter_matches_short_or_full_description(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Customer Support');
        $branch = Branch::first();

        $shortMatch = $this->makeComplaint($user, $branch);
        $shortMatch->update(['short_description' => 'Short description search token', 'description' => 'Generic complaint details']);
        $fullMatch = $this->makeComplaint($user, $branch);
        $fullMatch->update(['short_description' => 'Generic complaint summary', 'description' => 'Full description search token']);
        $unrelated = $this->makeComplaint($user, $branch);
        $unrelated->update(['short_description' => 'Unrelated summary', 'description' => 'Unrelated complaint details']);

        $shortResponse = $this->actingAs($user)->get(route('complaints.index', ['description' => 'Short description search token']));
        $shortResponse->assertOk()->assertSee('#'.$shortMatch->id)->assertDontSee('#'.$fullMatch->id)->assertDontSee('#'.$unrelated->id);

        $fullResponse = $this->actingAs($user)->get(route('complaints.index', ['description' => 'Full description search token']));
        $fullResponse->assertOk()->assertSee('#'.$fullMatch->id)->assertDontSee('#'.$shortMatch->id)->assertDontSee('#'.$unrelated->id);
    }

    public function test_branch_reports_paginate_thirty_grouped_rows_and_preserve_filters(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $branch = Branch::first();
        $dateFrom = now()->subDays(31)->toDateString();
        $dateTo = now()->toDateString();

        foreach (range(1, 31) as $daysAgo) {
            $this->makeComplaint($admin, $branch, now()->subDays($daysAgo)->toDateString());
        }

        $filters = ['date_from' => $dateFrom, 'date_to' => $dateTo, 'branch_ids' => [$branch->id]];
        $firstPage = $this->actingAs($admin)->get(route('reports.branches', $filters + ['page' => 1]));
        $firstPage->assertOk()->assertSee('page=2', false)->assertSee('date_from='.$dateFrom, false);
        $this->assertStringContainsString('branch_ids%5B0%5D='.$branch->id, $firstPage->getContent());
        preg_match('/<tbody>(.*?)<\/tbody>/s', $firstPage->getContent(), $firstTableBody);
        $this->assertSame(30, substr_count($firstTableBody[1] ?? '', '<tr>'));

        $secondPage = $this->actingAs($admin)->get(route('reports.branches', $filters + ['page' => 2]));
        $secondPage->assertOk()->assertSee(now()->subDays(31)->toDateString(), false);
        preg_match('/<tbody>(.*?)<\/tbody>/s', $secondPage->getContent(), $secondTableBody);
        $this->assertSame(1, substr_count($secondTableBody[1] ?? '', '<tr>'));
    }

    public function test_branch_report_results_count_is_localized(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $filters = ['date_from' => '2099-01-01', 'date_to' => '2099-01-31'];

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('reports.branches', $filters))
            ->assertOk()
            ->assertSee('0 results');

        $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('reports.branches', $filters))
            ->assertOk()
            ->assertSee('عدد النتائج: 0');
    }

    public function test_branch_reports_renders_all_branches_and_search_returns_matches(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        foreach (range(1, 7) as $number) {
            Branch::create(['name_en' => 'Report Branch '.$number, 'name_ar' => 'فرع التقرير '.$number, 'code' => 'RB'.$number, 'is_active' => true, 'sort_order' => $number]);
        }
        $response = $this->actingAs($admin)->get(route('reports.branches'));
        $response->assertOk();
        $this->assertSame(10, substr_count($response->getContent(), 'class="branch-option'));
        $this->actingAs($admin)->getJson(route('complaints.branches.search', ['q' => 'Report Branch']))->assertOk()->assertJsonCount(7);
    }

    public function test_branch_picker_loads_all_branches_and_filters_by_name(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        foreach (range(1, 7) as $number) {
            Branch::create(['name_en' => 'Search Branch '.$number, 'name_ar' => 'فرع البحث '.$number, 'code' => 'SB'.$number, 'is_active' => true, 'sort_order' => $number]);
        }
        $response = $this->actingAs($admin)->get(route('complaints.index'));
        $response->assertOk();
        $this->assertSame(10, substr_count($response->getContent(), 'class="branch-option'));

        $response = $this->actingAs($admin)->getJson(route('complaints.branches.search', ['q' => 'Search Branch']));
        $response->assertOk()->assertJsonCount(7);
    }

    public function test_complaint_filter_loads_only_ten_initial_customers(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        Customer::factory()->count(12)->create();
        $response = $this->actingAs($admin)->get(route('complaints.index'));
        $response->assertOk();
        $this->assertSame(10, substr_count($response->getContent(), 'class="customer-option'));
    }

    public function test_complaint_filters_render_closed_customer_and_branch_dropdowns(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $content = $this->actingAs($admin)->get(route('complaints.index'))->assertOk()->getContent();

        $this->assertSame(2, substr_count($content, 'data-picker-trigger'));
        $this->assertSame(2, substr_count($content, 'data-filter-dropdown'));
        $this->assertStringContainsString('name="customer_id"', $content);
        $this->assertStringContainsString('aria-multiselectable="true"', $content);
        $this->assertStringContainsString('id="customer-results" class="dropdown-panel absolute start-0 z-30 mt-2 hidden', $content);
        $this->assertStringContainsString('id="branch-results" class="dropdown-panel absolute start-0 z-30 mt-2 hidden', $content);
        $this->assertSame(2, substr_count($content, 'data-picker-clear'));
    }

    public function test_dashboard_report_and_complaint_create_use_closed_picker_controls(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();

        $dashboard = $this->actingAs($admin)->get(route('dashboard'))->assertOk()->getContent();
        $report = $this->actingAs($admin)->get(route('reports.branches'))->assertOk()->getContent();
        $create = $this->actingAs($admin)->get(route('complaints.create'))->assertOk()->getContent();

        $this->assertStringContainsString('data-branch-picker data-filter-dropdown', $dashboard);
        $this->assertStringContainsString('id="branch-results" class="dropdown-panel absolute start-0 z-30 mt-2 hidden', $dashboard);
        $this->assertStringContainsString('data-branch-picker data-filter-dropdown', $report);
        $this->assertStringContainsString('id="branch-results" class="dropdown-panel absolute start-0 z-30 mt-2 hidden', $report);
        $this->assertStringContainsString('data-customer-picker data-filter-dropdown', $create);
        $this->assertStringContainsString('id="customer-results" class="dropdown-panel absolute start-4 end-4 top-full z-30 mt-2 hidden', $create);
        $this->assertStringContainsString('name="customer_id"', $create);
    }

    public function test_admin_can_open_master_data_create_form(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $this->actingAs($admin)->get(route('master.create', 'branches'))->assertOk()->assertSee('name="name_en"', false)->assertSee('name="name_ar"', false);
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

    public function test_navbar_hides_customers_and_complaints_without_permissions(): void
    {
        $role = Role::create(['name' => 'Nav Test', 'guard_name' => 'web']);
        $role->syncPermissions(['dashboard.view']);
        $user = User::factory()->create();
        $user->assignRole($role->name);

        $content = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('href="'.route('customers.index').'"', $content);
        $this->assertStringNotContainsString('href="'.route('complaints.index').'"', $content);
        $this->assertStringNotContainsString('<details class="group relative" data-nav-dropdown>', $content);
    }

    public function test_navbar_shows_customers_and_complaints_with_permissions(): void
    {
        $support = User::where('email', 'admin@example.com')->first();

        $content = $this->actingAs($support)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('customers.index').'"', $content);
        $this->assertStringContainsString('href="'.route('complaints.index').'"', $content);
        $this->assertStringContainsString('<details class="group relative" data-nav-dropdown>', $content);
    }

    public function test_dashboard_hides_new_complaint_button_without_permission(): void
    {
        $role = Role::create(['name' => 'Nav Test 2', 'guard_name' => 'web']);
        $role->syncPermissions(['dashboard.view']);
        $user = User::factory()->create();
        $user->assignRole($role->name);

        $content = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('href="'.route('complaints.create').'"', $content);
    }

    public function test_dashboard_shows_new_complaint_button_with_permission(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();

        $content = $this->actingAs($admin)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('complaints.create').'"', $content);
    }

    private function makeComplaint(User $user, Branch $branch, ?string $date = null): Complaint
    {
        return Complaint::create(['customer_id'=>Customer::factory()->create()->id,'branch_id'=>$branch->id,'service_id'=>Service::first()->id,'source_id'=>ComplaintSource::first()->id,'category_id'=>ComplaintCategory::first()->id,'type_id'=>ComplaintType::first()->id,'priority_id'=>Priority::first()->id,'status_id'=>ComplaintStatus::first()->id,'short_description'=>'Example complaint','description'=>'Example details','complaint_date'=>$date ?? now()->toDateString(),'created_by'=>$user->id]);
    }
}
