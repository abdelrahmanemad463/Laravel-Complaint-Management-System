<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorViolationFollowUp extends Model
{
    protected $table = 'visitors_violation_follow_ups';

    protected $fillable = [
        'visit_id', 'visit_item_id', 'performed_by_id', 'follow_up_note', 'result', 'followed_up_at',
    ];

    protected function casts(): array
    {
        return [
            'followed_up_at' => 'datetime',
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

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }

    /**
     * Every previous violation this follow-up addressed.
     */
    public function violations()
    {
        return $this->belongsToMany(VisitorCapaAction::class, 'visitors_violation_follow_up_items', 'violation_follow_up_id', 'capa_action_id');
    }

    /**
     * Evidence photos uploaded as part of this follow-up.
     */
    public function photos()
    {
        return $this->hasMany(VisitorVisitPhoto::class, 'violation_follow_up_id');
    }

    public function isResolved(): bool
    {
        return $this->result === 'resolved';
    }
}