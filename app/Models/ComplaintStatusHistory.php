<?php
namespace App\Models;
class ComplaintStatusHistory extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = ['complaint_id', 'from_status_id', 'to_status_id', 'reason', 'changed_by', 'changed_at'];
    protected function casts(): array { return ['changed_at' => 'datetime']; }
    public function complaint() { return $this->belongsTo(Complaint::class); }
    public function fromStatus() { return $this->belongsTo(ComplaintStatus::class, 'from_status_id'); }
    public function toStatus() { return $this->belongsTo(ComplaintStatus::class, 'to_status_id'); }
    public function changer() { return $this->belongsTo(User::class, 'changed_by'); }
}
