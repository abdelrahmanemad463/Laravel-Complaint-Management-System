<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorCapaUpdate extends Model
{
    protected $table = 'visitors_capa_updates';

    public $timestamps = false;

    protected $fillable = ['capa_action_id', 'user_id', 'status', 'comment', 'photo', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function capaAction()
    {
        return $this->belongsTo(VisitorCapaAction::class, 'capa_action_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
