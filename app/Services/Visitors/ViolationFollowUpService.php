<?php

namespace App\Services\Visitors;

use App\Models\VisitorCapaAction;
use App\Models\VisitorViolationFollowUp;
use App\Models\VisitorVisit;
use App\Models\VisitorVisitItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Follow-up Previous Violations in a New Visit.
 *
 * A follow-up is a separate, non-destructive event recorded during a later
 * visit. It references one or more previous open violations for the same
 * inspection item + branch (never a different item/branch), optionally carries
 * evidence photos and a note, and either moves the referenced violations to
 * pending review (resolved — a reviewer confirms) or leaves them open
 * (still_open). Historical visits/reports are never modified.
 */
class ViolationFollowUpService
{
    /**
     * Violations that may still be followed up. pending_review already has a
     * submitted resolution awaiting the reviewer, so it is not eligible.
     */
    public const ELIGIBLE_STATUSES = ['open', 'in_progress'];

    public function __construct(
        private CapaService $capa,
        private VisitorPhotoService $photos,
    ) {}

    /**
     * All eligible previous violations for one inspection item.
     */
    public function candidatesFor(VisitorVisitItem $item): Collection
    {
        if ($item->visit === null || $item->visit->isCompleted()) {
            return collect();
        }

        return VisitorCapaAction::query()
            ->whereIn('status', self::ELIGIBLE_STATUSES)
            ->whereHas('visitItem', fn ($q) => $q->where('checklist_item_id', $item->checklist_item_id))
            ->whereHas('visit', fn ($q) => $q
                ->where('branch_id', $item->visit->branch_id)
                ->where('status', 'completed'))
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Map every eligible previous violation in the visit's items, keyed by
     * checklist_item_id, so the inspection page can drive the follow-up button
     * (count) and the modal (violation checklist).
     */
    public function candidateMap(VisitorVisit $visit): Collection
    {
        if ($visit->isCompleted()) {
            return collect();
        }

        $itemIds = $visit->items->pluck('checklist_item_id')->unique()->values()->all();

        if ($itemIds === []) {
            return collect();
        }

        return VisitorCapaAction::query()
            ->whereIn('status', self::ELIGIBLE_STATUSES)
            ->whereHas('visitItem', fn ($q) => $q->whereIn('checklist_item_id', $itemIds))
            ->whereHas('visit', fn ($q) => $q
                ->where('branch_id', $visit->branch_id)
                ->where('status', 'completed'))
            ->orderBy('created_at')
            ->with(['visitItem', 'visit', 'photos'])
            ->get()
            ->groupBy(fn ($a) => $a->visitItem->checklist_item_id);
    }

    /**
     * Server-side eligibility: the violation must be open/in-progress, belong
     * to the SAME inspection item and branch as the follow-up, and originate
     * from a completed visit. The current visit itself must not be completed.
     */
    public function isEligible(VisitorVisitItem $item, VisitorCapaAction $action): bool
    {
        if ($item->visit === null || $item->visit->isCompleted()) {
            return false;
        }
        if (!in_array($action->status, self::ELIGIBLE_STATUSES, true)) {
            return false;
        }
        if ($action->visitItem === null || $action->visitItem->checklist_item_id !== $item->checklist_item_id) {
            return false;
        }
        if ($action->visit === null || $action->visit->status !== 'completed') {
            return false;
        }

        return $action->visit->branch_id === $item->visit->branch_id;
    }

    /**
     * Create the follow-up record, link all selected violations, store the
     * (optional) evidence photos and apply the follow-up result to each
     * referenced violation — all inside one transaction.
     */
    public function store(VisitorVisitItem $item, array $data): VisitorViolationFollowUp
    {
        if ($item->visit === null || $item->visit->isCompleted()) {
            throw new RuntimeException(__('visitors.visit_completed_no_edit'));
        }

        $ids = array_values(array_unique(array_map('intval', $data['violation_ids']) ?: []));

        if ($ids === []) {
            throw new RuntimeException(__('visitors.follow_up_no_selection'));
        }

        $actions = VisitorCapaAction::query()
            ->whereIn('id', $ids)
            ->with(['visit', 'visitItem'])
            ->get();

        if ($actions->count() !== count($ids)) {
            throw new RuntimeException(__('visitors.follow_up_invalid_violation'));
        }

        foreach ($actions as $action) {
            if (!$this->isEligible($item, $action)) {
                throw new RuntimeException(__('visitors.follow_up_invalid_violation'));
            }
        }

        $result = $data['result'];
        $note = isset($data['note']) && trim((string) $data['note']) !== ''
            ? trim((string) $data['note'])
            : null;
        $files = $data['photos'] ?? [];

        return DB::transaction(function () use ($item, $actions, $result, $note, $files) {
            $followUp = VisitorViolationFollowUp::create([
                'visit_id' => $item->visit_id,
                'visit_item_id' => $item->id,
                'performed_by_id' => auth()->id(),
                'follow_up_note' => $note,
                'result' => $result,
                'followed_up_at' => now(),
            ]);

            $followUp->violations()->attach($actions->pluck('id')->all());

            foreach ($files as $file) {
                $photoData = $this->photos->store($file);

                $item->photos()->create(array_merge($photoData, [
                    'visit_id' => $item->visit_id,
                    'violation_follow_up_id' => $followUp->id,
                    'evidence_role' => 'follow_up',
                ]));
            }

            foreach ($actions as $action) {
                $this->applyResult($item->visit_id, $action, $result, $note);
            }

            return $followUp->fresh();
        });
    }

    private function applyResult(int $visitId, VisitorCapaAction $action, string $result, ?string $note): void
    {
        if ($result === 'resolved') {
            $this->capa->markResolvedFromFollowUp($action, $visitId, $note);
            return;
        }

        $this->capa->recordUpdate(
            $action,
            $action->status,
            'Follow-up during visit #'.$visitId.': '.($note ?: 'Still non-compliant.')
        );
    }
}