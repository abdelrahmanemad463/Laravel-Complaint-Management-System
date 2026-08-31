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
        'immediate_action', 'corrective_action', 'preventive_action', 'responsible', 'deadline',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'deduction_score' => 'integer',
            'photo_required' => 'boolean',
            'main_kitchen' => 'boolean',
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

    public function isReviewed(): bool
    {
        return $this->visited_at !== null;
    }

    public function isNonCompliant(): bool
    {
        return $this->status === 'nc';
    }

    public function requiresPhoto(): bool
    {
        return ($this->status === 'nc' && $this->severity === 'critical') || $this->photo_required;
    }
}
