<?php
namespace App\Services;
use App\Models\Complaint;
use App\Models\ComplaintStatus;
use App\Models\ComplaintStatusHistory;
use Illuminate\Support\Facades\DB;
class ComplaintStatusService
{
    public function change(Complaint $complaint, int $statusId, ?string $reason = null): void
    {
        if ((int) $complaint->status_id === $statusId) return;
        DB::transaction(function () use ($complaint, $statusId, $reason) {
            $oldStatus = $complaint->status;
            $newStatus = ComplaintStatus::findOrFail($statusId);
            $complaint->status_id = $statusId;
            if (mb_strtolower($newStatus->name) === 'solved') {
                $complaint->resolved_by = auth()->id();
                $complaint->resolved_at = now();
            }
            $complaint->save();
            ComplaintStatusHistory::create([
                'complaint_id' => $complaint->id, 'from_status_id' => $oldStatus?->id,
                'to_status_id' => $newStatus->id, 'reason' => $reason,
                'changed_by' => auth()->id(), 'changed_at' => now(),
            ]);
            app(ActivityLogService::class)->record('complaint.status_changed', $complaint->fresh(),
                __('complaints.status_changed_log', ['from' => $oldStatus?->name ?? '—', 'to' => $newStatus->name]),
                ['status_id' => $oldStatus?->id], ['status_id' => $newStatus->id]);
        });
    }
}
