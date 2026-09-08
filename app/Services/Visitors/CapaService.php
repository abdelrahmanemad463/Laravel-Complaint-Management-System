<?php

namespace App\Services\Visitors;

use App\Models\VisitorCapaAction;
use App\Models\VisitorCapaUpdate;
use App\Models\VisitorVisit;
use App\Models\VisitorVisitItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CapaService
{
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
     * Every NC item creates its own brand-new violation for the current
     * inspection. Following up previous violations is a separate action
     * recorded through ViolationFollowUpService and never suppresses the
     * current inspection result.
     */
    public function processViolationsOnSubmit(VisitorVisit $visit): void
    {
        foreach ($visit->items as $item) {
            if ($item->isNonCompliant()) {
                $this->createViolationForItem($visit, $item);
            }
        }
    }

    /**
     * Create a new violation (CAPA action) for an NC item.
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
     * resolution evidence attached. The submitter is recorded so a reviewer —
     * never the submitter themselves — can approve/reject.
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
            'submitted_by' => auth()->id(),
            'resolution_note' => $note !== null && trim($note) !== '' ? trim($note) : null,
        ]);

        $comment = $note !== null && trim($note) !== '' ? "Resolved: {$note}" : 'Violation resolved.';
        $evidence = $action->resolutionPhotos()->latest('id')->first()?->original_name;

        $this->recordUpdate($action, 'pending_review', $comment, $evidence);
    }

    /**
     * Mark an existing violation as "resolved" from a follow-up performed
     * during a later visit. The violation moves to pending review so a
     * reviewer confirms it before it is closed. Follow-up evidence is
     * optional by design: the reviewer decides, not the uploader.
     */
    public function markResolvedFromFollowUp(VisitorCapaAction $action, int $visitId, ?string $note = null): void
    {
        if (!in_array($action->status, ['open', 'in_progress'], true)) {
            throw new RuntimeException(__('visitors.violation_cannot_resolve'));
        }

        $action->update([
            'status' => 'pending_review',
            'submitted_review_at' => now(),
            'submitted_by' => auth()->id(),
            'resolution_note' => $note !== null && trim($note) !== '' ? trim($note) : null,
        ]);

        $this->recordUpdate(
            $action,
            'pending_review',
            trim('Resolved via follow-up during visit #'.$visitId.': '.($note ?? ''))
        );
    }

    /**
     * Reviewer approves a pending-review violation → closed. The user who
     * submitted the resolution may never approve their own submission
     * (separation of duties).
     */
    public function approve(VisitorCapaAction $action, ?string $comment = null): void
    {
        if ($action->status !== 'pending_review') {
            throw new RuntimeException(__('visitors.capa_not_pending_review'));
        }

        if ($action->submitted_by !== null && (int) $action->submitted_by === (int) auth()->id()) {
            throw new RuntimeException(__('visitors.cannot_self_approve'));
        }

        $action->update([
            'status' => 'closed',
            'completed_at' => now(),
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'closed_by' => auth()->id(),
        ]);

        $closingNote = $comment !== null && trim($comment) !== ''
            ? 'Approved by reviewer. '.trim($comment)
            : 'Approved by reviewer.';
        $this->recordUpdate($action, 'closed', $closingNote);
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