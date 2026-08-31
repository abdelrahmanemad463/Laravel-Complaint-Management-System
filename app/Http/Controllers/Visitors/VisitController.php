<?php

namespace App\Http\Controllers\Visitors;

use App\Http\Controllers\Controller;
use App\Http\Requests\Visitors\StartVisitRequest;
use App\Models\Branch;
use App\Models\VisitorVisit;
use App\Models\VisitorVisitType;
use App\Services\Visitors\VisitScoreService;
use App\Services\Visitors\VisitService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class VisitController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private VisitService $service,
        private VisitScoreService $score,
    ) {}

    // Quality Visits landing page
    public function home()
    {
        abort_unless(auth()->user()?->can('visit.view'), 403);
        return view('visitors.home');
    }

    // New Visit: pick a visit type
    public function create()
    {
        abort_unless(auth()->user()?->can('visit.create'), 403);
        $visitTypes = VisitorVisitType::where('is_active', true)
            ->orderBy('id')->get();
        return view('visitors.create', compact('visitTypes'));
    }

    // New Visit step 2: choose branch + date (inspector is the auth user)
    public function setup(VisitorVisitType $visitType)
    {
        abort_unless(auth()->user()?->can('visit.create'), 403);
        $branches = Branch::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        return view('visitors.setup', compact('visitType', 'branches'));
    }

    public function store(StartVisitRequest $request)
    {
        $data = $request->validated();
        $visit = $this->service->start($data['visit_type_id'], $data['branch_id'], $data['visit_date']);
        return redirect()->route('visitors.show', $visit)
            ->with('success', __('visitors.visit_started'));
    }

    // Open Visits: only the authenticated user's in-progress visits
    public function open()
    {
        abort_unless(auth()->user()?->can('visit.view'), 403);
        $visits = VisitorVisit::with(['visitType', 'branch', 'items'])
            ->ownedBy(auth()->id())
            ->inProgress()
            ->latest('updated_at')
            ->get();
        $visits->each(fn ($visit) => $visit->setAttribute('reviewed_count', $visit->items->filter->isReviewed()->count()));
        return view('visitors.open', compact('visits'));
    }

    // Inspection page
    public function show(VisitorVisit $visit)
    {
        $this->authorize('view', $visit);
        $visit->load([
            'visitType', 'branch', 'inspector',
            'items.rootCause', 'items.photos', 'items.capaAction',
        ]);
        $grouped = $visit->items->groupBy('section_name');
        $score = $this->score->calculate($visit);
        $rootCauses = \App\Models\VisitorRootCause::where('is_active', true)->orderBy('name')->get();
        $supportDepartments = [
            'maintenance', 'purchasing', 'training', 'human_resources', 'it',
            'central_kitchen', 'marketing', 'customer_service', 'accounting', 'senior_management',
        ];
        return view('visitors.show', compact('visit', 'grouped', 'score', 'rootCauses', 'supportDepartments'));
    }

    public function submit(VisitorVisit $visit)
    {
        $this->authorize('submit', $visit);
        try {
            $this->service->submit($visit);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return redirect()->route('visitors.home')->with('success', __('visitors.visit_submitted'));
    }
}
