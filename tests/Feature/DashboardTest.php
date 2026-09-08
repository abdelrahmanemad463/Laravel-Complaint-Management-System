<?php

namespace Tests\Feature;

use App\Models\{Branch, Complaint, ComplaintCategory, ComplaintSource, ComplaintStatus, ComplaintType, Customer, Priority, Service, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_authenticated_dashboard_renders_real_analytics_and_chart_payload(): void
    {
        $complaint = $this->createComplaint([
            'short_description' => 'Dashboard fixture complaint',
            'description' => 'A complaint used to verify dashboard analytics.',
            'complaint_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard', [
            'date_from' => now()->subDays(2)->toDateString(),
            'date_to' => now()->toDateString(),
            'branch_ids' => [$complaint->branch_id],
        ]));

        $response->assertOk()
            ->assertSee('Dashboard filters')
            ->assertSee('Total complaints')
            ->assertSee('Complaints over time')
            ->assertSee('id="trend-chart"', false)
            ->assertSee('dashboard-chart-data', false);
    }

    public function test_dashboard_bar_charts_suppress_undefined_dataset_legends(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertNotFalse($script);
        $this->assertStringContainsString(
            'legend: { ...commonOptions.plugins.legend, display: isDoughnut }',
            $script
        );
    }

    public function test_dashboard_filters_limit_real_data_and_preserve_actionable_branch_link(): void
    {
        $branch = Branch::first();
        $complaint = $this->createComplaint([
            'branch_id' => $branch->id,
            'short_description' => 'Filtered dashboard complaint',
            'complaint_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard', [
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'branch_ids' => [$branch->id],
            'status_id' => $complaint->status_id,
        ]));

        $response->assertOk()
            ->assertSee($branch->localized_name)
            ->assertSee('branch_ids%5B0%5D='.$branch->id, false)
            ->assertSee('#'.$complaint->id);
    }

    private function createComplaint(array $overrides = []): Complaint
    {
        $customer = Customer::first();

        return Complaint::create(array_merge([
            'customer_id' => $customer->id,
            'branch_id' => Branch::first()->id,
            'service_id' => Service::first()->id,
            'source_id' => ComplaintSource::first()->id,
            'category_id' => ComplaintCategory::first()->id,
            'type_id' => ComplaintType::first()->id,
            'priority_id' => Priority::where('name_en', 'High')->first()->id,
            'status_id' => ComplaintStatus::where('name_en', 'Pending')->first()->id,
            'short_description' => 'Dashboard test complaint',
            'description' => 'Dashboard test description.',
            'complaint_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ], $overrides));
    }
}
