<?php
namespace App\Models;
use App\Models\Concerns\HasLocalizedName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class ComplaintStatus extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory, SoftDeletes, HasLocalizedName;
    protected $fillable = ['name_en', 'name_ar', 'color', 'is_active', 'sort_order'];
    protected function casts(): array { return ['is_active' => 'boolean', 'sort_order' => 'integer']; }
    public function complaints() { return $this->hasMany(Complaint::class, 'status_id'); }
}
