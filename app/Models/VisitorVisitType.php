<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorVisitType extends Model
{
    protected $table = 'visitors_visit_types';

    protected $fillable = ['name', 'code', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function checklistItems()
    {
        return $this->hasMany(VisitorChecklistItem::class, 'visit_type_id');
    }

    public function visits()
    {
        return $this->hasMany(VisitorVisit::class, 'visit_type_id');
    }
}
