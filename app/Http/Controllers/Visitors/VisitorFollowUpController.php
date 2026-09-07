<?php

namespace App\Http\Controllers\Visitors;

use App\Http\Controllers\Controller;
use App\Http\Requests\Visitors\StoreFollowUpRequest;
use App\Models\VisitorVisitItem;
use App\Services\Visitors\ViolationFollowUpService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class VisitorFollowUpController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ViolationFollowUpService $service,
    ) {}

    /**
     * Record a follow-up on a previous violation (or several) for the given
     * inspection item, with optional note + evidence photos.
     */
    public function store(StoreFollowUpRequest $request, VisitorVisitItem $visitItem): JsonResponse
    {
        $this->authorize('update', $visitItem->visit);

        try {
            $followUp = $this->service->store($visitItem, $request->validated());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => __('visitors.follow_up_saved'),
            'follow_up' => [
                'id' => $followUp->id,
                'violation_ids' => $followUp->violations->pluck('id')->all(),
                'result' => $followUp->result,
            ],
        ]);
    }
}