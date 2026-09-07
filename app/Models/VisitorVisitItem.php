<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorVisitItem extends Model
{
    protected $table = 'visitors_visit_items';

    protected $fillable = [
        'visit_id', 'checklist_item_id', 'status', 'visited_at', 'root_cause_id', 'note',
        'main_kitchen', 'support_department',
        'item_code', 'item_title', 'section_name', 'severity', 'deduction_score', 'photo_required',
        'immediate_action', 'corrective_action', 'preventive_action', 'responsible', 'period_hours',
        'follow_up_action', 'linked_capa_action_id',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'deduction_score' => 'integer',
            'photo_required' => 'boolean',
            'main_kitchen' => 'boolean',
            'period_hours' => 'float',
        ];
    }

    public function visit()
    {
        return $this->belongsTo(VisitorVisit::class, 'visit_id');
    }

    public function checklistItem()
    {
        return $this->belongsTo(VisitorChecklistItem::class, 'checklist_item_id');
    }

    public function rootCause()
    {
        return $this->belongsTo(VisitorRootCause::class, 'root_cause_id');
    }

    public function photos()
    {
        return $this->hasMany(VisitorVisitPhoto::class, 'visit_item_id');
    }

    public function capaAction()
    {
        return $this->hasOne(VisitorCapaAction::class, 'visit_item_id');
    }

    /**
     * The existing open violation this item's follow-up action refers to
     * (Still Open / Resolved / New Violation on a later inspection).
     */
    public function linkedViolation()
    {
        return $this->belongsTo(VisitorCapaAction::class, 'linked_capa_action_id');
    }

    public function followUpChoice(): ?string
    {
        return $this->follow_up_action; // still_open | resolved | new_violation
    }

    public function isFollowUp(): bool
    {
        return $this->linked_capa_action_id !== null;
    }

    public function isReviewed(): bool
    {
        return $this->visited_at !== null;
    }

    public function isNonCompliant(): bool
    {
        return $this->status === 'nc';
    }

    /**
     * Whether this item's severity snapshot is Critical. Severity is stored
     * normalized to lowercase on both the master item and the visit snapshot,
     * so "Critical"/"critical"/"CRITICAL" all compare equal here.
     */
    public function isCritical(): bool
    {
        return strtolower((string) $this->severity) === 'critical';
    }

    /**
     * Evidence/photo is mandatory when the item is both Non-Compliant and
     * Critical (regardless of the item code/section). The snapshot
     * photo_required flag additionally forces evidence where a checklist item
     * was configured to require a photo.
     */
    public function requiresPhoto(): bool
    {
        return ($this->status === 'nc' && $this->isCritical()) || $this->photo_required;
    }
}
