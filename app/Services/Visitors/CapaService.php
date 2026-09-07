<?php

namespace App\Services\Visitors;

use App\Models\VisitorCapaAction;
use App\Models\VisitorCapaUpdate;
use App\Models\VisitorVisit;
use App\Models\VisitorVisitItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CapaService
{
    /**
     * Find the oldest open violation for a given branch + checklist item
     * combination. "Open" means status is one of open, in_progress, or
     * pending_review.
     *
     * Returns null when no existing violation is found.
     */
    public function existingOpenFor(int $branchId, int $checklistItemId): ?VisitorCapaAction
    {
        return VisitorCapaAction::query()
            ->whereIn('status', ['open', 'in_progress', 'pending_review'])
            ->whereHas('visitItem', fn ($q) => $q->where('checklist_item_id', $checklistItemId))
            ->whereHas('visit', fn ($q) => $q->where('branch_id', $branchId)->where('status', 'completed'))
            ->orderBy('created_at')
            ->first();
    }

    /**
     * Build a Collection keyed by checklist_item_id of the oldest still-open
     * violation for every item in a visit that currently has one.
     *
     * The returned collection has shape { checklistItemId => VisitorCapaAction|null, ... }.
     */
    public function existingOpenMap(VisitorVisit $visit): Collection
    {
        $itemIds = $visit->items->pluck('checklist_item_id')->unique()->values()->all();

        if ($itemIds === []) {
            return collect();
        }

        return VisitorCapaAction::query()
            ->whereIn('status', ['open', 'in_progress', 'pending_review'])
            ->whereHas('visitItem', fn ($q) => $q->whereIn('checklist_item_id', $itemIds))
            ->whereHas('visit', fn ($q) => $q->where('branch_id', $visit->branch_id)->where('status', 'completed'))
            ->orderBy('created_at')
            ->with('visitItem')
            ->get()
            ->unique('visitItem.checklist_item_id')
            ->keyBy(fn ($a) => $a->visitItem->checklist_item_id);
    }

    /**
     * Create a single CAPA action for a new non-compliant item, copying the
     * checklist snapshot. Due date is action.created_at + period_hours (0 = Immediate / NULL).
     */
    public function createFromNonCompliant(VisitorVisit $visit, VisitorVisitItem $item): VisitorCapaAction
    {
        $periodHours = $item->period_hours !== null ? (float) $item->period_hours : null;

        return DB::transaction(function () use ($visit, $item, $periodHours) {
            $action = VisitorCapaAction::create([
                'visit_id' => $visit->id,
                'visit_item_id' => $item->id,
                'title' => $item->item_code.' — '.$item->item_title,
                'immediate_action' => $item->immediate_action,
                'corrective_action' => $item->corrective_action,
                'preventive_action' => $item->preventive_action,
                'status' => 'open',
                'period_hours' => $periodHours,
            ]);

            if ($periodHours !== null && $periodHours > 0) {
                $action->update([
                    'due_at' => DueDateService::dueDate($periodHours, $action->fresh()->created_at ?? now()),
                ]);
            }

            return $action->fresh();
        });
    }

    /**
     * Process all non-compliant items on visit submission.
     *
     * - If the item has a linked existing violation, route according to the
     *   inspector's follow-up choice (still_open / resolved / new_violation).
     * - Otherwise create a brand-new violation.
     */
    public function processViolationsOnSubmit(VisitorVisit $visit): void
    {
        foreach ($visit->items as $item) {
            if (!$item->isNonCompliant()) {
                continue;
            }

            $linkedAction = $item->linkedViolation;

            if ($linkedAction && $item->follow_up_action) {
                match ($item->follow_up_action) {
                    'resolved' => $this->resolveViolation($visit, $item, $linkedAction),
                    'still_open' => $this->stillOpenNote($visit, $item, $linkedAction),
                    default => $this->createViolationForItem($visit, $item),
                };
                continue;
            }

            $this->createViolationForItem($visit, $item);
        }
    }

    /**
     * Record a follow-up note for a still-open violation.
     */
    protected function stillOpenNote(VisitorVisit $visit, VisitorVisitItem $item, VisitorCapaAction $action): void
    {
        $this->recordUpdate(
            $action,
            $action->status,
            trim(($item->note ?: '')." — Inspector noted during visit #{$visit->id}")
        );
    }

    /**
     * Mark an existing violation as resolved (pending review). Critical
     * violations require resolution evidence to have been uploaded.
     */
    protected function resolveViolation(VisitorVisit $visit, VisitorVisitItem $item, VisitorCapaAction $action): void
    {
        $violationSeverity = strtolower((string) ($item->severity));

        if ($violationSeverity === 'critical') {
            $hasResolutionEvidence = $item->photos()
                ->where('capa_action_id', $action->id)
                ->where('evidence_role', 'resolution')
                ->exists();

            if (!$hasResolutionEvidence) {
                throw new RuntimeException(__('visitors.resolution_evidence_required'));
            }
        }

        $action->update([
            'status' => 'pending_review',
            'submitted_review_at' => now(),
        ]);

        $this->recordUpdate(
            $action,
            'pending_review',
            trim("Resolved during visit #{$visit->id}: ".($item->note ?? ''))
        );
    }

    /**
     * Create a new violation (CAPA action) for an NC item that has no
     * existing open violation or where the inspector chose "New Violation".
     */
    protected function createViolationForItem(VisitorVisit $visit, VisitorVisitItem $item): VisitorCapaAction
    {
        $action = $this->createFromNonCompliant($visit, $item);

        $this->recordUpdate($action, 'open', 'Violation recorded during inspection.');

        return $action;
    }

    /**
     * Inspector submits resolution for an existing open violation between
     * inspections (from the violations page). Critical violations require
     * resolution evidence attached.
     */
    public function submitResolution(VisitorCapaAction $action, ?string $note = null): void
    {
        if (!in_array($action->status, ['open', 'in_progress'], true)) {
            throw new RuntimeException(__('visitors.violation_cannot_resolve'));
        }

        $violationSeverity = strtolower((string) ($action->visitItem?->severity));

        if ($violationSeverity === 'critical') {
            $hasEvidence = $action->photos()->where('evidence_role', 'resolution')->exists();

            if (!$hasEvidence) {
                throw new RuntimeException(__('visitors.resolution_evidence_required'));
            }
        }

        $action->update([
            'status' => 'pending_review',
            'submitted_review_at' => now(),
        ]);

        $comment = $note ? "Resolved: {$note}" : 'Violation resolved.';
        $this->recordUpdate($action, 'pending_review', $comment);
    }

    /**
     * Reviewer approves a pending-review violation → closed.
     */
    public function approve(VisitorCapaAction $action, ?string $comment = null): void
    {
        if ($action->status !== 'pending_review') {
            throw new RuntimeException(__('visitors.capa_not_pending_review'));
        }

        $action->update([
            'status' => 'closed',
            'completed_at' => now(),
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        $this->recordUpdate($action, 'closed', $comment ?: 'Approved by reviewer.');
    }

    /**
     * Reviewer rejects a pending-review violation → back to in-progress.
     */
    public function reject(VisitorCapaAction $action, string $reason): void
    {
        if ($action->status !== 'pending_review') {
            throw new RuntimeException(__('visitors.capa_not_pending_review'));
        }

        $action->update([
            'status' => 'in_progress',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'reject_reason' => $reason,
        ]);

        $this->recordUpdate($action, 'rejected', "Rejected: {$reason}");
    }

    /**
     * Record a status/comment update in the CAPA history timeline.
     */
    public function recordUpdate(VisitorCapaAction $action, string $status, ?string $comment = null, ?string $photo = null): VisitorCapaUpdate
    {
        return VisitorCapaUpdate::create([
            'capa_action_id' => $action->id,
            'user_id' => auth()->id(),
            'status' => $status,
            'comment' => $comment,
            'photo' => $photo,
            'created_at' => now(),
        ]);
    }
}