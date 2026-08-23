<?php
namespace App\Models;
class ActivityLog extends \Illuminate\Database\Eloquent\Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'description', 'old_values', 'new_values', 'created_at'];
    protected function casts(): array { return ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime']; }
    public function user() { return $this->belongsTo(User::class); }
    public function subject() { return $this->morphTo(); }
}
