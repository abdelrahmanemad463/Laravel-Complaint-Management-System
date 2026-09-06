<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id', 'branch_id', 'service_id', 'source_id', 'category_id', 'type_id',
        'priority_id', 'status_id', 'short_description', 'description', 'complaint_date',
        'serial_number', 'price',
        'created_by', 'resolved_by', 'resolved_at', 'resolution',
    ];

    protected function casts(): array
    {
        return ['complaint_date' => 'date', 'resolved_at' => 'datetime', 'price' => 'decimal:2'];
    }

    public function customer() { return $this->belongsTo(Customer::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function service() { return $this->belongsTo(Service::class); }
    public function source() { return $this->belongsTo(ComplaintSource::class, 'source_id'); }
    public function category() { return $this->belongsTo(ComplaintCategory::class, 'category_id'); }
    public function type() { return $this->belongsTo(ComplaintType::class, 'type_id'); }
    public function priority() { return $this->belongsTo(Priority::class); }
    public function status() { return $this->belongsTo(ComplaintStatus::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function resolver() { return $this->belongsTo(User::class, 'resolved_by'); }
    public function statusHistories() { return $this->hasMany(ComplaintStatusHistory::class)->latest('changed_at'); }
    public function activityLogs() { return $this->morphMany(ActivityLog::class, 'subject'); }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $descriptionSearch = trim((string) ($filters['description'] ?? ''));

        return $query
            ->when($filters['complaint_id'] ?? null, fn ($q, $v) => $q->whereKey($v))
            ->when($filters['customer_id'] ?? null, fn ($q, $v) => $q->where('customer_id', $v))
            ->when($filters['branch_ids'] ?? [], fn ($q, $v) => $q->whereIn('branch_id', (array) $v))
            ->when($filters['service_id'] ?? null, fn ($q, $v) => $q->where('service_id', $v))
            ->when($filters['source_id'] ?? null, fn ($q, $v) => $q->where('source_id', $v))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['type_id'] ?? null, fn ($q, $v) => $q->where('type_id', $v))
            ->when($filters['priority_id'] ?? null, fn ($q, $v) => $q->where('priority_id', $v))
            ->when($filters['status_id'] ?? null, fn ($q, $v) => $q->where('status_id', $v))
            ->when($filters['created_by'] ?? null, fn ($q, $v) => $q->where('created_by', $v))
            ->when($descriptionSearch !== '', fn ($q) => $q->where(function ($descriptionQuery) use ($descriptionSearch) {
                $descriptionQuery
                    ->where('short_description', 'like', "%{$descriptionSearch}%")
                    ->orWhere('description', 'like', "%{$descriptionSearch}%");
            }))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('complaint_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('complaint_date', '<=', $v));
    }
}
