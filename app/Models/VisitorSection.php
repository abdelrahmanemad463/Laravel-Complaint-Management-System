<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorSection extends Model
{
    protected $table = 'visitors_sections';

    protected $fillable = ['name', 'code', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function checklistItems()
    {
        return $this->hasMany(VisitorChecklistItem::class, 'section_id');
    }
}
