<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorVisitPhoto extends Model
{
    protected $table = 'visitors_visit_photos';

    protected $fillable = [
        'visit_id', 'visit_item_id', 'path', 'original_name', 'mime_type',
        'original_size', 'compressed_size',
    ];

    public function visit()
    {
        return $this->belongsTo(VisitorVisit::class, 'visit_id');
    }

    public function visitItem()
    {
        return $this->belongsTo(VisitorVisitItem::class, 'visit_item_id');
    }
}
