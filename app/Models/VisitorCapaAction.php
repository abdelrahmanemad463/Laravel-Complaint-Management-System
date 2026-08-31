<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorCapaAction extends Model
{
    protected $table = 'visitors_capa_actions';

    protected $fillable = [
        'visit_id', 'visit_item_id', 'title', 'immediate_action', 'corrective_action',
        'preventive_action', 'responsible_user_id', 'due_date', 'status',
        'completed_at', 'reviewed_at', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
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
        if (in_array($this->status, ['open', 'in_progress'], true) && $this->due_date !== null) {
            $due = $this->due_date;
            if ($due instanceof \Carbon\CarbonInterface && $due->isBefore(today())) {
                return 'overdue';
            }
            if (is_string($due) && $due < today()->toDateString()) {
                return 'overdue';
            }
        }
        return $this->status;
    }
}
