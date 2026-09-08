<?php

namespace Tests\Feature;

use App\Exports\ComplaintsExport;
use App\Models\{ActivityLog, Branch, Complaint, ComplaintCategory, ComplaintSource, ComplaintStatus, ComplaintStatusHistory, ComplaintType, Customer, Priority, Service, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_export_query_respects_branch_filter(): void
    {
        $user = User::where('email', 'admin@example.com')->first();
        $first = Branch::first();
        $second = Branch::skip(1)->first();
        $this->makeComplaint($user, $first);
        $excluded = $this->makeComplaint($user, $second);
        $export = new ComplaintsExport(['branch_ids' => [$first->id]]);
        $this->assertTrue($export->query()->whereKey($excluded->id)->doesntExist());
        $this->assertSame(19, count($export->headings()));
    }

    public function test_export_query_respects_description_filter(): void
    {
        $user = User::where('email', 'admin@example.com')->first();
        $branch = Branch::first();
        $match = $this->makeComplaint($user, $branch);
        $match->update(['short_description' => 'Export description token']);
        $excluded = $this->makeComplaint($user, $branch);

        $export = new ComplaintsExport(['description' => 'Export description token']);

        $this->assertTrue($export->query()->whereKey($match->id)->exists());
        $this->assertTrue($export->query()->whereKey($excluded->id)->doesntExist());
    }

    public function test_export_contains_short_description_and_combined_timeline(): void
    {
        app()->setLocale('en');
        $user = User::where('email', 'admin@example.com')->first();
        $branch = Branch::first();
        $complaint = $this->makeComplaint($user, $branch);
        $pending = ComplaintStatus::where('name_en', 'Pending')->first();
        $solved = ComplaintStatus::where('name_en', 'Solved')->first();

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'complaint.created',
            'subject_type' => $complaint->getMorphClass(),
            'subject_id' => $complaint->id,
            'description' => __('complaints.created_log'),
            'created_at' => now()->subMinutes(2),
        ]);
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'complaint.updated',
            'subject_type' => $complaint->getMorphClass(),
            'subject_id' => $complaint->id,
            'description' => __('complaints.updated_log'),
            'created_at' => now()->subMinute(),
        ]);
        ComplaintStatusHistory::create([
            'complaint_id' => $complaint->id,
            'from_status_id' => $pending->id,
            'to_status_id' => $solved->id,
            'reason' => 'Resolved by support',
            'changed_by' => $user->id,
            'changed_at' => now(),
        ]);

        $export = new ComplaintsExport([]);
        $complaint->update(['serial_number' => 'SN-2026-001', 'price' => 1234.50]);
        $row = $export->map($export->query()->whereKey($complaint->id)->firstOrFail());

        $this->assertSame('Short Description', $export->headings()[13]);
        $this->assertSame('Timeline', $export->headings()[18]);
        $this->assertSame('SN-2026-001', $row[11]);
        $this->assertSame(1234.5, $row[12]);
        $this->assertSame('Export complaint', $row[13]);
        $this->assertStringContainsString('Complaint created.', $row[18]);
        $this->assertStringContainsString('Complaint updated.', $row[18]);
        $this->assertStringContainsString('Pending → Solved', $row[18]);
        $this->assertStringContainsString('Resolved by support', $row[18]);
    }

    public function test_export_headings_follow_locale(): void
    {
        app()->setLocale('en');
        $english = (new ComplaintsExport([]))->headings();
        app()->setLocale('ar');
        $arabic = (new ComplaintsExport([]))->headings();
        $this->assertSame('Complaint ID', $english[0]);
        $this->assertSame('رقم الشكوى', $arabic[0]);
        $this->assertSame('Serial Number', $english[11]);
        $this->assertSame('Price', $english[12]);
        $this->assertSame('رقم السيريال', $arabic[11]);
        $this->assertSame('السعر', $arabic[12]);
        $this->assertSame('الوصف المختصر', $arabic[13]);
        $this->assertSame('الخط الزمني', $arabic[18]);
    }

    private function makeComplaint(User $user, Branch $branch): Complaint
    {
        return Complaint::create(['customer_id'=>Customer::factory()->create()->id,'branch_id'=>$branch->id,'service_id'=>Service::first()->id,'source_id'=>ComplaintSource::first()->id,'category_id'=>ComplaintCategory::first()->id,'type_id'=>ComplaintType::first()->id,'priority_id'=>Priority::first()->id,'status_id'=>ComplaintStatus::first()->id,'short_description'=>'Export complaint','description'=>'Export details','complaint_date'=>now()->toDateString(),'created_by'=>$user->id]);
    }
}
