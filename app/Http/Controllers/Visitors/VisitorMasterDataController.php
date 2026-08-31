<?php

namespace App\Http\Controllers\Visitors;

use App\Exports\Visitors\VisitorCurrentMasterDataExport;
use App\Exports\Visitors\VisitorMasterTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\VisitorChecklistItem;
use App\Models\VisitorImport;
use App\Models\VisitorSection;
use App\Models\VisitorVisitType;
use App\Services\Visitors\VisitorMasterDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitorMasterDataController extends Controller
{
    public function __construct(private VisitorMasterDataService $service) {}

    /** Master data page: stats, current data, filters, import history, upload. */
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->can('visit.master.view'), 403);

        $filters = [
            'visit_type_id' => $request->integer('visit_type_id') ?: null,
            'section_id' => $request->integer('section_id') ?: null,
            'severity' => $request->string('severity')->trim()->toString() ?: null,
            'q' => $request->string('q')->trim()->toString() ?: null,
        ];

        $items = $this->itemsQuery($filters)->paginate(20)->withQueryString();

        $stats = [
            'total' => VisitorChecklistItem::where('is_active', true)->count(),
            'daily' => $this->countByType('daily'),
            'monthly' => $this->countByType('monthly'),
            'safety' => $this->countByType('occupational_safety'),
            'sections' => VisitorSection::where('is_active', true)->count(),
            'last_import' => VisitorImport::where('status', 'imported')->latest('created_at')->value('created_at'),
        ];

        $types = VisitorVisitType::where('is_active', true)->orderBy('name')->get();
        $sections = VisitorSection::where('is_active', true)->orderBy('name')->get();
        $history = VisitorImport::with('user')->latest('created_at')->paginate(10, ['*'], 'history_page');

        return view('visitors.master-data.index', compact('items', 'stats', 'types', 'sections', 'filters', 'history'));
    }

    /** Download the official empty Excel template. */
    public function template()
    {
        abort_unless(auth()->user()?->can('visit.master.view'), 403);

        return Excel::download(new VisitorMasterTemplateExport(), 'quality_visits_master_data_template.xlsx');
    }

    /** Download an Excel template with clearly-labelled example rows. */
    public function templateExample()
    {
        abort_unless(auth()->user()?->can('visit.master.view'), 403);

        return Excel::download(new VisitorMasterTemplateExport(), 'quality_visits_master_data_example.xlsx');
    }

    /** Download the current master data in the importable format. */
    public function download(Request $request)
    {
        abort_unless(auth()->user()?->can('visit.master.export'), 403);

        $filters = [
            'visit_type_id' => $request->integer('visit_type_id') ?: null,
            'section_id' => $request->integer('section_id') ?: null,
        ];

        return Excel::download(new VisitorCurrentMasterDataExport($filters), 'quality_visits_master_data.xlsx');
    }

    /** Handle an Excel upload: validate, chunk-read, preview (no DB mutation). */
    public function import(Request $request)
    {
        abort_unless(auth()->user()?->can('visit.master.import'), 403);

        $request->validate(['file' => 'required|file']);

        $file = $request->file('file');
        $check = $this->service->validateUpload($file);
        if (!$check['ok']) {
            return back()->withErrors(['file' => $check['errors']])->withInput();
        }

        try {
            $path = $file->store('master-imports', 'local');
            $rows = $this->service->readRows(Storage::disk('local')->path($path), strtolower($file->getClientOriginalExtension() ?: ''));
            $validation = $this->service->validateRows($rows);
        } catch (\Throwable $e) {
            return back()->with('error', 'Unable to read the Excel file. Please check the column headers and format.')
                ->withInput();
        }

        $import = $this->service->storeImport($file, auth()->id(), $rows, $validation);

        return redirect()->route('visitors.master-data.import.preview', $import)
            ->with('success', __('visitors.master_uploaded'));
    }

    /** Show the preview + confirmation step for a staged import. */
    public function preview(VisitorImport $import)
    {
        abort_unless(auth()->user()?->can('visit.master.import'), 403);
        abort_unless($import->isReady(), 404);

        $import->load('rows');
        $validRows = $import->rows->where('valid', true);
        $invalidRows = $import->rows->where('valid', false);

        return view('visitors.master-data.preview', compact('import', 'validRows', 'invalidRows'));
    }

    /** Confirm and import the staged data (create/update, no destructive delete). */
    public function confirm(VisitorImport $import)
    {
        abort_unless(auth()->user()?->can('visit.master.import'), 403);
        abort_unless($import->user_id === auth()->id() || auth()->user()->can('visit.manage'), 403);

        try {
            $result = $this->service->confirm($import);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('visitors.master-data')
            ->with('success', __('visitors.master_imported', ['created' => $result['created'], 'updated' => $result['updated']]));
    }

    /** Reject/cancel a staged import before confirmation. */
    public function cancel(VisitorImport $import)
    {
        abort_unless(auth()->user()?->can('visit.master.import'), 403);
        abort_unless($import->user_id === auth()->id() || auth()->user()->can('visit.manage'), 403);

        $this->service->cancel($import);

        return redirect()->route('visitors.master-data')->with('success', __('visitors.master_cancelled'));
    }

    private function itemsQuery(array $filters)
    {
        $q = VisitorChecklistItem::query()
            ->with(['section', 'visitType'])
            ->when($filters['visit_type_id'], fn ($q, $v) => $q->where('visit_type_id', $v))
            ->when($filters['section_id'], fn ($q, $v) => $q->where('section_id', $v))
            ->when($filters['severity'], fn ($q, $v) => $q->where('severity', $v))
            ->when($filters['q'], function ($q, $v) {
                $q->where(function ($q) use ($v) {
                    $q->where('code', 'like', "%{$v}%")
                        ->orWhere('title', 'like', "%{$v}%");
                });
            })
            ->orderBy('visit_type_id')
            ->orderBy('sort_order')
            ->orderBy('id');

        return $q;
    }

    private function countByType(string $code): int
    {
        return VisitorChecklistItem::where('is_active', true)
            ->whereHas('visitType', fn ($q) => $q->where('code', $code))
            ->count();
    }
}
