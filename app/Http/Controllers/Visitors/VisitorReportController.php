<?php

namespace App\Http\Controllers\Visitors;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Models\VisitorRootCause;
use App\Models\VisitorSection;
use App\Models\VisitorVisit;
use App\Models\VisitorVisitType;
use App\Services\Visitors\VisitorPdfService;
use App\Services\Visitors\VisitorReportDashboardService;
use App\Services\Visitors\VisitorReportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VisitorReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private VisitorReportService $reports,
        private VisitorReportDashboardService $dashboard,
        private VisitorPdfService $pdf,
    ) {}

    private function abortUnlessReportsAccess(): void
    {
        if (Gate::denies('visitors.reports')) {
            abort(403);
        }
    }

    private function abortUnlessVisitorsDashboardAccess(): void
    {
        if (Gate::denies('dashboard.visitors')) {
            abort(403);
        }
    }

    /**
     * Report list with filters.
     */
    public function index(Request $request)
    {
        $this->abortUnlessReportsAccess();

        $filters = $request->only(['branch_id', 'visit_type_id', 'inspector_id', 'date_from', 'date_to', 'colors', 'due_status']);
        $filters['colors'] = $request->input('colors', []);

        $visits = $this->reports->listReports($filters);

        return view('visitors.reports.index', [
            'visits' => $visits,
            'filters' => $filters,
            'branches' => Branch::orderBy('sort_order')->orderBy('name')->get(),
            'visitTypes' => VisitorVisitType::orderBy('id')->get(),
            'inspectors' => User::whereIn('id', VisitorVisit::completed()->select('inspector_id')->distinct())->orderBy('name')->get([ 'id', 'name' ]),
        ]);
    }

    /**
     * Individual web report (all sections).
     */
    public function show(VisitorVisit $visit)
    {
        $this->authorize('viewReport', $visit);

        $report = $this->reports->build($visit);

        return view('visitors.reports.show', [
            'report' => $report,
            'generatedAt' => now(),
            'company' => __('visitors.report_company_name'),
        ]);
    }

    /**
     * Analytics dashboard across many completed visits.
     */
    public function dashboard(Request $request)
    {
        $this->abortUnlessVisitorsDashboardAccess();

        $data = $this->dashboard->build($request->query());

        return view('visitors.reports.dashboard', [
            'data' => $data,
            'branches' => Branch::orderBy('sort_order')->orderBy('name')->get(),
            'visitTypes' => VisitorVisitType::orderBy('id')->get(),
            'severityOptions' => ['critical', 'major', 'minor'],
            'sections' => VisitorSection::orderBy('sort_order')->orderBy('name')->get(),
            'inspectors' => User::whereIn('id', VisitorVisit::completed()->select('inspector_id')->distinct())->orderBy('name')->get([ 'id', 'name' ]),
            'rootCauses' => VisitorRootCause::orderBy('name')->get(),
        ]);
    }

    /**
     * Download the PDF of a single report.
     */
    public function pdf(VisitorVisit $visit)
    {
        $this->authorize('viewReport', $visit);

        $filename = 'inspection-report-'.$visit->id.'-'.$visit->visit_date?->format('Y-m-d').'.pdf';

        return $this->pdf->download($visit, $filename);
    }
}
