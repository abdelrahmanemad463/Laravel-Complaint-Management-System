<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorCapaAction extends Model
{
    protected $table = 'visitors_capa_actions';

    protected $fillable = [
        'visit_id', 'visit_item_id', 'title', 'immediate_action', 'corrective_action',
        'preventive_action', 'responsible_user_id', 'period_hours', 'due_at', 'status',
        'completed_at', 'reviewed_at', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'period_hours' => 'float',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function visit()
    {
        return $this->belongsTo(VisitorVisit::class, 'visit_id');
    }

    public function visitItem()
    {
        return $this->belongsTo(VisitorVisitItem::class, 'visit_item_id');
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function updates()
    {
        return $this->hasMany(VisitorCapaUpdate::class, 'capa_action_id');
    }

    /**
     * The effective CAPA status as seen by reports. Open/in-progress actions
     * whose due date has passed are presented as "overdue" while the stored
     * status remains open/in_progress for the CAPA workflow.
     */
    public function effectiveStatus(): string
    {
        $status = \App\Services\Visitors\DueDateService::dueStatus($this);
        if ($status === 'overdue') {
            return 'overdue';
        }
        if ($status === 'immediate' || $status === 'due_soon' || $status === 'upcoming') {
            return $this->status;
        }
        if ($status === 'closed_late') {
            return 'closed';
        }
        if ($status === 'completed') {
            return 'closed';
        }
        return $this->status;
    }

    /**
     * Full due-status taxonomy: immediate / upcoming / due_soon / overdue /
     * completed / closed_late / rejected.
     */
    public function dueStatus(): string
    {
        return \App\Services\Visitors\DueDateService::dueStatus($this);
    }

    /**
     * Human-readable label for the period snapshot held by this action.
     */
    public function periodLabel(): string
    {
        return \App\Services\Visitors\DueDateService::hoursLabel($this->period_hours);
    }
}
