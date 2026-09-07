<?php

namespace App\Services\Visitors;

use App\Models\VisitorChecklistItem;
use App\Models\VisitorVisit;
use App\Models\VisitorVisitItem;
use App\Models\VisitorVisitPhoto;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VisitService
{
    public function __construct(
        private VisitScoreService $score,
        private CapaService $capa,
    ) {}

    /**
     * Start a new visit: create the record and pre-create every checklist item
     * with an immutable snapshot of the checklist config. Items start
     * unreviewed (pending, no visited_at) so nothing is implicitly "Compliant"
     * until the inspector actively chooses a status.
     */
    public function start(int $visitTypeId, int $branchId, string $visitDate): VisitorVisit
    {
        return DB::transaction(function () use ($visitTypeId, $branchId, $visitDate) {
            $visit = VisitorVisit::create([
                'visit_type_id' => $visitTypeId,
                'branch_id' => $branchId,
                'inspector_id' => auth()->id(),
                'visit_date' => $visitDate,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            $items = VisitorChecklistItem::where('visit_type_id', $visitTypeId)
                ->where('is_active', true)
                ->with('section')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            foreach ($items as $item) {
                VisitorVisitItem::create([
                    'visit_id' => $visit->id,
                    'checklist_item_id' => $item->id,
                    'status' => 'pending',
                    'visited_at' => null,
                    'item_code' => $item->code,
                    'item_title' => $item->title,
                    'section_name' => $item->section?->name ?? 'General',
                    'severity' => $item->severity,
                    'deduction_score' => $item->deduction_score,
                    'photo_required' => $item->photo_required,
                    'immediate_action' => $item->immediate_action,
                    'corrective_action' => $item->corrective_action,
                    'preventive_action' => $item->preventive_action,
                    'responsible' => $item->responsible,
                    'period_hours' => $item->period_hours,
                ]);
            }

            return $visit;
        });
    }

    /**
     * Persist an inspector's answer for a single item. Never trusts client
     * values for severity, deduction, or predefined actions.
     */
    public function saveItem(VisitorVisitItem $item, array $data): VisitorVisitItem
    {
        if ($item->visit->isCompleted()) {
            throw new RuntimeException('This visit is completed and can no longer be edited.');
        }

        $item->status = $data['status'] ?? $item->status;
        $item->visited_at = $item->visited_at ?? now();
        if ($item->status === 'nc') {
            $item->root_cause_id = $data['root_cause_id'] ?? null;
            $item->note = $data['note'] ?? null;
            $item->main_kitchen = filter_var($data['main_kitchen'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $item->support_department = $data['support_department'] ?? null;
        } else {
            $item->root_cause_id = null;
            $item->note = null;
            $item->main_kitchen = false;
            $item->support_department = null;
        }
        $item->follow_up_action = null;
        $item->linked_capa_action_id = null;
        $item->save();

        return $item;
    }

    /**
     * Validate and submit the visit inside a transaction.
     */
    public function submit(VisitorVisit $visit): VisitorVisit
    {
        return DB::transaction(function () use ($visit) {
            if ($visit->isCompleted()) {
                throw new RuntimeException('This visit is already completed.');
            }

            $visit->load('items.rootCause', 'items.photos');

            $unreviewed = $visit->items->filter(fn ($i) => !$i->isReviewed());
            if ($unreviewed->isNotEmpty()) {
                throw new RuntimeException('All checklist items must be reviewed before submission.');
            }

            // Validate required evidence for the current visit's non-compliant
            // items. Follow-up evidence photos (evidence_role = follow_up) never
            // count as the mandatory initial evidence of a NEW violation.
            foreach ($visit->items as $item) {
                if (!$item->isNonCompliant()) {
                    continue;
                }

                if ($item->requiresPhoto()) {
                    $hasInitial = $item->photos->contains(fn ($p) => $p->evidence_role !== 'follow_up');
                    if (!$hasInitial) {
                        throw new RuntimeException(__('visitors.evidence_photo_required'));
                    }
                }
            }

            $this->capa->processViolationsOnSubmit($visit);

            $visit->status = 'completed';
            $visit->completed_at = now();
            $visit->save();

            return $visit;
        });
    }
}
