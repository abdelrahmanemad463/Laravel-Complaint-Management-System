<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorVisit extends Model
{
    protected $table = 'visitors_visits';

    protected $fillable = [
        'visit_type_id', 'branch_id', 'inspector_id', 'visit_date',
        'status', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function visitType()
    {
        return $this->belongsTo(VisitorVisitType::class, 'visit_type_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function items()
    {
        return $this->hasMany(VisitorVisitItem::class, 'visit_id');
    }

    public function photos()
    {
        return $this->hasMany(VisitorVisitPhoto::class, 'visit_id');
    }

    public function capaActions()
    {
        return $this->hasMany(VisitorCapaAction::class, 'visit_id');
    }

    /**
     * Follow-ups performed during this visit (each references one or more
     * previous violations from earlier visits).
     */
    public function followUps()
    {
        return $this->hasMany(VisitorViolationFollowUp::class, 'visit_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('inspector_id', $userId);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
