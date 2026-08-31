<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorRootCause extends Model
{
    protected $table = 'visitors_root_causes';

    protected $fillable = ['name', 'code', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
