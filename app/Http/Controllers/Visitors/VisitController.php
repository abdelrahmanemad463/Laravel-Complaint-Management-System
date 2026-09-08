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

    // New Visit: single-page form — pick a visit type, branch, and visit date
    public function create()
    {
        abort_unless(auth()->user()?->can('visit.create'), 403);
        $visitTypes = VisitorVisitType::where('is_active', true)
            ->orderBy('id')->get();
        $branches = Branch::where('is_active', true)->orderBy('sort_order')->orderBy('name_en')->get();
        return view('visitors.create', compact('visitTypes', 'branches'));
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
            'items.rootCause', 'items.photos', 'items.capaAction', 'items.followUps.violations',
        ]);
        $grouped = $visit->items->groupBy('section_name');
        $score = $this->score->calculate($visit);
        $rootCauses = \App\Models\VisitorRootCause::where('is_active', true)->orderBy('name')->get();
        $supportDepartments = [
            'maintenance', 'purchasing', 'training', 'human_resources', 'it',
            'central_kitchen', 'marketing', 'customer_service', 'accounting', 'senior_management',
        ];

        // For in-progress visits, compute which items have previous open
        // violations so the inspector sees the Follow-up Violation (N) button
        // and can pick one/more violations to follow up in the modal.
        $followUpCandidates = collect();
        if (!$visit->isCompleted()) {
            $followUpCandidates = app(\App\Services\Visitors\ViolationFollowUpService::class)
                ->candidateMap($visit)
                ->map(fn ($group) => $group->map(fn ($a) => [
                    'id' => $a->id,
                    'label' => '#V-'.$a->id,
                    'status' => $a->status,
                    'dueStatus' => $a->dueStatus(),
                    'dueDate' => $a->due_at?->format('Y-m-d H:i'),
                    'itemCode' => $a->visitItem?->item_code,
                    'itemTitle' => $a->visitItem?->item_title,
                    'originDate' => $a->visit?->visit_date?->format('Y-m-d') ?: $a->created_at->format('Y-m-d'),
                    'createdAt' => $a->created_at->format('Y-m-d H:i'),
                    'note' => $a->visitItem?->note,
                    'photos' => $a->photos->map(fn ($p) => route('visitors.photos.serve', $p))->values(),
                ])->values());
        }

        return view('visitors.show', compact('visit', 'grouped', 'score', 'rootCauses', 'supportDepartments', 'followUpCandidates'));
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
