<?php
namespace App\Services;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
class ActivityLogService
{
    public function record(string $action, ?Model $subject, ?string $description = null, ?array $old = null, ?array $new = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => auth()->id(), 'action' => $action,
            'subject_type' => $subject?->getMorphClass(), 'subject_id' => $subject?->getKey(),
            'description' => $description, 'old_values' => $old, 'new_values' => $new,
            'created_at' => now(),
        ]);
    }
}
