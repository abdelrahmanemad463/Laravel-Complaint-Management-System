<?php
namespace App\Models;
use App\Models\Concerns\HasLocalizedName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class ComplaintType extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory, SoftDeletes, HasLocalizedName;
    protected $table = 'complaint_types';
    protected $fillable = ['name_en', 'name_ar', 'color', 'category_id', 'priority_id', 'is_active', 'sort_order'];
    protected function casts(): array { return ['is_active' => 'boolean', 'sort_order' => 'integer']; }
    public function complaints() { return $this->hasMany(Complaint::class, 'type_id'); }
    public function category() { return $this->belongsTo(ComplaintCategory::class); }
    public function priority() { return $this->belongsTo(Priority::class); }
}
