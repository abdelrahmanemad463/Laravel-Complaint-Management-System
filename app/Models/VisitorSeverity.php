<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorSeverity extends Model
{
    protected $table = 'visitors_severities';

    protected $fillable = ['name', 'code', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }
}
