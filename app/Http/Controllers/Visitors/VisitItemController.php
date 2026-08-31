<?php

namespace App\Http\Controllers\Visitors;

use App\Http\Controllers\Controller;
use App\Http\Requests\Visitors\UpdateVisitItemRequest;
use App\Models\VisitorVisitItem;
use App\Services\Visitors\VisitService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class VisitItemController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private VisitService $service,
        private \App\Services\Visitors\VisitScoreService $score,
    ) {}

    /**
     * Autosave an inspector's answer for a visit item.
     */
    public function update(UpdateVisitItemRequest $request, VisitorVisitItem $visitItem): JsonResponse
    {
        $this->authorize('update', $visitItem->visit);

        try {
            $item = $this->service->saveItem($visitItem, $request->validated());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $item->visit->load('items');
        $score = $this->score->calculate($item->visit);

        return response()->json([
            'ok' => true,
            'visited_at' => $item->visited_at?->toIso8601String(),
            'status' => $item->status,
            'reviewed' => $item->visit->items->filter->isReviewed()->count(),
            'total' => $item->visit->items->count(),
            'score' => $score,
        ]);
    }
}
