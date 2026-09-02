<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VisitorChecklistItem;
use App\Models\VisitorImport;
use App\Models\VisitorImportRow;
use App\Models\VisitorSection;
use App\Models\VisitorVisitType;
use App\Services\Visitors\VisitorMasterDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class VisitorMasterDataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $support;
    private VisitorMasterDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');

        $this->support = User::factory()->create();
        $this->support->assignRole('Customer Support');

        $this->service = app(VisitorMasterDataService::class);
    }

    private function typeCode(): string
    {
        return VisitorVisitType::where('is_active', true)->firstOrFail()->code;
    }

    private function validRow(array $overrides = []): array
    {
        return array_merge([
            'code' => 'ZZ-001',
            'inspection_type' => $this->typeCode(),
            'section' => 'Test Section',
            'note' => 'A valid checklist note',
            'severity' => 'Major',
            'immediate_action' => 'Immediate action text',
            'corrective_action' => 'Corrective action text',
            'responsible' => 'Branch Manager',
            'period' => '24 hours',
            'preventive_action' => 'Preventive action text',
            'deduction_score' => 3,
        ], $overrides);
    }

    // ---- Permissions ----

    public function test_support_role_is_denied_master_data_page(): void
    {
        $this->admin->can('visit.master.view');
        $this->assertFalse($this->support->can('visit.master.view'));
        $this->assertFalse($this->support->can('visit.master.import'));
        $this->assertFalse($this->support->can('visit.master.export'));

        $this->actingAs($this->support)->get(route('visitors.master-data'))->assertForbidden();
    }

    public function test_admin_can_view_master_data_page(): void
    {
        $this->assertTrue($this->admin->can('visit.master.view'));
        $response = $this->actingAs($this->admin)->get(route('visitors.master-data'));
        $response->assertOk();
    }

    public function test_support_role_is_denied_download_and_import(): void
    {
        $this->actingAs($this->support)->get(route('visitors.master-data.template'))->assertForbidden();
        $this->actingAs($this->support)->get(route('visitors.master-data.download'))->assertForbidden();
        $this->actingAs($this->support)->post(route('visitors.master-data.import'))->assertForbidden();
    }

    public function test_template_download_returns_xlsx_file(): void
    {
        $response = $this->actingAs($this->admin)->get(route('visitors.master-data.template'));
        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats', $response->headers->get('content-type'));
    }

    public function test_current_data_download_returns_xlsx_file(): void
    {
        $response = $this->actingAs($this->admin)->get(route('visitors.master-data.download'));
        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats', $response->headers->get('content-type'));
    }

    // ---- Upload validation (service level) ----

    public function test_upload_rejects_oversized_file(): void
    {
        $file = UploadedFile::fake()->create('big.xlsx', 51 * 1024);
        $result = $this->service->validateUpload($file);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('50 MB', implode(' ', $result['errors']));
    }

    public function test_upload_rejects_wrong_extension(): void
    {
        $file = UploadedFile::fake()->create('sheet.txt', 10);
        $result = $this->service->validateUpload($file);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('xlsx', implode(' ', $result['errors']));
    }

    public function test_upload_accepts_excel_fake(): void
    {
        $file = UploadedFile::fake()->create('sheet.xlsx', 10);
        $result = $this->service->validateUpload($file);
        $this->assertTrue($result['ok']);
    }

    // ---- Validation ----

    public function test_validate_rows_flags_invalid_type_and_duplicate(): void
    {
        $type = $this->typeCode();
        $validation = $this->service->validateRows([
            $this->validRow(),
            $this->validRow(), // duplicate (type, code)
            $this->validRow(['inspection_type' => 'not_a_real_type']),
            $this->validRow(['deduction_score' => -5]),
        ]);

        $this->assertCount(1, $validation['valid']);
        $this->assertCount(3, $validation['invalid']);

        $invalidMessages = array_merge(...array_column($validation['invalid'], 'errors'));
        $this->assertContains('Duplicate code for this inspection_type within the file.', $invalidMessages);
        $this->assertContains('Invalid inspection_type.', $invalidMessages);
        $this->assertContains('Deduction score must be a number.', $invalidMessages);
    }

    // ---- Import lifecycle ----

    public function test_preview_persists_rows_without_mutating_master_data(): void
    {
        $rows = [
            $this->validRow(['code' => 'AA-001']),
            $this->validRow(['code' => 'AA-002']),
        ];
        $validation = $this->service->validateRows($rows);
        $before = VisitorChecklistItem::count();

        $import = $this->service->storeImport(
            UploadedFile::fake()->create('data.xlsx', 10),
            $this->admin->id,
            $rows,
            $validation
        );

        $this->assertSame(2, $import->validRows()->count());
        $this->assertTrue($import->isReady());
        $this->assertSame($before, VisitorChecklistItem::count(), 'preview must not mutate master data');
        $this->assertSame(2, $import->total_rows);
    }

    public function test_confirm_creates_new_items_and_sets_status_imported(): void
    {
        $rows = [
            $this->validRow(['code' => 'AA-001']),
            $this->validRow(['code' => 'AA-002', 'severity' => 'Critical', 'deduction_score' => 5]),
        ];
        $validation = $this->service->validateRows($rows);
        $import = $this->service->storeImport(UploadedFile::fake()->create('data.xlsx', 10), $this->admin->id, $rows, $validation);

        $result = $this->service->confirm($import);

        $this->assertSame(['created' => 2, 'updated' => 0], $result);
        $this->assertTrue($import->isImported());
        $this->assertSame(2, $import->created_records);

        $item = VisitorChecklistItem::where('code', 'AA-001')->firstOrFail();
        $this->assertSame('AA-001', $item->code);
        $this->assertSame('major', $item->severity);
        $this->assertSame(3, $item->deduction_score);

        $critical = VisitorChecklistItem::where('code', 'AA-002')->firstOrFail();
        $this->assertSame('critical', $critical->severity);
        $this->assertTrue($critical->photo_required);
        $this->assertSame(5, $critical->deduction_score);
    }

    public function test_confirm_updates_existing_item_keyed_by_type_and_code(): void
    {
        $type = VisitorVisitType::where('is_active', true)->firstOrFail();
        $section = VisitorSection::firstOrCreate(['name' => 'Existing Section'], ['is_active' => true, 'sort_order' => 0]);

        VisitorChecklistItem::create([
            'visit_type_id' => $type->id,
            'section_id' => $section->id,
            'code' => 'UP-900',
            'title' => 'Old title',
            'severity' => 'minor',
            'deduction_score' => 1,
            'photo_required' => false,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $rows = [$this->validRow(['code' => 'UP-900', 'note' => 'New title', 'severity' => 'Critical', 'deduction_score' => 7])];
        $validation = $this->service->validateRows($rows);
        $import = $this->service->storeImport(UploadedFile::fake()->create('data.xlsx', 10), $this->admin->id, $rows, $validation);

        $result = $this->service->confirm($import);

        $this->assertSame(['created' => 0, 'updated' => 1], $result);
        $this->assertSame(0, $import->created_records);
        $this->assertSame(1, $import->updated_records);

        $this->assertDatabaseHas('visitors_checklist_items', [
            'code' => 'UP-900',
            'title' => 'New title',
            'severity' => 'critical',
        ]);
        $this->assertSame(1, VisitorChecklistItem::where('code', 'UP-900')->count());
    }

    public function test_confirm_rejects_import_with_invalid_rows(): void
    {
        $rows = [$this->validRow(), $this->validRow(['inspection_type' => 'bogus_type'])];
        $validation = $this->service->validateRows($rows);
        $import = $this->service->storeImport(UploadedFile::fake()->create('data.xlsx', 10), $this->admin->id, $rows, $validation);

        $before = VisitorChecklistItem::count();
        $this->expectException(\RuntimeException::class);
        $this->service->confirm($import);

        $this->assertSame($before, VisitorChecklistItem::count());
    }

    public function test_cancel_marks_import_cancelled_without_mutation(): void
    {
        $rows = [$this->validRow(['code' => 'CC-001'])];
        $validation = $this->service->validateRows($rows);
        $import = $this->service->storeImport(UploadedFile::fake()->create('data.xlsx', 10), $this->admin->id, $rows, $validation);

        $before = VisitorChecklistItem::count();
        $this->service->cancel($import);

        $this->assertSame('cancelled', $import->fresh()->status);
        $this->assertNull(VisitorChecklistItem::where('code', 'CC-001')->first());
        $this->assertSame($before, VisitorChecklistItem::count());
    }

    public function test_invalid_row_errors_are_stored_and_can_be_read(): void
    {
        $rows = [$this->validRow(['code' => ''])];
        $validation = $this->service->validateRows($rows);
        $import = $this->service->storeImport(UploadedFile::fake()->create('data.xlsx', 10), $this->admin->id, $rows, $validation);

        $row = $import->invalidRows()->firstOrFail();
        $this->assertContains('code is required.', $row->errorsList());
    }
}
