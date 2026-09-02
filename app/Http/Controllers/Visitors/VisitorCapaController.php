<?php

namespace App\Http\Controllers\Visitors;

use App\Http\Controllers\Controller;
use App\Models\VisitorCapaAction;
use App\Services\Visitors\CapaService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VisitorCapaController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private CapaService $capa,
    ) {}

    /**
     * Close a single CAPA action (open/in_progress/overdue -> closed).
     * Per-item workflow from the report page — not a "close all" action.
     */
    public function close(Request $request, VisitorCapaAction $capaAction): RedirectResponse
    {
        $visit = $capaAction->visit;
        $this->authorize('viewReport', $visit);

        $effective = $capaAction->effectiveStatus();
        if (in_array($effective, ['closed', 'rejected'], true)) {
            return back()->with('info', __('visitors.capa_already_closed'));
        }

        $data = $request->validate([
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $capaAction->update([
            'status' => 'closed',
            'completed_at' => now(),
        ]);

        $this->capa->recordUpdate(
            $capaAction,
            'closed',
            $data['comment'] ?? null,
        );

        return back()->with('success', __('visitors.capa_closed_success'));
    }

    /**
     * Generic status transition (in_progress, rejected, reopen) — kept for
     * future workflow without exposing a "close all".
     */
    public function update(Request $request, VisitorCapaAction $capaAction): RedirectResponse
    {
        $visit = $capaAction->visit;
        $this->authorize('viewReport', $visit);

        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,closed,rejected'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $status = $data['status'];
        $updates = ['status' => $status];
        if ($status === 'closed') {
            $updates['completed_at'] = now();
        } elseif ($status === 'open' || $status === 'in_progress') {
            $updates['completed_at'] = null;
        }

        $capaAction->update($updates);

        $this->capa->recordUpdate($capaAction, $status, $data['comment'] ?? null);

        return back()->with('success', __('visitors.capa_updated_success'));
    }
}
