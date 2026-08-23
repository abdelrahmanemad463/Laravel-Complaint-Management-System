<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class ComplaintSource extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'complaint_sources';
    protected $fillable = ['name', 'color', 'is_active', 'sort_order'];
    protected function casts(): array { return ['is_active' => 'boolean', 'sort_order' => 'integer']; }
    public function complaints() { return $this->hasMany(Complaint::class, 'source_id'); }
}
