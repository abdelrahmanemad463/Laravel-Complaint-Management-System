<?php

namespace App\Services\Visitors;

use App\Models\VisitorCapaAction;
use App\Models\VisitorCapaUpdate;
use App\Models\VisitorVisit;
use Illuminate\Support\Facades\DB;

class CapaService
{
    /**
     * Create a CAPA action for a non-compliant visit item, copying the checklist
     * snapshot so historical data remains stable. The due date is always
     * computed by the backend from the action's created_at + the item's
     * period_hours (0 = Immediate, no due date) and is never entered by hand.
     */
    public function createFromNonCompliant(VisitorVisit $visit, $item): VisitorCapaAction
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
     * Create CAPA actions for every non-compliant item and record the initial
     * "Violation created" history update. Skips items that already have a CAPA.
     */
    public function createForVisit(VisitorVisit $visit): void
    {
        foreach ($visit->items as $item) {
            if (!$item->isNonCompliant()) continue;
            if ($item->capaAction) continue;
            $action = $this->createFromNonCompliant($visit, $item);
            VisitorCapaUpdate::create([
                'capa_action_id' => $action->id,
                'user_id' => $visit->inspector_id,
                'status' => 'open',
                'comment' => 'Violation recorded during inspection.',
                'created_at' => now(),
            ]);
        }
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
