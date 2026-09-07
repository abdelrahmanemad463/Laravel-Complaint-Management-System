<?php

namespace App\Http\Controllers\Visitors;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\VisitorCapaAction;
use App\Services\Visitors\CapaService;
use App\Services\Visitors\VisitorPhotoService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VisitorViolationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private CapaService $capa,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('visit.view'), 403);

        $query = VisitorCapaAction::query()
            ->with(['visitItem', 'visit', 'visit.branch', 'responsible'])
            ->whereHas('visit', fn ($q) => $q->where('status', 'completed'));

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('visit', fn ($q) => $q->where('branch_id', $request->input('branch_id')));
        }

        if ($request->filled('due_status')) {
            $dueStatus = $request->input('due_status');
            if ($dueStatus === 'overdue') {
                $query->whereIn('status', ['open', 'in_progress'])
                    ->whereNotNull('due_at')
                    ->where('due_at', '<', now());
            } elseif ($dueStatus === 'immediate') {
                $query->whereIn('status', ['open', 'in_progress'])
                    ->whereNull('due_at');
            } elseif ($dueStatus === 'closed') {
                $query->where('status', 'closed');
            }
        }

        $violations = $query->latest('created_at')->paginate(15)->withQueryString();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('visitors.violations.index', compact('violations', 'branches'));
    }

    public function show(VisitorCapaAction $capaAction): View
    {
        abort_unless(auth()->user()->can('visit.view'), 403);

        $capaAction->load([
            'visit.branch', 'visit.inspector', 'visit.visitType',
            'visitItem.photos', 'visitItem.rootCause',
            'responsible', 'updates.user', 'photos',
        ]);

        $capaAction->setAttribute('periodLabel', $capaAction->periodLabel());

        return view('visitors.violations.show', ['violation' => $capaAction]);
    }

    /**
     * Inspector or reviewer submits resolution for an open violation
     * (between inspections).
     */
    public function resolve(Request $request, VisitorCapaAction $capaAction): RedirectResponse
    {
        abort_unless($request->user()->can('visit.update') || $request->user()->can('visit.review'), 403);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:4000'],
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:20480'],
        ]);

        try {
            // Attach resolution photo to the violation's origin visit item.
            $photos = app(VisitorPhotoService::class);
            $photoData = $photos->store($request->file('photo'));

            $capaAction->visitItem->photos()->create(array_merge($photoData, [
                'visit_id' => $capaAction->visit_id,
                'capa_action_id' => $capaAction->id,
                'evidence_role' => 'resolution',
            ]));

            $this->capa->submitResolution($capaAction, $data['note'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('visitors.violation_submitted_for_review'));
    }

    /**
     * Reviewer approves a pending-review violation → closed.
     */
    public function approve(Request $request, VisitorCapaAction $capaAction): RedirectResponse
    {
        abort_unless($request->user()->can('visit.review'), 403);

        $data = $request->validate([
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->capa->approve($capaAction, $data['comment'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('visitors.violation_approved'));
    }

    /**
     * Reviewer rejects a pending-review violation → back to in-progress.
     */
    public function reject(Request $request, VisitorCapaAction $capaAction): RedirectResponse
    {
        abort_unless($request->user()->can('visit.review'), 403);

        $data = $request->validate([
            'reject_reason' => ['required', 'string', 'max:4000'],
        ]);

        try {
            $this->capa->reject($capaAction, $data['reject_reason']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('visitors.violation_rejected'));
    }
}