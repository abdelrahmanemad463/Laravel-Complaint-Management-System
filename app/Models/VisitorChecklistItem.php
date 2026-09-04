<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorChecklistItem extends Model
{
    protected $table = 'visitors_checklist_items';

    protected $fillable = [
        'visit_type_id', 'section_id', 'code', 'title', 'severity', 'deduction_score',
        'photo_required', 'immediate_action', 'corrective_action', 'preventive_action',
        'responsible', 'period_hours', 'sort_order', 'is_active', 'root_cause_id',
    ];

    protected function casts(): array
    {
        return [
            'deduction_score' => 'integer',
            'photo_required' => 'boolean',
            'period_hours' => 'float',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Human-readable label for the configured corrective-action period.
     */
    public function periodLabel(): string
    {
        return \App\Services\Visitors\DueDateService::hoursLabel($this->period_hours);
    }

    public function visitType()
    {
        return $this->belongsTo(VisitorVisitType::class, 'visit_type_id');
    }

    public function section()
    {
        return $this->belongsTo(VisitorSection::class, 'section_id');
    }

    public function rootCause()
    {
        return $this->belongsTo(VisitorRootCause::class, 'root_cause_id');
    }
}
