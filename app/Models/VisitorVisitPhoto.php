<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorVisitPhoto extends Model
{
    protected $table = 'visitors_visit_photos';

    protected $fillable = [
        'visit_id', 'visit_item_id', 'capa_action_id', 'violation_follow_up_id', 'evidence_role',
        'path', 'original_name', 'mime_type', 'original_size', 'compressed_size',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function visit()
    {
        return $this->belongsTo(VisitorVisit::class, 'visit_id');
    }

    public function visitItem()
    {
        return $this->belongsTo(VisitorVisitItem::class, 'visit_item_id');
    }

    /**
     * The violation (corrective-action record) this photo resolves.
     * Null for initial evidence uploaded on the inspection form.
     */
    public function capaAction()
    {
        return $this->belongsTo(VisitorCapaAction::class, 'capa_action_id');
    }

    /**
     * The follow-up record this photo belongs to. Null for initial/resolution
     * evidence uploaded through the regular photo flow.
     */
    public function followUp()
    {
        return $this->belongsTo(VisitorViolationFollowUp::class, 'violation_follow_up_id');
    }

    public function isResolution(): bool
    {
        return $this->evidence_role === 'resolution';
    }

    public function isFollowUp(): bool
    {
        return $this->evidence_role === 'follow_up';
    }
}
