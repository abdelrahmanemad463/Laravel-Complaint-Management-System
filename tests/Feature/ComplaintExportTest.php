<?php

namespace Tests\Feature;

use App\Exports\ComplaintsExport;
use App\Models\{Branch, Complaint, ComplaintCategory, ComplaintSource, ComplaintStatus, ComplaintType, Customer, Priority, Service, User};
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
        $this->assertSame(15, count($export->headings()));
    }

    public function test_export_headings_follow_locale(): void
    {
        app()->setLocale('en');
        $english = (new ComplaintsExport([]))->headings();
        app()->setLocale('ar');
        $arabic = (new ComplaintsExport([]))->headings();
        $this->assertSame('Complaint ID', $english[0]);
        $this->assertSame('رقم الشكوى', $arabic[0]);
    }

    private function makeComplaint(User $user, Branch $branch): Complaint
    {
        return Complaint::create(['customer_id'=>Customer::factory()->create()->id,'branch_id'=>$branch->id,'service_id'=>Service::first()->id,'source_id'=>ComplaintSource::first()->id,'category_id'=>ComplaintCategory::first()->id,'type_id'=>ComplaintType::first()->id,'priority_id'=>Priority::first()->id,'status_id'=>ComplaintStatus::first()->id,'short_description'=>'Export complaint','description'=>'Export details','complaint_date'=>now()->toDateString(),'created_by'=>$user->id]);
    }
}
