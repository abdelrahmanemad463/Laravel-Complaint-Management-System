<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Priority extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['name', 'color', 'level', 'is_active', 'sort_order'];
    protected function casts(): array { return ['is_active' => 'boolean', 'level' => 'integer', 'sort_order' => 'integer']; }
    public function complaints() { return $this->hasMany(Complaint::class, 'priority_id'); }
}
